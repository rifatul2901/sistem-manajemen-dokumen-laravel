<?php

namespace App\Console\Commands;

use App\Models\Upload;
use App\Models\User;
use App\Models\RevisionHistory;
use Illuminate\Console\Command;

class ImportJsonData extends Command
{
    protected $signature = 'app:import-json-data {file? : Path to JSON file}';
    protected $description = 'Import data dari database_lokal.json ke MySQL';

    public function handle()
    {
        $jsonPath = $this->argument('file') ?? base_path('../database_lokal.json');

        if (!file_exists($jsonPath)) {
            $this->error("File JSON tidak ditemukan: $jsonPath");
            return 1;
        }

        $jsonContent = file_get_contents($jsonPath);
        if ($jsonContent === false) {
            $this->error("Gagal membaca file JSON.");
            return 1;
        }

        $json = json_decode($jsonContent, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->error('File JSON tidak valid: ' . json_last_error_msg());
            return 1;
        }
        $uploads = $json['uploads'] ?? [];

        if (empty($uploads)) {
            $this->warn('Tidak ada data uploads di JSON.');
            return 0;
        }

        $this->info("Ditemukan " . count($uploads) . " data upload.");

        // Buat user rifatul jika belum ada
        $userRifatul = User::where('username', 'rifatul')->first();
        if (!$userRifatul) {
            $userRifatul = User::create([
                'username' => 'rifatul',
                'nama'     => 'rifatul nabil',
                'email'    => 'rifatul@sistemdokumen.test',
                'password' => 'rifatul123',
                'role'     => 'user',
            ]);
            $this->info('User rifatul created.');
        }

        $userBudi = User::where('username', 'budi')->first();
        $userAdmin = User::where('username', 'admin')->first();

        $userMap = [
            'rifatul'       => $userRifatul->id,
            'rifatul nabil' => $userRifatul->id,
            'Budi Santoso'  => $userBudi?->id,
            'admin'         => $userAdmin?->id,
        ];

        $count = 0;
        foreach ($uploads as $item) {
            $uploaderName = $item['uploader'] ?? 'Unknown';
            $userId = $userMap[$uploaderName] ?? $userRifatul->id;

            // Cek duplikat berdasarkan drive_file_id atau filename+upload_time
            $existing = null;
            if (!empty($item['drive_file_id'])) {
                $existing = Upload::where('drive_file_id', $item['drive_file_id'])->first();
            }
            if (!$existing) {
                $existing = Upload::where('filename', $item['filename'])
                    ->where('upload_time', $item['upload_time'])
                    ->first();
            }
            if ($existing) {
                $this->warn("Skip (exists): {$item['filename']}");
                continue;
            }

            $catatanAdmin = $item['catatan_admin'] ?? [];
            if (is_string($catatanAdmin)) {
                $catatanAdmin = $catatanAdmin ? [$catatanAdmin] : [];
            }

            $upload = Upload::create([
                'user_id'            => $userId,
                'drive_file_id'      => $item['drive_file_id'] ?? null,
                'filename'           => $item['filename'],
                'subfolder'          => $item['subfolder'] ?? '(folder utama)',
                'uploader_name'      => $uploaderName,
                'upload_time'        => $item['upload_time'],
                'status'             => $item['status'] ?? 'pending',
                'catatan_user'       => $item['catatan_user'] ?? '',
                'catatan_admin'      => $catatanAdmin,
                'notifikasi_pdf'     => $item['notifikasi_pdf'] ?? '',
                'tanggal_kirim_awal' => $item['tanggal_kirim_awal'] ?? $item['upload_time'],
            ]);

            // Import revision histories
            foreach ($item['riwayat_revisi'] ?? [] as $rev) {
                $revCatatan = $rev['catatan_admin'] ?? [];
                if (is_string($revCatatan)) {
                    $revCatatan = $revCatatan ? [$revCatatan] : [];
                }

                $upload->revisionHistories()->create([
                    'catatan_admin'        => $revCatatan,
                    'nama_file_sebelumnya' => $rev['nama_file_sebelumnya'] ?? $item['filename'],
                    'tanggal'              => $rev['tanggal'] ?? $item['upload_time'],
                ]);
            }

            $count++;
            $this->info("Imported: {$item['filename']}");
        }

        $this->info("Selesai. $count data berhasil diimport.");
        return 0;
    }
}
