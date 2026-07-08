<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;

class CleanupTempFiles extends Command
{
    protected $signature = 'cleanup:temp-files';
    protected $description = 'Hapus file temporary > 7 hari dari drive_cache dan preview_temp';

    public function handle()
    {
        $dirs = [
            storage_path('app/drive_cache'),
            storage_path('app/preview_temp'),
        ];

        $cutoff = now()->subDays(7)->timestamp;
        $deleted = 0;

        foreach ($dirs as $dir) {
            if (!is_dir($dir)) {
                continue;
            }
            foreach (glob($dir . '/*') as $file) {
                if (is_file($file) && filemtime($file) < $cutoff) {
                    unlink($file);
                    $deleted++;
                }
            }
        }

        $this->info("Deleted {$deleted} temporary files.");
        return 0;
    }
}
