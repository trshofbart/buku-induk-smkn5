<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dokumen_siswa', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->enum('jenis', ['akta', 'kk', 'ijazah', 'foto', 'lain']);
            $table->string('nama_file');
            $table->string('path');
            $table->timestamps();
            $table->index(['siswa_id', 'jenis']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dokumen_siswa');
    }
};
