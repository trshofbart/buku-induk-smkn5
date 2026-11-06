<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Daftar mata pelajaran mengikuti sheet MAPEL file MASTER (.xlsm):
     * kelompok A (Muatan Nasional), B (Muatan Kewilayahan),
     * C1 (Dasar Bidang Keahlian), C2 (Dasar Program Keahlian),
     * C3 (Kompetensi Keahlian). jurusan_id null = berlaku semua jurusan;
     * tingkat null = berlaku semua tingkat.
     */
    public function up(): void
    {
        Schema::create('mapel', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 20)->nullable();
            $table->string('nama', 150);
            $table->enum('kelompok', ['A', 'B', 'C1', 'C2', 'C3']);
            $table->foreignId('jurusan_id')->nullable()->constrained('jurusan')->nullOnDelete();
            $table->enum('tingkat', ['X', 'XI', 'XII'])->nullable();
            $table->integer('urutan')->default(0);
            $table->decimal('kkm_default', 5, 2)->default(76);
            $table->timestamps();
            $table->unique(['nama', 'jurusan_id', 'tingkat']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mapel');
    }
};
