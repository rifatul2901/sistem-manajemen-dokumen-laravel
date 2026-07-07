<?php

namespace App\Http\Controllers;

use App\Models\GenerateHistory;
use App\Services\DocumentService;
use App\Services\GoogleSheetsService;
use Illuminate\Http\Request;
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
            $data = $sheetsService->readRange($spreadsheetId, $firstSheet);

            if (empty($data) || count($data) < 1) {
                return response()->json(['error' => 'Spreadsheet kosong atau tidak dapat dibaca.'], 400);
            }

            $headers = $data[0];
            $rows = array_slice($data, 1);

            $columns = array_map('trim', $headers);

            $tableData = [];
            foreach ($rows as $ri => $row) {
                $entry = ['_row' => $ri + 2];
                foreach ($columns as $ci => $col) {
                    $entry[$col] = isset($row[$ci]) ? trim($row[$ci]) : '';
                }
                $tableData[] = $entry;
            }

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
            $data = $worksheet->toArray();

            if (empty($data) || count($data) < 1) {
                return response()->json(['error' => 'File Excel kosong.'], 400);
            }

            $headers = $data[0];
            $rows = array_slice($data, 1);

            $columns = array_map(fn($h) => trim($h ?? ''), $headers);

            $tableData = [];
            foreach ($rows as $ri => $row) {
                $entry = ['_row' => $ri + 2];
                foreach ($columns as $ci => $col) {
                    $entry[$col] = isset($row[$ci]) ? trim((string)$row[$ci]) : '';
                }
                $tableData[] = $entry;
            }

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
            'selected_data' => 'required|json',
            'template'      => 'required|file|mimes:docx|max:10240',
            'format'        => 'required|in:docx,pdf',
        ]);

        try {
            $selectedData = json_decode($request->selected_data, true);
            if (!is_array($selectedData) || empty($selectedData)) {
                return back()->with('error', 'Pilih minimal 1 data terlebih dahulu.');
            }

            $templatePath = $request->file('template')->store('templates', 'local');
            $fullTemplatePath = storage_path('app/' . $templatePath);

            $outputDir = storage_path('app/hasil_generate');
            if (!is_dir($outputDir)) {
                mkdir($outputDir, 0755, true);
            }

            $filename = 'Generate_' . now()->format('Ymd_His') . '_' . uniqid();
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

                GenerateHistory::create([
                    'user_id'  => auth()->id(),
                    'filename' => $filename . '.pdf',
                    'filepath' => $pdfPath,
                    'waktu'    => now(),
                ]);

                return response()->download($pdfPath, $filename . '.pdf')->deleteFileAfterSend(true);
            }

            GenerateHistory::create([
                'user_id'  => auth()->id(),
                'filename' => $filename . '.docx',
                'filepath' => $outputPath,
                'waktu'    => now(),
            ]);

            return response()->download($outputPath, $filename . '.docx')->deleteFileAfterSend(true);
        } catch (\Exception $e) {
            Log::error('Admin generate failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal generate dokumen: ' . $e->getMessage());
        }
    }
}
