<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DriveIgnored extends Model
{
    protected $fillable = ['file_id'];

    protected $table = 'drive_ignored';
}
