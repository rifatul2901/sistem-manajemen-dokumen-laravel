<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RevisionHistory extends Model
{
    protected $fillable = [
        'upload_id', 'catatan_admin', 'nama_file_sebelumnya', 'submitted_by', 'tanggal',
    ];

    protected function casts(): array
    {
        return [
            'catatan_admin' => 'array',
        ];
    }

    public function upload()
    {
        return $this->belongsTo(Upload::class);
    }
}
