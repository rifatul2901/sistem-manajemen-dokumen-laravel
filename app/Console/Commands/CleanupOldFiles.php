<?php

namespace App\Console\Commands;

use App\Models\Upload;
use Illuminate\Console\Command;

class CleanupOldFiles extends Command
{
    protected $signature = 'cleanup:old-files';
    protected $description = 'Bersihkan file-file lama: notifikasi PDF, template, drive_cache, preview_temp';

    public function handle()
    {
        $deleted = 0;

        // 1. Hapus PDF notifikasi yang tidak tercantum di database
        $notifDir = storage_path('app/notifikasi_revisi');
        if (is_dir($notifDir)) {
            $filesInDb = Upload::whereNotNull('notifikasi_pdf')
                ->pluck('notifikasi_pdf')
                ->map(fn($path) => realpath($path))
                ->filter()
                ->toArray();

            foreach (glob($notifDir . '/*.pdf') as $file) {
                if (!in_array(realpath($file), $filesInDb)) {
                    unlink($file);
                    $deleted++;
                }
            }
        }

        $now = now()->timestamp;

        // 2. Hapus file template > 1 hari (kecuali template fallback default)
        $templateDir = storage_path('app/templates');
        if (is_dir($templateDir)) {
            foreach (glob($templateDir . '/*') as $file) {
                if (basename($file) === 'template_multinama.docx') {
                    continue;
                }
                if (is_file($file) && filemtime($file) < $now - 86400) {
                    unlink($file);
                    $deleted++;
                }
            }
        }

        // 3. Hapus drive_cache > 7 hari
        $cacheDir = storage_path('app/drive_cache');
        if (is_dir($cacheDir)) {
            foreach (glob($cacheDir . '/*') as $file) {
                if (is_file($file) && filemtime($file) < $now - 604800) {
                    unlink($file);
                    $deleted++;
                }
            }
        }

        // 4. Hapus preview_temp > 1 hari
        $previewDir = storage_path('app/preview_temp');
        if (is_dir($previewDir)) {
            foreach (glob($previewDir . '/*') as $file) {
                if (is_file($file) && filemtime($file) < $now - 86400) {
                    unlink($file);
                    $deleted++;
                }
            }
        }

        $this->info("Cleaned up {$deleted} old files.");
        return 0;
    }
}
