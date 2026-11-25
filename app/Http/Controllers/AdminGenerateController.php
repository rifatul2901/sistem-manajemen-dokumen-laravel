<?php

namespace App\Http\Controllers;

use App\Models\GenerateHistory;
use App\Services\DocumentService;
use App\Services\GoogleSheetsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use PhpOffice\PhpSpreadsheet\IOFactory;

class AdminGenerateController extends Controller
{
    protected DocumentService $docService;

    public function __construct(DocumentService $docService)
    {
        $this->docService = $docService;
    }

    public function index()
    {
        $history = GenerateHistory::with('user')
            ->where('user_id', auth()->id())
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('admin.generate.index', compact('history'));
    }

    public function fetchSpreadsheet(Request $request)
    {
        $request->validate([
            'url' => 'required|string|max:500',
        ]);

        try {
            $sheetsService = new GoogleSheetsService();
            $spreadsheetId = $sheetsService->extractSpreadsheetId($request->url);

            if (!$spreadsheetId) {
                return response()->json(['error' => 'Link Google Spreadsheet tidak valid.'], 400);
            }

            $sheetNames = $sheetsService->getSheetNames($spreadsheetId);
            $firstSheet = $sheetNames[0] ?? 'Sheet1';
            $allRows = $sheetsService->readRange($spreadsheetId, $firstSheet);

            if (empty($allRows) || count($allRows) < 1) {
                return response()->json(['error' => 'Spreadsheet kosong atau tidak dapat dibaca.'], 400);
            }

            // Pad rows to sheet's actual column count (API truncates trailing empty cells)
            $totalCols = $sheetsService->getSheetTotalColumns($spreadsheetId, $firstSheet);
            foreach ($allRows as $ri => $row) {
                $allRows[$ri] = array_pad($row, $totalCols, '');
            }

            // 1. Skip leading rows with < 2 non-empty cells (judul, empty rows)
            $headerIdx = null;
            foreach ($allRows as $i => $row) {
                $nonEmpty = count(array_filter($row, fn($v) => trim($v ?? '') !== ''));
                if ($nonEmpty >= 2) {
                    $headerIdx = $i;
                    break;
                }
            }

            if ($headerIdx === null) {
                return response()->json(['error' => 'Tidak ditemukan baris header yang valid.'], 400);
            }

            $headers = $allRows[$headerIdx];
            $dataRows = array_slice($allRows, $headerIdx + 1);

            // 2. Build flat column names: merge cells as Nama_1, Nama_2, etc.
            //    solo empty columns: Kolom_A, Kolom_B, etc.
            $columns = [];
            $columnIndices = [];
            $currentGroup = null;
            $currentGroupStart = null;
            $groupCounter = 0;

            foreach ($headers as $ci => $h) {
                $trimmed = trim($h ?? '');
                if ($trimmed !== '') {
                    $currentGroup = $trimmed;
                    $currentGroupStart = $ci;
                    $groupCounter = 1;
                    $columns[$ci] = $currentGroup;
                } else {
                    if ($currentGroup !== null) {
                        $groupCounter++;
                        if ($groupCounter === 2) {
                            $columns[$currentGroupStart] = $currentGroup . '_1';
                        }
                        $columns[$ci] = $currentGroup . '_' . $groupCounter;
                    } else {
                        $columns[$ci] = 'Kolom_' . chr(65 + $ci);
                    }
                }
                $columnIndices[] = $ci;
            }

            // 4. Filter columns where all data rows are empty (merge cell leftovers)
            $activeColumnIndices = [];
            foreach ($columnIndices as $ci) {
                $hasData = false;
                foreach ($dataRows as $row) {
                    if (isset($row[$ci]) && trim($row[$ci]) !== '') {
                        $hasData = true;
                        break;
                    }
                }
                if ($hasData) {
                    $activeColumnIndices[] = $ci;
                }
            }
            $columnIndices = $activeColumnIndices;
            $columns = array_intersect_key($columns, array_flip($columnIndices));

            // 3. Build table data using flat column keys
            $tableData = [];
            foreach ($dataRows as $ri => $row) {
                $entry = ['_row' => $headerIdx + $ri + 2];
                foreach ($columnIndices as $ci) {
                    $entry[$columns[$ci]] = isset($row[$ci]) ? trim($row[$ci]) : '';
                }
                $tableData[] = $entry;
            }

            $columns = array_values($columns);

            return response()->json([
                'columns' => $columns,
                'rows'    => $tableData,
            ]);
        } catch (\Exception $e) {
            Log::error('Fetch spreadsheet failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Gagal membaca spreadsheet. Pastikan spreadsheet sudah di-share ke email admin yang terhubung.'], 500);
        }
    }

    public function fetchExcel(Request $request)
    {
        $request->validate([
            'file' => 'required|file|mimes:xlsx,xls|max:10240',
        ]);

        try {
            $spreadsheet = IOFactory::load($request->file('file')->getRealPath());
            $worksheet = $spreadsheet->getActiveSheet();
            $allRows = $worksheet->toArray();

            if (empty($allRows) || count($allRows) < 1) {
                return response()->json(['error' => 'File Excel kosong.'], 400);
            }

            // 1. Skip leading rows with < 2 non-empty cells (judul, empty rows)
            $headerIdx = null;
            foreach ($allRows as $i => $row) {
                $nonEmpty = count(array_filter($row, fn($v) => trim($v ?? '') !== ''));
                if ($nonEmpty >= 2) {
                    $headerIdx = $i;
                    break;
                }
            }

            if ($headerIdx === null) {
                return response()->json(['error' => 'Tidak ditemukan baris header yang valid.'], 400);
            }

            $headers = $allRows[$headerIdx];
            $dataRows = array_slice($allRows, $headerIdx + 1);

            // 2. Build flat column names: merge cells as Nama_1, Nama_2, etc.
            //    solo empty columns: Kolom_A, Kolom_B, etc.
            $columns = [];
            $columnIndices = [];
            $currentGroup = null;
            $currentGroupStart = null;
            $groupCounter = 0;

            foreach ($headers as $ci => $h) {
                $trimmed = trim($h ?? '');
                if ($trimmed !== '') {
                    $currentGroup = $trimmed;
                    $currentGroupStart = $ci;
                    $groupCounter = 1;
                    $columns[$ci] = $currentGroup;
                } else {
                    if ($currentGroup !== null) {
                        $groupCounter++;
                        if ($groupCounter === 2) {
                            $columns[$currentGroupStart] = $currentGroup . '_1';
                        }
                        $columns[$ci] = $currentGroup . '_' . $groupCounter;
                    } else {
                        $columns[$ci] = 'Kolom_' . chr(65 + $ci);
                    }
                }
                $columnIndices[] = $ci;
            }

            // 4. Filter columns where all data rows are empty (merge cell leftovers)
            $activeColumnIndices = [];
            foreach ($columnIndices as $ci) {
                $hasData = false;
                foreach ($dataRows as $row) {
                    if (isset($row[$ci]) && trim((string)$row[$ci]) !== '') {
                        $hasData = true;
                        break;
                    }
                }
                if ($hasData) {
                    $activeColumnIndices[] = $ci;
                }
            }
            $columnIndices = $activeColumnIndices;
            $columns = array_intersect_key($columns, array_flip($columnIndices));

            // 3. Build table data using flat column keys
            $tableData = [];
            foreach ($dataRows as $ri => $row) {
                $entry = ['_row' => $headerIdx + $ri + 2];
                foreach ($columnIndices as $ci) {
                    $entry[$columns[$ci]] = isset($row[$ci]) ? trim((string)$row[$ci]) : '';
                }
                $tableData[] = $entry;
            }

            $columns = array_values($columns);

            return response()->json([
                'columns' => $columns,
                'rows'    => $tableData,
            ]);
        } catch (\Exception $e) {
            Log::error('Fetch Excel failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Gagal membaca file Excel.'], 500);
        }
    }

    public function generate(Request $request)
    {
        $request->validate([
            'selected_data'  => 'required|json',
            'template'       => 'required|file|mimes:docx|max:10240',
            'format'         => 'required|in:docx,pdf',
            'document_name'  => 'nullable|string|max:200',
        ]);

        try {
            $selectedData = json_decode($request->selected_data, true);
            if (!is_array($selectedData) || empty($selectedData)) {
                return $request->expectsJson()
                    ? response()->json(['error' => 'Pilih minimal 1 data terlebih dahulu.'], 400)
                    : back()->with('error', 'Pilih minimal 1 data terlebih dahulu.');
            }

            $templatePath = $request->file('template')->store('templates', 'local');
            $fullTemplatePath = storage_path('app/' . $templatePath);

            $outputDir = storage_path('app/hasil_generate');
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            $customName = $request->input('document_name');
            if ($customName) {
                $baseName = preg_replace('/[^a-zA-Z0-9\s\-_\.]/', '', $customName);
            } else {
                $baseName = pathinfo($request->file('template')->getClientOriginalName(), PATHINFO_FILENAME);
            }
            $baseName = $baseName ?: 'Dokumen';
            $uniqueSuffix = now()->format('Ymd_His') . '_' . uniqid();
            $filename = $baseName . '_' . $uniqueSuffix;
            $outputPath = $outputDir . '/' . $filename . '.docx';

            $extraContext = [
                'Judul_Project' => 'Dokumen Triwulanan',
                'Tanggal_Cetak' => now()->format('d F Y'),
            ];

            $this->docService->generateFromData($fullTemplatePath, $selectedData, $extraContext, $outputPath);

            Storage::disk('local')->delete($templatePath);

            if ($request->format === 'pdf') {
                $pdfPath = $outputDir . '/' . $filename . '.pdf';
                $this->docService->convertDocxToPdf($outputPath, $pdfPath);
                unlink($outputPath);

                $finalPath = $pdfPath;
                $finalName = $baseName . '.pdf';
            } else {
                $finalPath = $outputPath;
                $finalName = $baseName . '.docx';
            }

            $history = GenerateHistory::create([
                'user_id'  => auth()->id(),
                'filename' => $finalName,
                'filepath' => $finalPath,
                'waktu'    => now(),
            ]);

            return response()->json([
                'success'    => true,
                'history_id' => $history->id,
                'filename'   => $finalName,
                'waktu'      => \Carbon\Carbon::parse($history->waktu)->format('d M Y H:i'),
            ]);
        } catch (\Exception $e) {
            foreach ([$outputPath ?? null, $pdfPath ?? null] as $path) {
                if ($path && file_exists($path)) {
                    unlink($path);
                }
            }
            if (isset($templatePath)) {
                Storage::disk('local')->delete($templatePath);
            }
            Log::error('Admin generate failed', ['error' => $e->getMessage()]);
            if ($request->expectsJson()) {
                return response()->json(['error' => 'Gagal generate dokumen. Silakan coba lagi atau hubungi admin.'], 500);
            }
            return back()->with('error', 'Gagal generate dokumen. Silakan coba lagi atau hubungi admin.');
        }
    }

    public function detectTags(Request $request)
    {
        $request->validate([
            'template' => 'required|file|mimes:docx|max:10240',
            'columns'  => 'nullable|json',
        ]);

        $columns = json_decode($request->input('columns', '[]'), true);
        if (!is_array($columns)) {
            $columns = [];
        }
        $columns = array_values(array_filter(array_map(
            fn($c) => is_scalar($c) ? trim((string) $c) : '',
            $columns
        )));

        $templatePath = $request->file('template')->store('templates', 'local');
        try {
            $tags = $this->docService->detectTags(storage_path('app/' . $templatePath), $columns);
            $systemKeys = ['judul_project', 'tanggal_cetak'];
            $tags = array_values(array_filter($tags, function ($t) use ($systemKeys) {
                return !in_array($t['key'], $systemKeys, true);
            }));
            return response()->json(['success' => true, 'tags' => $tags]);
        } finally {
            Storage::disk('local')->delete($templatePath);
        }
    }

    public function generateBulk(Request $request)
    {
        $request->validate([
            'rows'            => 'required|json',
            'template'        => 'required|file|mimes:docx|max:10240',
            'filename_column' => 'required|string|max:200',
            'mapping'         => 'nullable|json',
        ]);

        $templatePath = null;
        $batchDir = null;
        $zipPath = null;

        try {
            $rows = json_decode($request->input('rows'), true);
            if (!is_array($rows) || empty($rows)) {
                return response()->json(['error' => 'Tidak ada baris data untuk di-generate.'], 400);
            }
            if (count($rows) > 50) {
                return response()->json(['error' => 'Maksimal 50 baris per sekali generate massal.'], 422);
            }

            $templatePath = $request->file('template')->store('templates', 'local');
            $fullTemplatePath = storage_path('app/' . $templatePath);

            $outputDir = storage_path('app/hasil_generate');
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            $templateBase = pathinfo($request->file('template')->getClientOriginalName(), PATHINFO_FILENAME);
            $templateBase = preg_replace('/[^a-zA-Z0-9\s\-_\.]/', '', $templateBase);
            $templateBase = trim($templateBase) !== '' ? trim($templateBase) : 'Dokumen';
            $downloadName = $templateBase . '_' . now()->format('Ymd') . '.zip';

            $batchDir = $outputDir . '/bulk_' . now()->format('Ymd_His') . '_' . uniqid();
            mkdir($batchDir, 0755, true);

            $extraContext = [
                'Judul_Project' => 'Dokumen Triwulanan',
                'Tanggal_Cetak' => now()->format('d F Y'),
            ];

            $tagMapping = [];
            $rawMapping = json_decode($request->input('mapping', '{}'), true);
            if (is_array($rawMapping)) {
                foreach ($rawMapping as $k => $v) {
                    if (is_string($k) && is_string($v) && $v !== '' && strlen($v) <= 200) {
                        $tagMapping[strtolower(preg_replace('/\s+/', '', trim($k)))] = $v;
                    }
                }
            }

            $filenameCol = $request->input('filename_column');
            $usedNames = [];
            $made = [];
            $skipped = 0;

            foreach ($rows as $i => $row) {
                if (!is_array($row)) {
                    $skipped++;
                    continue;
                }
                $hasValue = false;
                foreach ($row as $v) {
                    if (is_scalar($v) && trim((string) $v) !== '') {
                        $hasValue = true;
                        break;
                    }
                }
                if (!$hasValue) {
                    $skipped++;
                    continue;
                }

                $rawName = null;
                if (array_key_exists($filenameCol, $row)) {
                    $rawName = $row[$filenameCol];
                } else {
                    $normWanted = strtolower(preg_replace('/\s+/', '', trim($filenameCol)));
                    foreach ($row as $col => $v) {
                        if (strtolower(preg_replace('/\s+/', '', trim((string) $col))) === $normWanted) {
                            $rawName = $v;
                            break;
                        }
                    }
                }
                $safe = is_scalar($rawName) ? preg_replace('/[^a-zA-Z0-9\s\-_\.]/', '', (string) $rawName) : '';
                $safe = trim(trim($safe), '.');
                $safe = $safe !== '' ? $safe : 'Dokumen_' . ($i + 1);

                $candidate = $safe;
                $n = 2;
                while (isset($usedNames[$candidate])) {
                    $candidate = $safe . '-' . $n++;
                }
                $usedNames[$candidate] = true;

                $outPath = $batchDir . '/' . $candidate . '.docx';
                $this->docService->generateFromRow($fullTemplatePath, $row, $extraContext, $outPath, $tagMapping);
                $made[] = $candidate . '.docx';
            }

            if (empty($made)) {
                $this->rrmdirSafe($batchDir);
                Storage::disk('local')->delete($templatePath);
                return response()->json(['error' => 'Semua baris kosong, tidak ada dokumen dibuat.'], 400);
            }

            $zipName = $templateBase . '_' . now()->format('Ymd_His') . '_' . uniqid() . '.zip';
            $zipPath = $outputDir . '/' . $zipName;
            $zip = new \ZipArchive();
            if ($zip->open($zipPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
                throw new \RuntimeException('Gagal membuat file ZIP.');
            }
            foreach ($made as $f) {
                $zip->addFile($batchDir . '/' . $f, $f);
            }
            $zip->close();

            $this->rrmdirSafe($batchDir);
            Storage::disk('local')->delete($templatePath);

            $history = GenerateHistory::create([
                'user_id'  => auth()->id(),
                'filename' => $downloadName,
                'filepath' => $zipPath,
                'waktu'    => now(),
            ]);

            return response()->json([
                'success'    => true,
                'history_id' => $history->id,
                'filename'   => $downloadName,
                'waktu'      => \Carbon\Carbon::parse($history->waktu)->format('d M Y H:i'),
                'count'      => count($made),
                'skipped'    => $skipped,
            ]);
        } catch (\Exception $e) {
            if ($zipPath && file_exists($zipPath)) {
                unlink($zipPath);
            }
            if ($batchDir) {
                $this->rrmdirSafe($batchDir);
            }
            if ($templatePath) {
                Storage::disk('local')->delete($templatePath);
            }
            Log::error('Admin generate bulk failed', ['error' => $e->getMessage()]);
            return response()->json(['error' => 'Gagal generate massal. Silakan coba lagi atau hubungi admin.'], 500);
        }
    }

    protected function rrmdirSafe(?string $dir): void
    {
        if (!$dir || !is_dir($dir)) {
            return;
        }
        $files = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($files as $file) {
            if ($file->isDir()) {
                rmdir($file->getPathname());
            } else {
                unlink($file->getPathname());
            }
        }
        rmdir($dir);
    }

    public function downloadHistory($id)
    {
        $history = GenerateHistory::findOrFail($id);

        if ($history->user_id !== auth()->id()) {
            abort(403);
        }

        if (!file_exists($history->filepath)) {
            return back()->with('error', 'File sudah tidak tersedia.');
        }

        return response()->download($history->filepath, $history->filename);
    }

    public function deleteSelected(Request $request)
    {
        $request->validate([
            'ids'     => 'required|array|min:1',
            'ids.*'   => 'integer',
        ]);

        $histories = GenerateHistory::where('user_id', auth()->id())
            ->whereIn('id', $request->ids)
            ->get();

        $deleted = 0;
        DB::transaction(function () use ($histories, &$deleted) {
            foreach ($histories as $h) {
                if ($h->filepath && str_starts_with($h->filepath, storage_path()) && file_exists($h->filepath)) {
                    unlink($h->filepath);
                }
                $h->delete();
                $deleted++;
            }
        });

        return response()->json(['success' => true, 'deleted' => $deleted]);
    }
}
