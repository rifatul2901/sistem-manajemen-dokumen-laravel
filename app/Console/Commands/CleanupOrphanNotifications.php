<?php

namespace App\Console\Commands;

use App\Models\Upload;
use Illuminate\Console\Command;

class CleanupOrphanNotifications extends Command
{
    protected $signature = 'cleanup:orphan-notifications';
    protected $description = 'Hapus PDF notifikasi yang upload-nya sudah tidak revision atau sudah dihapus';

    public function handle()
    {
        $uploads = Upload::whereNotNull('notifikasi_pdf')->get();
        $deleted = 0;

        foreach ($uploads as $upload) {
            if ($upload->status !== 'revision') {
                if ($upload->notifikasi_pdf && file_exists($upload->notifikasi_pdf)) {
                    unlink($upload->notifikasi_pdf);
                    $upload->notifikasi_pdf = null;
                    $upload->save();
                    $deleted++;
                }
            }
        }

        $this->info("Deleted {$deleted} orphan notification PDFs.");
        return 0;
    }
}
