<?php

namespace App\Http\Controllers;

use App\Models\DriveConfig;
use App\Models\DriveToken;
use App\Models\Upload;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class DriveController extends Controller
{
    public function index()
    {
        $driveConfig = Cache::remember('drive_config', 300, fn() => DriveConfig::first());
        return view('drive.index', compact('driveConfig'));
    }

    public function scan(Request $request)
    {
        $request->validate([
            'folder_link' => 'nullable|string',
        ]);

        $driveConfig = DriveConfig::first();

        if ($request->filled('folder_link')) {
            $folderId = GoogleDriveService::extractFolderId($request->folder_link);
            $label = 'folder lain yang kamu masukkan';
        } else {
            $folderId = $driveConfig?->folder_id;
            $label = 'folder Drive tim';
        }

        if (!$folderId) {
            return back()->with('error', 'Folder Drive belum dikonfigurasi. Isi link folder dulu.');
        }

        $adminToken = DriveToken::first();
        if (!$adminToken) {
            return back()->with('error', 'Admin belum menghubungkan Google Drive.');
        }

        try {
            $driveService = new GoogleDriveService($adminToken);
            $files = $driveService->listFilesRecursive($folderId, '(folder utama)');

            if (empty($files)) {
                return back()->with('info', 'Folder ini kosong atau belum ada file.');
            }

            $uploads = Upload::whereIn('drive_file_id', collect($files)->pluck('id'))->get()->keyBy('drive_file_id');
            $statusLabel = [
                'pending'  => 'Menunggu',
                'approved' => 'Disetujui',
                'revision' => 'Butuh Revisi',
            ];

            $rows = [];
            foreach ($files as $f) {
                $meta = $uploads->get($f['id']);
                $labelStatus = $meta ? ($statusLabel[$meta->status] ?? 'Tidak Tercatat') : 'Tidak Tercatat';
                $uploader = $meta ? $meta->uploader_name : '—';

                $tanggal = '-';
                if (!empty($f['createdTime'])) {
                    try {
                        $tanggal = \Carbon\Carbon::parse($f['createdTime'])->addHours(7)->format('d-m-Y H:i');
                    } catch (\Exception $e) {
                        $tanggal = '-';
                    }
                }

                $rows[] = [
                    'Nama File' => $f['name'],
                    'Subfolder' => $f['subfolder'],
                    'Diupload'  => $uploader,
                    'Tanggal'   => $tanggal,
                    'Status'    => $labelStatus,
                ];
            }

            return back()->with('scan_result', collect($rows))->with('scan_count', count($rows))->with('scan_label', $label);
        } catch (\Exception $e) {
            Log::error('Drive scan failed', ['error' => $e->getMessage()]);
            Log::error('Drive scan failed', ['error' => $e->getMessage()]);
            return back()->with('error', 'Gagal memindai Drive. Silakan coba lagi.');
        }
    }
}
