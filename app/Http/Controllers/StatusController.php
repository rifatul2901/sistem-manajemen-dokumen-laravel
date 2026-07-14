<?php

namespace App\Http\Controllers;

use App\Models\Upload;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;

class StatusController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $uploads = Upload::with(['revisionHistories' => function($q) {
                $q->orderBy('created_at');
            }])
            ->where(function($q) use ($user) {
                $q->where('user_id', $user->id)
                  ->orWhere('assigned_user_id', $user->id);
            })
            ->orderBy('created_at', 'desc')
            ->limit(100)
            ->get();

        $statusLabel = [
            'pending'  => 'Menunggu',
            'approved' => 'Disetujui',
            'revision' => 'Butuh Revisi',
        ];

        $kejadianPerDokumen = [];
        foreach ($uploads as $item) {
            $riwayat = $item->revisionHistories;
            $n = $riwayat->count();
            $label = $statusLabel[$item->status] ?? $item->status;

            $namaAwal = $n >= 1 ? ($riwayat[0]->nama_file_sebelumnya ?: $item->filename) : $item->filename;
            $tanggalAwal = $item->tanggal_kirim_awal ?: $item->upload_time;

            $kejadian = [];
            $kejadian[] = ['jenis' => 'Pengiriman Awal', 'nama' => $namaAwal, 'tanggal' => $tanggalAwal];

            foreach ($riwayat as $i => $r) {
                $namaHasil = ($i + 1 < $n) ? ($riwayat[$i + 1]->nama_file_sebelumnya ?: $item->filename) : $item->filename;
                $kejadian[] = ['jenis' => 'Revisi ke-' . ($i + 1), 'nama' => $namaHasil, 'tanggal' => $r->tanggal];
            }

            $tanggalTerbaru = collect($kejadian)->pluck('tanggal')->map(function ($t) {
                try {
                    return \Carbon\Carbon::createFromFormat('d-m-Y H:i', $t);
                } catch (\Exception $e) {
                    return new \Carbon\Carbon('1000-01-01');
                }
            })->max();

            $kejadianPerDokumen[] = [
                'induk'          => $namaAwal,
                'statusLabel'    => $label,
                'kejadian'       => $kejadian,
                'tanggalTerbaru' => $tanggalTerbaru,
                'status'         => $item->status,
                'subfolder'      => $item->subfolder ?? '(folder utama)',
                'uploadId'       => $item->id,
            ];
        }

        $urutanStatus = ['revision' => 0, 'pending' => 1, 'approved' => 2];

        usort($kejadianPerDokumen, function ($a, $b) use ($urutanStatus) {
            $aPrio = $urutanStatus[$a['status']] ?? 99;
            $bPrio = $urutanStatus[$b['status']] ?? 99;
            if ($aPrio !== $bPrio) {
                return $aPrio <=> $bPrio;
            }
            return $b['tanggalTerbaru'] <=> $a['tanggalTerbaru'];
        });

        return view('status.index', compact('uploads', 'kejadianPerDokumen', 'statusLabel'));
    }

    public function downloadNotif($id)
    {
        $upload = Upload::findOrFail($id);

        if ($upload->user_id !== Auth::id() && $upload->assigned_user_id !== Auth::id()) {
            abort(403);
        }

        if (!$upload->notifikasi_pdf || !file_exists($upload->notifikasi_pdf)) {
            return back()->with('error', 'File notifikasi tidak tersedia.');
        }

        return response()->download($upload->notifikasi_pdf, basename($upload->notifikasi_pdf));
    }
}
