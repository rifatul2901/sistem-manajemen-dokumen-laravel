<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Hentikan migrasi dengan pesan jelas jika masih ada duplikat,
        // agar tidak meninggalkan skema setengah jadi.
        $duplikat = DB::table('uploads')
            ->select('drive_file_id')
            ->whereNotNull('drive_file_id')
            ->groupBy('drive_file_id')
            ->havingRaw('COUNT(*) > 1')
            ->count();

        if ($duplikat > 0) {
            throw new \RuntimeException(
                "Tidak dapat menambah unique index: ditemukan {$duplikat} drive_file_id duplikat. "
                . 'Bersihkan duplikat terlebih dahulu, lalu jalankan ulang migrasi.'
            );
        }

        Schema::table('uploads', function (Blueprint $table) {
            $table->dropIndex('uploads_drive_file_id_index');
        });

        // ID Google Drive bersifat case-sensitive. Collation biner membuat
        // dua ID yang hanya berbeda huruf besar/kecil tetap dianggap berbeda.
        DB::statement(
            'ALTER TABLE uploads MODIFY drive_file_id VARCHAR(255) CHARACTER SET utf8mb4 COLLATE utf8mb4_bin NULL DEFAULT NULL'
        );

        Schema::table('uploads', function (Blueprint $table) {
            $table->unique('drive_file_id', 'uploads_drive_file_id_unique');
        });
    }

    public function down(): void
    {
        Schema::table('uploads', function (Blueprint $table) {
            $table->dropUnique('uploads_drive_file_id_unique');
        });

        // Tanpa CHARACTER SET/COLLATE, MySQL memakai default tabel.
        DB::statement('ALTER TABLE uploads MODIFY drive_file_id VARCHAR(255) NULL DEFAULT NULL');

        Schema::table('uploads', function (Blueprint $table) {
            $table->index('drive_file_id');
        });
    }
};
