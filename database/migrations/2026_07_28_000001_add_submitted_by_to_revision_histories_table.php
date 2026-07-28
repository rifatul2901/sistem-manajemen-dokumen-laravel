<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('revision_histories', function (Blueprint $table) {
            $table->string('submitted_by')->nullable()->after('nama_file_sebelumnya');
        });
    }

    public function down(): void
    {
        Schema::table('revision_histories', function (Blueprint $table) {
            $table->dropColumn('submitted_by');
        });
    }
};
