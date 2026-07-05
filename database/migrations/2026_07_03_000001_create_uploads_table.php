<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('uploads', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('drive_file_id')->nullable();
            $table->string('filename');
            $table->string('subfolder')->default('(folder utama)');
            $table->string('uploader_name');
            $table->string('upload_time');
            $table->string('status', 15)->default('pending');
            $table->text('catatan_user')->nullable();
            $table->json('catatan_admin')->nullable();
            $table->string('notifikasi_pdf')->nullable();
            $table->string('tanggal_kirim_awal')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('uploads');
    }
};
