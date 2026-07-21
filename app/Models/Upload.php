<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Upload extends Model
{
    protected $fillable = [
        'user_id', 'assigned_user_id', 'drive_file_id', 'filename', 'subfolder',
        'uploader_name', 'upload_time', 'status',
        'catatan_user', 'catatan_admin', 'notifikasi_pdf', 'tanggal_kirim_awal',
        'poin_pengukuran_kinerja', 'poin_dokumen_sumber', 'poin_rencana_tindak_lanjut',
        'poin_bukti_kegiatan', 'poin_bukti_media_kinerja',
    ];

    protected function casts(): array
    {
        return [
            'catatan_admin' => 'array',
            'approved_at' => 'datetime',
            'revision_requested_at' => 'datetime',
            'poin_pengukuran_kinerja' => 'boolean',
            'poin_dokumen_sumber' => 'boolean',
            'poin_rencana_tindak_lanjut' => 'boolean',
            'poin_bukti_kegiatan' => 'boolean',
            'poin_bukti_media_kinerja' => 'boolean',
        ];
    }

    public static function poinLabels(): array
    {
        return [
            'poin_pengukuran_kinerja'    => 'Kelengkapan Pengukuran Kinerja Triwulanan (Undangan, Daftar Hadir, Notula+Dokumentasi)',
            'poin_dokumen_sumber'        => 'Dokumen Sumber Triwulanan',
            'poin_rencana_tindak_lanjut' => 'Bukti Rencana Tindak Lanjut Triwulanan',
            'poin_bukti_kegiatan'        => 'Bukti Dukung Kegiatan Triwulanan',
            'poin_bukti_media_kinerja'   => 'Bukti Dukung Media Pengelolaan Kinerja dan Inovasi',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function assignedUser()
    {
        return $this->belongsTo(User::class, 'assigned_user_id');
    }

    public function revisionHistories()
    {
        return $this->hasMany(RevisionHistory::class);
    }
}
