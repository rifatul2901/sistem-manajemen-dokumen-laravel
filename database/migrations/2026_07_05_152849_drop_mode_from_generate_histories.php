<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('generate_histories', function (Blueprint $table) {
            $table->dropColumn('mode');
            $table->string('waktu')->nullable()->change();
        });
    }

    public function down(): void
    {
        Schema::table('generate_histories', function (Blueprint $table) {
            $table->string('mode')->after('filepath');
            $table->string('waktu')->nullable(false)->change();
        });
    }
};
