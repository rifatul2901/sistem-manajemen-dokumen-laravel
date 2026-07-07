<?php

namespace App\Http\Controllers;

use App\Models\DriveConfig;
use App\Models\DriveToken;
use App\Models\Upload;
use App\Models\GenerateHistory;
use App\Services\GoogleDriveService;
use App\Services\DocumentService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\IOFactory;

class DashboardController extends Controller
{
    protected DocumentService $docService;

    public function __construct(DocumentService $docService)
    {
        $this->docService = $docService;
    }

    public function index()
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        $histories = GenerateHistory::where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->limit(50)
            ->get(['id', 'filename', 'filepath', 'created_at', 'waktu']);
        $driveConfig = Cache::remember('drive_config', 300, fn() => DriveConfig::first());

        return view('dashboard.index', compact('user', 'histories', 'driveConfig'));
    }

    public function bacaExcel(Request $request)
    {
        $request->validate(['excel_file' => 'required|file|mimes:xlsx|max:10240']);

        try {
            $spreadsheet = IOFactory::load($request->file('excel_file')->getPathname());
            $worksheet = $spreadsheet->getActiveSheet();
            $rows = $worksheet->toArray(null, true, true, true);

            if (empty($rows) || count($rows) < 2) {
                return back()->with('error', 'File Excel kosong atau tidak ada baris data.');
            }

            $headers = array_values($rows[1]);
            $data = [];
            $colLetters = [];
            for ($idx = 0; $idx < count($headers); $idx++) {
                $colLetters[$idx] = \PhpOffice\PhpSpreadsheet\Cell\Coordinate::stringFromColumnIndex($idx + 1);
            }
            $maxRows = min(count($rows), 5001);
            for ($i = 2; $i < $maxRows; $i++) {
                $row = $rows[$i];
                $item = [];
                foreach ($headers as $idx => $header) {
                    $item[$header] = $row[$colLetters[$idx]] ?? '';
                }
                $item['_index'] = $i - 2;
                $data[] = $item;
            }

            session(['excel_data' => $data]);
            session(['excel_headers' => $headers]);
            session()->flash('success', count($data) . ' baris data terbaca dari Excel.');

            return back();
        } catch (\Exception $e) {
            Log::warning('Excel parse failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal membaca file Excel. Pastikan format file benar.');
        }
    }

    public function detectPlaceholders(Request $request)
    {
        $request->validate(['template' => 'required|file|mimes:docx']);

        $data = session('excel_data', []);
        if (empty($data)) {
            return response()->json(['error' => 'Upload Excel dulu'], 400);
        }

        $validColumns = array_keys($data[0]);

        $templateDir = storage_path('app/uploads/templates');
        if (!is_dir($templateDir)) {
            mkdir($templateDir, 0755, true);
        }
        $originalName = $request->file('template')->getClientOriginalName();
        $safeName = static::sanitizeFilename($originalName);
        $storedPath = $templateDir . '/' . uniqid('tpl_') . '_' . $safeName;
        copy($request->file('template')->getPathname(), $storedPath);

        $counts = $this->docService->detectPlaceholderCounts(
            $storedPath,
            $validColumns
        );

        session(['template_path' => $storedPath]);
        session(['template_name' => $safeName]);

        return response()->json([
            'counts' => $counts,
            'max'    => empty($counts) ? 0 : max($counts),
            'detail' => collect($counts)->map(fn($v, $k) => "$k x$v")->implode(', '),
        ]);
    }

    public function generate(Request $request)
    {
        $request->validate([
            'judul' => 'nullable|string|max:255',
            'format' => 'required|in:docx,pdf',
            'data_mode' => 'required|in:manual,otomatis',
            'selected_data' => 'nullable|json',
        ]);

        $user = Auth::user();
        $data = session('excel_data', []);
        $templateSource = session('template_path');

        if (empty($data)) {
            return back()->with('error', 'Data Excel belum diupload.');
        }

        if (!$templateSource || !file_exists($templateSource)) {
            $defaultTemplate = storage_path('app/templates/template_multinama.docx');
            if (file_exists($defaultTemplate)) {
                $templateSource = $defaultTemplate;
            } else {
                return back()->with('error', 'Template tidak ditemukan. Upload template atau pastikan template default tersedia.');
            }
        }

        if ($request->data_mode === 'manual') {
            $selectedIndices = json_decode($request->selected_data ?: '[]', true);
            if (empty($selectedIndices)) {
                return back()->with('error', 'Pilih minimal 1 baris data secara manual.');
            }
            $selectedRecords = collect($data)->whereIn('_index', $selectedIndices)->values()->toArray();
        } else {
            $selectedRecords = $data;
        }

        if (empty($selectedRecords)) {
            return back()->with('error', 'Tidak ada data yang dipilih.');
        }

        $folderTanggal = storage_path('app/hasil_generate/Hasil_Cetak_' . now()->format('d_m_Y'));
        $folderOutput = $folderTanggal . '/' . now()->format('H_i_s');
        if (!is_dir($folderOutput)) {
            mkdir($folderOutput, 0755, true);
        }

        $namaFile = $request->judul ? DocumentService::bersihkanNamaFile($request->judul) : 'Dokumen_' . Str::random(6);
        $docxOutput = $folderOutput . '/' . $namaFile . '.docx';

        try {
            $extraContext = [
                'Judul_Project' => 'Dokumen Multi-Data',
                'Tanggal_Cetak' => now()->format('d F Y'),
            ];

            $this->docService->generateMultiKalimat(
                $templateSource,
                $selectedRecords,
                $extraContext,
                $docxOutput
            );

            if ($request->format === 'pdf') {
                $pdfOutput = $folderOutput . '/' . $namaFile . '.pdf';
                $this->docService->convertDocxToPdf($docxOutput, $pdfOutput);
                $finalPath = $pdfOutput;
                $finalName = $namaFile . '.pdf';
            } else {
                $finalPath = $docxOutput;
                $finalName = $namaFile . '.docx';
            }

            GenerateHistory::create([
                'user_id'  => $user->id,
                'filename' => $finalName,
                'filepath' => $finalPath,
                'waktu'    => now()->format('d-m-Y H:i:s'),
            ]);

            $templatePath = session('template_path');
            if ($templatePath && file_exists($templatePath) && str_starts_with($templatePath, storage_path('app/uploads/templates/'))) {
                @unlink($templatePath);
            }
            session()->forget(['template_path', 'template_name']);

            return back()->with('success', 'Dokumen berhasil dibuat.')
                ->with('generated_file', $finalPath)
                ->with('generated_name', $finalName);
        } catch (\Exception $e) {
            Log::error('Generate failed', ['user' => $user->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Gagal membuat dokumen. Silakan coba lagi.');
        }
    }

    public function downloadGenerated($id)
    {
        $history = GenerateHistory::findOrFail($id);

        if ($history->user_id !== Auth::id()) {
            abort(403);
        }

        if (!file_exists($history->filepath)) {
            return back()->with('error', 'File sudah tidak tersedia.');
        }

        return response()->download($history->filepath, $history->filename);
    }

    public function uploadBerkas(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:docx,pdf|max:10240',
            'folder_tujuan' => 'required|string|max:255',
            'catatan' => 'nullable|string|max:2000',
        ]);

        $user = Auth::user();
        $driveConfig = DriveConfig::first();

        if (!$driveConfig || !$driveConfig->folder_id) {
            return back()->with('error', 'Admin belum mengatur folder Google Drive tim.');
        }

        $adminToken = DriveToken::first();
        if (!$adminToken) {
            return back()->with('error', 'Admin belum menghubungkan Google Drive.');
        }

        try {
            $driveService = new GoogleDriveService($adminToken);
        } catch (\Exception $e) {
            Log::error('Google Drive connection failed', ['user' => $user->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Gagal koneksi Google Drive. Silakan coba lagi.');
        }

        $folderIdTujuan = $driveConfig->folder_id;
        if ($request->folder_tujuan !== '(folder utama)') {
            try {
                $subfolders = $driveService->listSubfolders($driveConfig->folder_id);
                foreach ($subfolders as $sf) {
                    if ($sf->getName() === $request->folder_tujuan) {
                        $folderIdTujuan = $sf->getId();
                        break;
                    }
                }
            } catch (\Exception $e) {
                Log::error('List subfolders failed', ['user' => $user->id, 'error' => $e->getMessage()]);
                return back()->with('error', 'Gagal mengambil daftar folder.');
            }
        }

        $file = $request->file('file');
        $originalName = $file->getClientOriginalName();
        $safeName = static::sanitizeFilename($originalName);

        try {
            $existing = $driveService->checkFileExists($folderIdTujuan, $safeName);
            if (!empty($existing)) {
                return back()->with('upload_result', 'File ditolak: nama "' . $safeName . '" sudah ada di folder tersebut.');
            }

            $fileId = $driveService->uploadFile(
                file_get_contents($file->getPathname()),
                $safeName,
                $folderIdTujuan
            );

            $waktuSekarang = now()->format('d-m-Y H:i');

            Upload::create([
                'user_id'            => $user->id,
                'drive_file_id'      => $fileId,
                'filename'           => $safeName,
                'subfolder'          => $request->folder_tujuan,
                'uploader_name'      => $user->nama,
                'upload_time'        => $waktuSekarang,
                'status'             => 'pending',
                'catatan_user'       => strip_tags($request->catatan ?? ''),
                'catatan_admin'      => [],
                'notifikasi_pdf'     => '',
                'tanggal_kirim_awal' => $waktuSekarang,
            ]);

            return back()->with('upload_result', 'Berkas "' . $safeName . '" berhasil diupload dan menunggu review Admin.');
        } catch (\Exception $e) {
            Log::error('Upload failed', ['user' => $user->id, 'error' => $e->getMessage()]);
            return back()->with('upload_result', 'Gagal upload ke Drive. Silakan coba lagi.');
        }
    }

    public function uploadRevisi(Request $request)
    {
        $request->validate([
            'upload_id' => 'required|exists:uploads,id',
            'file' => 'required|file|mimes:docx,pdf|max:10240',
        ]);

        $user = Auth::user();
        $upload = Upload::findOrFail($request->upload_id);

        if ($upload->user_id !== $user->id || $upload->status !== 'revision') {
            return back()->with('error', 'Berkas tidak valid untuk direvisi.');
        }

        $driveConfig = DriveConfig::first();
        if (!$driveConfig || !$driveConfig->folder_id) {
            return back()->with('error', 'Folder Drive belum dikonfigurasi.');
        }

        $adminToken = DriveToken::first();
        if (!$adminToken) {
            return back()->with('error', 'Admin belum menghubungkan Google Drive.');
        }

        try {
            $driveService = new GoogleDriveService($adminToken);

            $revisionFile = $request->file('file');
            $revisionFileName = static::sanitizeFilename($revisionFile->getClientOriginalName());

            if ($revisionFileName !== $upload->filename) {
                $folderIdTujuan = $driveConfig->folder_id;
                if ($upload->subfolder !== '(folder utama)') {
                    $subfolders = $driveService->listSubfolders($driveConfig->folder_id);
                    foreach ($subfolders as $sf) {
                        if ($sf->getName() === $upload->subfolder) {
                            $folderIdTujuan = $sf->getId();
                            break;
                        }
                    }
                }
                $existing = $driveService->checkFileExists($folderIdTujuan, $revisionFileName);
                $existing = array_filter($existing, fn($f) => $f->getId() !== $upload->drive_file_id);
                if (!empty($existing)) {
                    return back()->with('error', 'Nama file sudah dipakai file lain di folder tersebut.');
                }
            }

            $driveService->updateFile(
                $upload->drive_file_id,
                file_get_contents($revisionFile->getPathname()),
                $revisionFileName
            );

            if (!$upload->tanggal_kirim_awal) {
                $upload->tanggal_kirim_awal = $upload->upload_time;
            }

            $upload->revisionHistories()->create([
                'catatan_admin'       => $upload->catatan_admin,
                'nama_file_sebelumnya' => $upload->filename,
                'tanggal'             => now()->format('d-m-Y H:i'),
            ]);

            $upload->filename = $revisionFileName;
            $upload->status = 'pending';
            $upload->catatan_admin = [];
            $upload->notifikasi_pdf = '';
            $upload->upload_time = now()->format('d-m-Y H:i');
            $upload->save();

            return back()->with('success', 'Berkas revisi berhasil diupload dan menunggu review Admin.');
        } catch (\Exception $e) {
            Log::error('Upload revisi failed', ['user' => $user->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Gagal upload revisi. Silakan coba lagi.');
        }
    }

    public function getSubfolders()
    {
        $driveConfig = DriveConfig::first();
        if (!$driveConfig || !$driveConfig->folder_id) {
            return response()->json([]);
        }

        $adminToken = DriveToken::first();
        if (!$adminToken) {
            return response()->json([]);
        }

        try {
            $driveService = new GoogleDriveService($adminToken);
            $subfolders = $driveService->listSubfolders($driveConfig->folder_id);
            $result = [['id' => '(folder utama)', 'name' => '(folder utama)']];
            foreach ($subfolders as $sf) {
                $result[] = ['id' => $sf->getName(), 'name' => $sf->getName()];
            }
            return response()->json($result);
        } catch (\Exception $e) {
            return response()->json([]);
        }
    }

    public function scanFolder(Request $request)
    {
        $request->validate(['folder' => 'required|string']);

        $driveConfig = DriveConfig::first();
        if (!$driveConfig || !$driveConfig->folder_id) {
            return response()->json(['error' => 'Folder Drive belum dikonfigurasi'], 400);
        }

        $adminToken = DriveToken::first();
        if (!$adminToken) {
            return response()->json(['error' => 'Admin belum menghubungkan Google Drive'], 400);
        }

        try {
            $driveService = new GoogleDriveService($adminToken);

            if ($request->folder === '(folder utama)') {
                $files = $driveService->listFilesInRoot($driveConfig->folder_id, '(folder utama)');
            } else {
                $subfolders = $driveService->listSubfolders($driveConfig->folder_id);
                $targetId = null;
                foreach ($subfolders as $sf) {
                    if ($sf->getName() === $request->folder) {
                        $targetId = $sf->getId();
                        break;
                    }
                }
                if (!$targetId) {
                    return response()->json(['error' => 'Folder tidak ditemukan'], 404);
                }
                $files = $driveService->listFilesRecursive($targetId, $request->folder);
            }

            return response()->json($files);
        } catch (\Exception $e) {
            Log::error('Scan folder failed', ['user' => Auth::id(), 'error' => $e->getMessage()]);
            return response()->json(['error' => 'Gagal memindai folder. Silakan coba lagi.'], 500);
        }
    }
}
