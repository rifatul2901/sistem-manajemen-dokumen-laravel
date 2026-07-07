<?php

namespace App\Http\Controllers;

use App\Models\RevisionHistory;
use App\Models\Upload;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class RiwayatRevisiController extends Controller
{
    public function index(Request $request)
    {
        $query = Upload::with(['user', 'revisionHistories'])
            ->whereIn('status', ['approved', 'revision', 'pending']);

        if ($request->filled('folder')) {
            $query->where('subfolder', $request->folder);
        }

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function ($q) use ($search) {
                $q->where('filename', 'like', "%{$search}%")
                  ->orWhereHas('user', function ($uq) use ($search) {
                      $uq->where('nama', 'like', "%{$search}%");
                  });
            });
        }

        $uploads = $query->orderBy('subfolder')
            ->orderBy('filename')
            ->paginate(50)
            ->withQueryString();

        $folders = Upload::whereIn('status', ['approved', 'revision', 'pending'])
            ->whereNotNull('subfolder')
            ->where('subfolder', '!=', '(folder utama)')
            ->select('subfolder')
            ->distinct()
            ->orderBy('subfolder')
            ->pluck('subfolder');

        return view('admin.riwayat-revisi.index', compact('uploads', 'folders'));
    }

    public function rekapIndex()
    {
        $users = User::where('role', 'user')->orderBy('nama')->get();
        return view('admin.rekapitulasi.index', compact('users'));
    }

    public function downloadRekap(User $user)
    {
        $riwayat = RevisionHistory::whereHas('upload', function ($q) use ($user) {
            $q->where('user_id', $user->id);
        })->with('upload')->orderBy('created_at', 'desc')->get();

        $uploads = $user->uploads()->orderBy('created_at', 'desc')->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekapitulasi');

        // Title
        $sheet->setCellValue('A1', 'REKAPITULASI CATATAN DOKUMEN');
        $sheet->mergeCells('A1:F1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setARGB('FF1F4E79');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'User: ' . $user->nama . ' (@' . $user->username . ')');
        $sheet->mergeCells('A2:F2');
        $sheet->getStyle('A2')->getFont()->setSize(12)->getColor()->setARGB('FF4472C4');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        // Header row
        $headerRow = 4;
        $headers = ['No', 'Nama Folder', 'Nama Dokumen', 'Status', 'Catatan', 'Tanggal'];
        foreach ($headers as $i => $h) {
            $col = chr(65 + $i);
            $sheet->setCellValue($col . $headerRow, $h);
        }

        $headerStyle = $sheet->getStyle('A' . $headerRow . ':F' . $headerRow);
        $headerStyle->getFont()->setBold(true)->setSize(11)->getColor()->setARGB('FFFFFFFF');
        $headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1F4E79');
        $headerStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $headerStyle->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

        // Data
        $row = 5;
        $no = 1;

        foreach ($uploads as $upload) {
            $statusLabel = match ($upload->status) {
                'approved' => 'Disetujui',
                'revision' => 'Butuh Revisi',
                'pending'  => 'Menunggu',
                default    => $upload->status,
            };

            $catatan = '';
            if ($upload->catatan_admin) {
                $notes = is_array($upload->catatan_admin) ? $upload->catatan_admin : json_decode($upload->catatan_admin, true) ?? [];
                $catatan = implode(PHP_EOL . '- ', $notes);
                if (count($notes) > 0) {
                    $catatan = '- ' . $catatan;
                }
            }

            $sheet->setCellValue('A' . $row, $no++);
            $sheet->setCellValue('B' . $row, $upload->subfolder ?? '(folder utama)');
            $sheet->setCellValue('C' . $row, $upload->filename);
            $sheet->setCellValue('D' . $row, $statusLabel);
            $sheet->setCellValue('E' . $row, $catatan);
            $sheet->setCellValue('F' . $row, $upload->created_at ? $upload->created_at->format('d/m/Y H:i') : '-');

            $dataStyle = $sheet->getStyle('A' . $row . ':F' . $row);
            $dataStyle->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            if ($upload->status === 'approved') {
                $dataStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE8F5E9');
            } elseif ($upload->status === 'revision') {
                $dataStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFEBEE');
            }
            $sheet->getStyle('E' . $row)->getAlignment()->setWrapText(true);
            $row++;

            foreach ($riwayat->where('upload_id', $upload->id) as $rev) {
                $revNotes = is_array($rev->catatan_admin) ? $rev->catatan_admin : json_decode($rev->catatan_admin, true) ?? [];
                $revText = '';
                if (count($revNotes) > 0) {
                    $revText = '- ' . implode(PHP_EOL . '- ', $revNotes);
                }

                $sheet->setCellValue('A' . $row, '');
                $sheet->setCellValue('B' . $row, '');
                $sheet->setCellValue('C' . $row, '  ↳ Revisi: ' . ($rev->nama_file_sebelumnya ?? ''));
                $sheet->setCellValue('D' . $row, 'Revisi');
                $sheet->setCellValue('E' . $row, $revText);
                $sheet->setCellValue('F' . $row, $rev->tanggal ?? ($rev->created_at?->format('d/m/Y H:i') ?? '-'));

                $revStyle = $sheet->getStyle('A' . $row . ':F' . $row);
                $revStyle->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $revStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF5F5F5');
                $sheet->getStyle('C' . $row)->getFont()->setItalic(true)->getColor()->setARGB('FF666666');
                $sheet->getStyle('E' . $row)->getAlignment()->setWrapText(true);
                $row++;
            }
        }

        foreach (range('A', 'F') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->getColumnDimension('E')->setWidth(50);

        $tempPath = storage_path('app/rekapitulasi_temp');
        if (!is_dir($tempPath)) {
            mkdir($tempPath, 0755, true);
        }

        $filename = 'Rekapitulasi_' . preg_replace('/[^a-zA-Z0-9]/', '_', $user->nama) . '_' . now()->format('Ymd_His') . '.xlsx';
        $filePath = $tempPath . '/' . $filename;

        $writer = new Xlsx($spreadsheet);
        $writer->save($filePath);

        return response()->download($filePath, $filename)->deleteFileAfterSend(true);
    }
}
