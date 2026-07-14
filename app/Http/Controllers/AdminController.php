<?php

namespace App\Http\Controllers;

use App\Models\DriveConfig;
use App\Models\DriveToken;
use App\Models\Upload;
use App\Models\User;
use App\Models\DriveIgnored;
use App\Services\GoogleDriveService;
use App\Services\DocumentService;
use Google\Client;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class AdminController extends Controller
{
    public function index(Request $request)
    {
        $driveConfig = Cache::remember('drive_config', 300, fn() => DriveConfig::first());

        $config = [
            'folder_size' => Cache::remember('folder_size', 60, fn() => $this->getFolderSize(storage_path('app/hasil_generate'))),
        ];

        return view('admin.dashboard', compact('driveConfig', 'config'));
    }

    public function saveDriveConfig(Request $request)
    {
        $request->validate([
            'folder_link' => 'required|string|max:500',
        ]);

        $folderId = GoogleDriveService::extractFolderId($request->folder_link);
        if (!preg_match('/^[a-zA-Z0-9_-]+$/', $folderId) || strlen($folderId) < 10) {
            return back()->with('error', 'Link folder Google Drive tidak valid.');
        }

        $config = DriveConfig::first();
        if (!$config) {
            DriveConfig::create([
                'folder_id'   => $folderId,
                'folder_link' => strip_tags($request->folder_link),
            ]);
        } else {
            $config->update([
                'folder_id'   => $folderId,
                'folder_link' => strip_tags($request->folder_link),
            ]);
        }

        Cache::forget('drive_config');

        return back()->with('success', 'Folder Drive tim berhasil disimpan.');
    }

    public function getSubfolders()
    {
        $driveConfig = DriveConfig::first();
        if (!$driveConfig || !$driveConfig->folder_id) {
            return response()->json([]);
        }

        try {
            $driveService = new GoogleDriveService();
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
        $request->validate([
            'parent_id'   => 'nullable|string',
            'parent_name' => 'nullable|string',
            'scan_all'    => 'nullable|boolean',
        ]);

        $driveConfig = DriveConfig::first();
        if (!$driveConfig || !$driveConfig->folder_id) {
            return response()->json(['error' => 'Folder Drive belum dikonfigurasi'], 400);
        }

        try {
            $driveService = new GoogleDriveService();

            $folderId   = $request->parent_id ?: $driveConfig->folder_id;
            $folderName = $request->parent_name ?: '(folder utama)';

            $subfolders = $driveService->listSubfolders($folderId);

            if ($request->scan_all) {
                $files = $driveService->listFilesRecursive($folderId, $folderName);
            } else {
                $files = $driveService->listFilesInRoot($folderId, $folderName);
            }

            $uploads = Upload::with('revisionHistories', 'user', 'assignedUser')
                ->whereIn('drive_file_id', collect($files)->pluck('id'))
                ->get()
                ->keyBy('drive_file_id');
            $diabaikan = Cache::remember('drive_ignored', 60, fn() => DriveIgnored::pluck('file_id')->toArray());

            $gabungan = [];
            foreach ($files as $f) {
                if (in_array($f['id'], $diabaikan)) {
                    continue;
                }
                $meta = $uploads->get($f['id']);
                if ($meta) {
                    $meta->subfolder = $f['subfolder'];
                    $row = $meta->toArray();
                    $row['database_id'] = $meta->id;
                    $row['assigned_user_id'] = $meta->assigned_user_id;
                    $row['subfolder'] = $f['subfolder'];
                    $row['uploader_name'] = $meta->user?->nama ?? $meta->user?->username ?? '(tanpa nama)';
                    $row['revision_histories'] = $meta->revisionHistories->map(function ($r) {
                        return [
                            'tanggal' => ($r->tanggal ?? $r->created_at?->format('d/m/Y H:i')),
                            'catatan_admin' => $r->catatan_admin,
                        ];
                    });
                    $gabungan[] = $row;
                } else {
                    $upload = Upload::create([
                        'user_id'        => auth()->id(),
                        'drive_file_id'  => $f['id'],
                        'filename'       => $f['name'],
                        'subfolder'      => $f['subfolder'],
                        'uploader_name'  => 'Eksternal',
                        'upload_time'    => isset($f['createdTime']) ? substr(str_replace('T', ' ', $f['createdTime']), 0, 16) : now()->format('Y-m-d H:i'),
                        'status'         => 'pending',
                    ]);
                    $row = $upload->toArray();
                    $row['database_id'] = $upload->id;
                    $row['drive_file_id'] = $f['id'];
                    $row['subfolder'] = $f['subfolder'];
                    $row['uploader_name'] = 'Eksternal';
                    $row['revision_histories'] = [];
                    $gabungan[] = $row;
                }
            }

            $subfolderData = [];
            foreach ($subfolders as $sf) {
                $subfolderData[] = [
                    'id'   => $sf->getId(),
                    'name' => $sf->getName(),
                ];
            }

            return response()->json([
                'files'       => $gabungan,
                'subfolders'  => $subfolderData,
                'folder_id'   => $folderId,
                'folder_name' => $folderName,
                'waktu'       => now()->format('d-m-Y H:i'),
            ]);
        } catch (\Exception $e) {
            Log::error('Admin scan folder failed', ['error' => $e->getMessage(), 'trace' => $e->getTraceAsString()]);
            return response()->json(['error' => 'Gagal memindai folder. Silakan coba lagi.'], 500);
        }
    }

    public function approve($id)
    {
        $upload = Upload::findOrFail($id);

        if ($upload->notifikasi_pdf && file_exists($upload->notifikasi_pdf)) {
            unlink($upload->notifikasi_pdf);
        }

        $upload->status = 'approved';
        $upload->catatan_admin = [];
        $upload->notifikasi_pdf = null;
        $upload->approved_at = now();
        $upload->save();

        return back()->with('success', 'Berkas berhasil disetujui.');
    }

    public function requestRevision(Request $request, $id)
    {
        $request->validate([
            'catatan' => 'required|array|min:1|max:20',
            'catatan.*' => 'required|string|max:500',
            'user_id' => 'required|exists:users,id',
        ]);

        $upload = Upload::findOrFail($id);
        $upload->status = 'revision';
        $upload->catatan_admin = $request->catatan;
        $upload->assigned_user_id = $request->user_id;
        $upload->revision_requested_at = now();
        $upload->save();

        try {
            $notifFolder = storage_path('app/notifikasi_revisi');
            if (!is_dir($notifFolder)) {
                mkdir($notifFolder, 0755, true);
            }

            $pdfPath = $notifFolder . '/Revisi_' . $upload->filename . '_' . now()->format('His') . '.pdf';

            $assignedUser = User::find($request->user_id);
            $userNama = $assignedUser?->nama ?? '(tidak dikenal)';
            $adminNama = Auth::user()->nama;

            $docService = new DocumentService();
            $docService->createRevisionPdf(
                $upload->filename,
                $request->catatan,
                $pdfPath,
                $upload->subfolder ?? '(folder utama)',
                $userNama,
                $adminNama
            );

            $upload->notifikasi_pdf = $pdfPath;
            $upload->save();
        } catch (\Exception $e) {
            // PDF notifikasi gagal dibuat, tapi status tetap berubah
        }

        return back()->with('success', 'Status berkas diubah menjadi "Butuh Revisi".');
    }

    public function deleteUpload($id)
    {
        $upload = Upload::findOrFail($id);

        if ($upload->notifikasi_pdf && file_exists($upload->notifikasi_pdf)) {
            unlink($upload->notifikasi_pdf);
        }

        $upload->revisionHistories()->delete();
        $upload->delete();

        return back()->with('success', 'Berkas dihapus dari riwayat sistem.');
    }

    public function hideFile(Request $request)
    {
        $request->validate(['file_id' => 'required|string']);

        DriveIgnored::firstOrCreate(['file_id' => $request->file_id]);

        return back()->with('success', 'File disembunyikan dari dashboard.');
    }

    public function unhideFile($fileId)
    {
        DriveIgnored::where('file_id', $fileId)->delete();
        return back()->with('success', 'File dimunculkan kembali.');
    }

    public function driveConfig()
    {
        $driveConfig = DriveConfig::first();
        $driveToken = DriveToken::where('user_id', Auth::id())->first();
        $clientExists = file_exists(storage_path('app/oauth_client_secret.json'));

        if ($driveToken && $driveToken->refresh_token && $clientExists) {
            try {
                $googleClient = new Client();
                $googleClient->setAuthConfig(storage_path('app/oauth_client_secret.json'));
                $googleClient->setAccessToken([
                    'access_token'  => $driveToken->access_token,
                    'refresh_token' => $driveToken->refresh_token,
                    'token_type'    => $driveToken->token_type ?? 'Bearer',
                    'expires_in'    => $driveToken->expires_in ?? 3600,
                    'created'       => $driveToken->created_at?->timestamp ?? time(),
                ]);

                if ($googleClient->isAccessTokenExpired()) {
                    $newToken = $googleClient->fetchAccessTokenWithRefreshToken($driveToken->refresh_token);
                    $driveToken->update([
                        'access_token' => $newToken['access_token'],
                        'expires_in'   => $newToken['expires_in'] ?? null,
                        'expires_at'   => isset($newToken['expires_in'])
                            ? now()->addSeconds($newToken['expires_in'])
                            : null,
                    ]);
                    $driveToken->refresh();
                }
            } catch (\Exception $e) {
                Log::warning('Gagal refresh token Drive saat akses drive config', [
                    'error' => $e->getMessage(),
                ]);
            }
        }

        $config = [
            'folder_size' => $this->getFolderSize(storage_path('app/hasil_generate')),
        ];

        return view('admin.drive-config', compact('driveConfig', 'driveToken', 'clientExists', 'config'));
    }

    public function uploadOAuthClient(Request $request)
    {
        $request->validate([
            'client_file' => 'required|file|mimes:json|max:100',
        ]);

        $content = file_get_contents($request->file('client_file')->getRealPath());
        $parsed = json_decode($content, true);

        if (!$parsed || (!isset($parsed['web']['client_id']) && !isset($parsed['installed']['client_id']))) {
            return back()->with('error', 'File JSON tidak valid. Pastikan anda mendownload OAuth Client ID dari Google Cloud Console.');
        }

        $targetPath = storage_path('app/oauth_client_secret.json');
        file_put_contents($targetPath, $content, LOCK_EX);

        return back()->with('success', 'OAuth Client berhasil diupload.');
    }

    public function downloadDriveFile($id)
    {
        $upload = Upload::findOrFail($id);
        $safeFilename = static::sanitizeFilename($upload->filename);
        $cachePath = storage_path('app/drive_cache/' . $upload->drive_file_id . '__' . $safeFilename);

        if (!file_exists($cachePath)) {
            try {
                $driveService = new GoogleDriveService();
                if (!is_dir(storage_path('app/drive_cache'))) {
                    mkdir(storage_path('app/drive_cache'), 0755, true);
                }
                $driveService->downloadFile($upload->drive_file_id, $cachePath);
            } catch (\Exception $e) {
                Log::error('Download from Drive failed', ['file_id' => $upload->drive_file_id, 'error' => $e->getMessage()]);
                return back()->with('error', 'Gagal download dari Drive.');
            }
        }

        return response()->download($cachePath, $safeFilename);
    }

    public function previewDriveFile($id)
    {
        $upload = Upload::findOrFail($id);
        $safeFilename = static::sanitizeFilename($upload->filename);
        $cachePath = storage_path('app/drive_cache/' . $upload->drive_file_id . '__' . $safeFilename);

        if (!file_exists($cachePath)) {
            try {
                $driveService = new GoogleDriveService();
                if (!is_dir(storage_path('app/drive_cache'))) {
                    mkdir(storage_path('app/drive_cache'), 0755, true);
                }
                $driveService->downloadFile($upload->drive_file_id, $cachePath);
            } catch (\Exception $e) {
                Log::error('Preview download failed', ['file_id' => $upload->drive_file_id, 'error' => $e->getMessage()]);
                return response()->json(['error' => 'Gagal mengakses file.'], 500);
            }
        }

        $pdfPath = $cachePath;
        if (strtolower(pathinfo($cachePath, PATHINFO_EXTENSION)) === 'docx') {
            $previewFolder = storage_path('app/preview_temp');
            if (!is_dir($previewFolder)) {
                mkdir($previewFolder, 0755, true);
            }
            $pdfPath = $previewFolder . '/' . pathinfo($upload->filename, PATHINFO_FILENAME) . '.pdf';

            if (!file_exists($pdfPath) || filemtime($cachePath) > filemtime($pdfPath)) {
                try {
                    $docService = new DocumentService();
                    $docService->convertDocxToPdf($cachePath, $pdfPath);
                } catch (\Exception $e) {
                    Log::error('Preview convert failed', ['file_id' => $upload->drive_file_id, 'error' => $e->getMessage()]);
                    return response()->json(['error' => 'Gagal konversi ke PDF.'], 500);
                }
            }
        }

        if (!file_exists($pdfPath)) {
            return response()->json(['error' => 'File tidak ditemukan'], 404);
        }

        return response()->file($pdfPath);
    }

    protected function getFolderSize(string $path): string
    {
        if (!is_dir($path)) {
            return '0 B';
        }

        $total = 0;
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($path, \RecursiveDirectoryIterator::SKIP_DOTS)
        );
        $limit = 100000;
        $count = 0;
        foreach ($iterator as $file) {
            $total += $file->getSize();
            $count++;
            if ($count > $limit) {
                break;
            }
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;
        while ($total >= 1024 && $i < count($units) - 1) {
            $total /= 1024;
            $i++;
        }

        return round($total, 1) . ' ' . $units[$i];
    }
}
