<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Upload extends Model
{
    protected $fillable = [
        'user_id', 'drive_file_id', 'filename', 'subfolder',
        'uploader_name', 'upload_time', 'status',
        'catatan_user', 'catatan_admin', 'notifikasi_pdf', 'tanggal_kirim_awal',
    ];

    protected function casts(): array
    {
        return [
            'catatan_admin' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function revisionHistories()
    {
        return $this->hasMany(RevisionHistory::class);
    }
}
