<?php

namespace App\Http\Controllers;

use App\Models\DriveConfig;
use App\Models\DriveToken;
use App\Models\Upload;
use App\Services\GoogleDriveService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class DashboardController extends Controller
{
    public function index()
    {
        return Auth::user()->isAdmin()
            ? redirect()->route('admin.dashboard')
            : redirect()->route('status.index');
    }

    public function uploadRevisi(Request $request)
    {
        $request->validate([
            'upload_id' => 'required|exists:uploads,id',
            'file' => 'required|file|mimes:docx,pdf|max:10240',
            'catatan' => 'nullable|string|max:2000',
        ]);

        $user = Auth::user();
        $upload = Upload::findOrFail($request->upload_id);

        if (($upload->user_id !== $user->id && $upload->assigned_user_id !== $user->id) || $upload->status !== 'revision') {
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
            $upload->catatan_user = strip_tags($request->catatan ?? '');
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

}
