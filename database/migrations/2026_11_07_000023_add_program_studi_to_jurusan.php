<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /** Program Studi Keahlian per jurusan (untuk baris PROGRAM STUDI KEAHLIAN di cetakan). */
    public function up(): void
    {
        Schema::table('jurusan', function (Blueprint $table) {
            $table->string('program_studi', 100)->nullable()->after('nama');
        });
    }

    public function down(): void
    {
        Schema::table('jurusan', function (Blueprint $table) {
            $table->dropColumn('program_studi');
        });
    }
};
