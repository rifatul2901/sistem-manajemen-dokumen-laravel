<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('uploads', function (Blueprint $table) {
            $table->boolean('poin_pengukuran_kinerja')->default(false);
            $table->boolean('poin_dokumen_sumber')->default(false);
            $table->boolean('poin_rencana_tindak_lanjut')->default(false);
            $table->boolean('poin_bukti_kegiatan')->default(false);
            $table->boolean('poin_bukti_media_kinerja')->default(false);
        });
    }

    public function down(): void
    {
        Schema::table('uploads', function (Blueprint $table) {
            $table->dropColumn([
                'poin_pengukuran_kinerja',
                'poin_dokumen_sumber',
                'poin_rencana_tindak_lanjut',
                'poin_bukti_kegiatan',
                'poin_bukti_media_kinerja',
            ]);
        });
    }
};
