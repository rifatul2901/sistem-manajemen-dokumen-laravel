<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use App\Models\User;
use Illuminate\Http\Request;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Style\Alignment;
use PhpOffice\PhpSpreadsheet\Style\Border;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;

class RiwayatRevisiController extends Controller
{
    public function index(Request $request)
    {
        $query = Upload::with('user')
            ->where(function ($q) {
                $q->whereNotNull('approved_at')
                  ->orWhereNotNull('revision_requested_at');
            });

        $currentActivity = $request->activity;

        if ($request->filled('user_id')) {
            $query->where(function ($q) use ($request) {
                $q->where('user_id', $request->user_id)
                  ->orWhere('assigned_user_id', $request->user_id);
            });
        }

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

        $uploads = $query->orderBy('created_at', 'desc')->paginate(15)->withQueryString();

        $activities = collect();

        foreach ($uploads as $u) {
            if ($u->revision_requested_at) {
                $activities->push([
                    'type'      => 'minta_revisi',
                    'timestamp' => $u->revision_requested_at,
                    'filename'  => $u->filename,
                    'subfolder' => $u->subfolder ?? '(folder utama)',
                    'pelaku'    => 'Admin',
                    'assigned_nama' => $u->assignedUser?->nama,
                    'catatan'   => $u->catatan_admin,
                    'upload_id' => $u->id,
                ]);
            }
            if ($u->approved_at) {
                $activities->push([
                    'type'      => 'disetujui',
                    'timestamp' => $u->approved_at,
                    'filename'  => $u->filename,
                    'subfolder' => $u->subfolder ?? '(folder utama)',
                    'pelaku'    => 'Admin',
                    'assigned_nama' => null,
                    'catatan'   => null,
                    'upload_id' => $u->id,
                ]);
            }
        }

        if ($request->filled('date_from')) {
            $from = \Carbon\Carbon::parse($request->date_from)->startOfDay();
            $activities = $activities->filter(fn($a) => \Carbon\Carbon::parse($a['timestamp'])->greaterThanOrEqualTo($from));
        }

        if ($request->filled('date_to')) {
            $to = \Carbon\Carbon::parse($request->date_to)->endOfDay();
            $activities = $activities->filter(fn($a) => \Carbon\Carbon::parse($a['timestamp'])->lessThanOrEqualTo($to));
        }

        $activities = $activities->sortByDesc('timestamp')->values();

        if ($currentActivity === 'disetujui') {
            $activities = $activities->filter(fn($a) => $a['type'] === 'disetujui');
        } elseif ($currentActivity === 'minta_revisi') {
            $activities = $activities->filter(fn($a) => $a['type'] === 'minta_revisi');
        }

        $users = User::where('role', 'user')->orderBy('nama')->get();

        $folders = Upload::where(function ($q) {
                $q->whereNotNull('approved_at')
                  ->orWhereNotNull('revision_requested_at');
            })
            ->whereNotNull('subfolder')
            ->where('subfolder', '!=', '(folder utama)')
            ->select('subfolder')
            ->distinct()
            ->orderBy('subfolder')
            ->pluck('subfolder');

        $folders = collect(['(folder utama)'])->merge($folders);

        return view('admin.riwayat-revisi.index', compact('activities', 'users', 'folders', 'uploads'));
    }

    public function rekapIndex()
    {
        $users = User::where('role', 'user')->orderBy('nama')->paginate(20)->withQueryString();

        $docCounts = Upload::selectRaw('user_id, assigned_user_id, COUNT(*) as total')
            ->where(function ($q) {
                $q->whereNotNull('user_id')
                  ->orWhereNotNull('assigned_user_id');
            })
            ->groupBy('user_id', 'assigned_user_id')
            ->get();

        $countMap = [];
        foreach ($docCounts as $row) {
            if ($row->user_id) {
                $countMap[$row->user_id] = ($countMap[$row->user_id] ?? 0) + $row->total;
            }
            if ($row->assigned_user_id && $row->assigned_user_id !== $row->user_id) {
                $countMap[$row->assigned_user_id] = ($countMap[$row->assigned_user_id] ?? 0) + $row->total;
            }
        }

        return view('admin.rekapitulasi.index', compact('users', 'countMap'));
    }

    public function downloadRekap(User $user)
    {
        $riwayat = \App\Models\RevisionHistory::whereHas('upload', function ($q) use ($user) {
            $q->where(function ($qq) use ($user) {
                $qq->where('user_id', $user->id)
                   ->orWhere('assigned_user_id', $user->id);
            });
        })->with('upload')->orderBy('created_at', 'desc')->get();

        $uploads = Upload::where(function ($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('assigned_user_id', $user->id);
            })
            ->orderBy('created_at', 'desc')
            ->get();

        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Rekapitulasi');

        $sheet->setCellValue('A1', 'REKAPITULASI CATATAN DOKUMEN');
        $sheet->mergeCells('A1:H1');
        $sheet->getStyle('A1')->getFont()->setBold(true)->setSize(16)->getColor()->setARGB('FF1F4E79');
        $sheet->getStyle('A1')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $sheet->setCellValue('A2', 'User: ' . $user->nama . ' (@' . $user->username . ')');
        $sheet->mergeCells('A2:H2');
        $sheet->getStyle('A2')->getFont()->setSize(12)->getColor()->setARGB('FF4472C4');
        $sheet->getStyle('A2')->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);

        $headerRow = 4;
        $headers = ['No', 'Nama Folder', 'Nama Dokumen', 'Status', 'Tanggal Upload', 'Tanggal Approve', 'Tanggal Minta Revisi', 'Catatan'];
        foreach ($headers as $i => $h) {
            $col = chr(65 + $i);
            $sheet->setCellValue($col . $headerRow, $h);
        }

        $headerStyle = $sheet->getStyle('A' . $headerRow . ':H' . $headerRow);
        $headerStyle->getFont()->setBold(true)->setSize(11)->getColor()->setARGB('FFFFFFFF');
        $headerStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FF1F4E79');
        $headerStyle->getAlignment()->setHorizontal(Alignment::HORIZONTAL_CENTER);
        $headerStyle->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);

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
            $sheet->setCellValue('E' . $row, $upload->created_at ? $upload->created_at->format('d/m/Y H:i') : '-');
            $sheet->setCellValue('F' . $row, $upload->approved_at ? $upload->approved_at->format('d/m/Y H:i') : '-');
            $sheet->setCellValue('G' . $row, $upload->revision_requested_at ? $upload->revision_requested_at->format('d/m/Y H:i') : '-');
            $sheet->setCellValue('H' . $row, $catatan);

            $dataStyle = $sheet->getStyle('A' . $row . ':H' . $row);
            $dataStyle->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
            if ($upload->status === 'approved') {
                $dataStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFE8F5E9');
            } elseif ($upload->status === 'revision') {
                $dataStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFFFEBEE');
            }
            $sheet->getStyle('H' . $row)->getAlignment()->setWrapText(true);
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
                $sheet->setCellValue('E' . $row, $rev->tanggal ?? ($rev->created_at?->format('d/m/Y H:i') ?? '-'));
                $sheet->setCellValue('F' . $row, '');
                $sheet->setCellValue('G' . $row, '');
                $sheet->setCellValue('H' . $row, $revText);

                $revStyle = $sheet->getStyle('A' . $row . ':H' . $row);
                $revStyle->getBorders()->getAllBorders()->setBorderStyle(Border::BORDER_THIN);
                $revStyle->getFill()->setFillType(Fill::FILL_SOLID)->getStartColor()->setARGB('FFF5F5F5');
                $sheet->getStyle('C' . $row)->getFont()->setItalic(true)->getColor()->setARGB('FF666666');
                $sheet->getStyle('H' . $row)->getAlignment()->setWrapText(true);
                $row++;
            }
        }

        foreach (range('A', 'H') as $col) {
            $sheet->getColumnDimension($col)->setAutoSize(true);
        }
        $sheet->getColumnDimension('H')->setWidth(50);

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
