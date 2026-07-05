<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Indexes for uploads
        Schema::table('uploads', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('status');
            $table->index('drive_file_id');
            $table->index(['user_id', 'status']);
        });

        // Indexes for generate_histories
        Schema::table('generate_histories', function (Blueprint $table) {
            $table->index('user_id');
            $table->index('created_at');
        });

        // Indexes for revision_histories
        Schema::table('revision_histories', function (Blueprint $table) {
            $table->index('upload_id');
        });

        // Indexes for drive_ignored
        Schema::table('drive_ignored', function (Blueprint $table) {
            $table->index('file_id');
        });

        // Indexes for drive_tokens
        Schema::table('drive_tokens', function (Blueprint $table) {
            $table->index('drive_email');
        });
    }

    public function down(): void
    {
        Schema::table('uploads', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['drive_file_id']);
            $table->dropIndex(['user_id', 'status']);
        });

        Schema::table('generate_histories', function (Blueprint $table) {
            $table->dropIndex(['user_id']);
            $table->dropIndex(['created_at']);
        });

        Schema::table('revision_histories', function (Blueprint $table) {
            $table->dropIndex(['upload_id']);
        });

        Schema::table('drive_ignored', function (Blueprint $table) {
            $table->dropIndex(['file_id']);
        });

        Schema::table('drive_tokens', function (Blueprint $table) {
            $table->dropIndex(['drive_email']);
        });
    }
};
