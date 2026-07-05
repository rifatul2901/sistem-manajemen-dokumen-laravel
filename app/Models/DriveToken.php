<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriveToken extends Model
{
    protected $fillable = [
        'user_id', 'access_token', 'refresh_token',
        'token_type', 'expires_in', 'expires_at', 'drive_email',
    ];

    protected function casts(): array
    {
        return [
            'expires_at' => 'datetime',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }
}
