<?php

namespace App\Http\Controllers;

use App\Models\DriveConfig;
use App\Models\DriveToken;
use App\Models\RevisionHistory;
use App\Models\Upload;
use App\Services\GoogleDriveService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class DashboardController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->isAdmin()) {
            return redirect()->route('admin.dashboard');
        }

        $visibleToUser = function ($query) use ($user) {
            $query->where('user_id', $user->id)
                ->orWhere('assigned_user_id', $user->id);
        };

        $stats = Upload::query()
            ->where($visibleToUser)
            ->selectRaw("
                COUNT(*) as total,
                SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
                SUM(CASE WHEN status = 'revision' THEN 1 ELSE 0 END) as revision,
                SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as approved
            ")
            ->first();

        $totalBerkas = (int) ($stats->total ?? 0);
        $totalMenunggu = (int) ($stats->pending ?? 0);
        $totalRevisiBerkas = (int) ($stats->revision ?? 0);
        $totalDisetujui = (int) ($stats->approved ?? 0);

        $totalRevisi = RevisionHistory::query()
            ->whereHas('upload', $visibleToUser)
            ->count();

        $perluTindakan = Upload::query()
            ->where($visibleToUser)
            ->where('status', 'revision')
            ->withCount('revisionHistories')
            ->orderByDesc('revision_requested_at')
            ->orderByDesc('updated_at')
            ->limit(4)
            ->get();

        $activities = $this->recentActivities($visibleToUser, 5);

        return view('dashboard.index', compact(
            'user',
            'totalBerkas',
            'totalMenunggu',
            'totalRevisiBerkas',
            'totalDisetujui',
            'totalRevisi',
            'perluTindakan',
            'activities'
        ));
    }

    private function recentActivities(\Closure $visibleToUser, int $limit = 5): Collection
    {
        $columns = ['id', 'filename', 'subfolder', 'created_at', 'approved_at', 'revision_requested_at', 'catatan_admin'];

        $submitted = Upload::query()
            ->where($visibleToUser)
            ->select($columns)
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        $approved = Upload::query()
            ->where($visibleToUser)
            ->whereNotNull('approved_at')
            ->select($columns)
            ->orderByDesc('approved_at')
            ->limit($limit)
            ->get();

        $revisionRequests = Upload::query()
            ->where($visibleToUser)
            ->whereNotNull('revision_requested_at')
            ->select($columns)
            ->orderByDesc('revision_requested_at')
            ->limit($limit)
            ->get();

        $revisions = RevisionHistory::query()
            ->whereHas('upload', $visibleToUser)
            ->with(['upload:id,filename'])
            ->orderByDesc('created_at')
            ->limit($limit)
            ->get();

        $events = collect();

        foreach ($submitted as $upload) {
            if ($upload->created_at) {
                $events->push($this->activity(
                    'submitted',
                    'Berkas dikirim',
                    'bi-cloud-arrow-up',
                    'blue',
                    $upload->created_at,
                    $upload->filename,
                    $upload->id
                ));
            }
        }

        foreach ($revisions as $revision) {
            if ($revision->created_at) {
                $events->push($this->activity(
                    'revision_sent',
                    'Revisi dikirim',
                    'bi-arrow-repeat',
                    'blue',
                    $revision->created_at,
                    $revision->upload?->filename,
                    $revision->upload_id,
                    $revision->submitted_by ? 'Oleh '.$revision->submitted_by : null
                ));
            }
        }

        foreach ($revisionRequests as $upload) {
            if ($upload->revision_requested_at) {
                $events->push($this->activity(
                    'revision_requested',
                    'Permintaan revisi',
                    'bi-exclamation-triangle',
                    'red',
                    $upload->revision_requested_at,
                    $upload->filename,
                    $upload->id,
                    $this->firstNote($upload->catatan_admin)
                ));
            }
        }

        foreach ($approved as $upload) {
            if ($upload->approved_at) {
                $events->push($this->activity(
                    'approved',
                    'Berkas disetujui',
                    'bi-check-circle',
                    'green',
                    $upload->approved_at,
                    $upload->filename,
                    $upload->id
                ));
            }
        }

        return $events
            ->filter(fn ($event) => $event['timestamp'] instanceof Carbon)
            ->sortByDesc(fn ($event) => $event['timestamp'])
            ->take($limit)
            ->values()
            ->map(function ($event) {
                $time = $event['timestamp']->copy()->locale('id');

                $event['time_label'] = $time->gt(now()->subDays(7))
                    ? $time->diffForHumans()
                    : $time->isoFormat('D MMM YYYY, HH:mm');
                $event['time_full'] = $time->isoFormat('dddd, D MMMM YYYY HH:mm');

                return $event;
            });
    }

    private function activity(
        string $type,
        string $label,
        string $icon,
        string $tone,
        Carbon $timestamp,
        ?string $filename,
        ?int $uploadId,
        ?string $note = null
    ): array {
        return [
            'type' => $type,
            'label' => $label,
            'icon' => $icon,
            'tone' => $tone,
            'timestamp' => $timestamp,
            'filename' => $filename ?: 'Dokumen',
            'upload_id' => $uploadId,
            'note' => $note,
        ];
    }

    private function firstNote($notes): ?string
    {
        $items = is_array($notes) ? $notes : ($notes ? [$notes] : []);

        foreach ($items as $note) {
            if (is_string($note) && trim($note) !== '') {
                return Str::limit(trim($note), 110);
            }
        }

        return null;
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

            DB::transaction(function () use ($driveService, $upload, $revisionFile, $revisionFileName, $request, $user) {
                if (!$upload->tanggal_kirim_awal) {
                    $upload->tanggal_kirim_awal = $upload->upload_time;
                }

                $upload->revisionHistories()->create([
                    'catatan_admin'       => $upload->catatan_admin,
                    'nama_file_sebelumnya' => $upload->filename,
                    'submitted_by'        => $user->nama,
                    'tanggal'             => now()->format('d-m-Y H:i'),
                ]);

                $upload->filename = $revisionFileName;
                $upload->status = 'pending';
                $upload->catatan_admin = [];

                $oldNotif = $upload->notifikasi_pdf;
                if ($oldNotif && file_exists($oldNotif)) {
                    unlink($oldNotif);
                }
                $upload->notifikasi_pdf = '';

                $upload->upload_time = now()->format('d-m-Y H:i');
                $upload->catatan_user = strip_tags($request->catatan ?? '');
                $upload->save();

                $driveService->updateFile(
                    $upload->drive_file_id,
                    file_get_contents($revisionFile->getPathname()),
                    $revisionFileName
                );
            });

            return back()->with('success', 'Berkas revisi berhasil diupload dan menunggu review Admin.');
        } catch (\Exception $e) {
            Log::error('Upload revisi failed', ['user' => $user->id, 'error' => $e->getMessage()]);
            return back()->with('error', 'Gagal upload revisi. Silakan coba lagi.');
        }
    }

}
