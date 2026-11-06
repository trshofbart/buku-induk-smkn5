<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Nilai per mata pelajaran per semester, mengikuti sheet SMSTR 1-6
     * file MASTER: nilai_angka = nilai Pengetahuan (kolom pertama tiap
     * blok mapel), sama dengan kolom NILAI di HAL_PRINT; kkm mengikuti
     * HAL_PRINT (76). Huruf (predikat) tidak disimpan — dihitung dari
     * angka dengan skala sekolah (lihat Nilai::predikat()).
     */
    public function up(): void
    {
        Schema::create('nilai', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->foreignId('mapel_id')->constrained('mapel')->cascadeOnDelete();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajaran');
            $table->unsignedTinyInteger('semester');
            $table->decimal('kkm', 5, 2)->nullable();
            $table->decimal('nilai_angka', 5, 2)->nullable();
            $table->timestamps();
            $table->unique(['siswa_id', 'mapel_id', 'tahun_ajaran_id', 'semester'], 'nilai_unik');
            $table->index(['mapel_id', 'tahun_ajaran_id', 'semester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('nilai');
    }
};
