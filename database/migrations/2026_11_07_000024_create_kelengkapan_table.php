<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kelengkapan per siswa per semester ala e-Rapor (diisi Wali Kelas;
     * di aplikasi ini oleh Admin TU): kehadiran, kepribadian
     * (kelakuan/kerapian/kerajinan), akhlak mulia, catatan wali kelas,
     * dan status akhir/kenaikan kelas. Satu baris per siswa per semester.
     */
    public function up(): void
    {
        Schema::create('kelengkapan', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajaran')->cascadeOnDelete();
            $table->unsignedTinyInteger('semester');
            $table->unsignedSmallInteger('sakit')->default(0);
            $table->unsignedSmallInteger('izin')->default(0);
            $table->unsignedSmallInteger('alpa')->default(0);
            $table->string('kelakuan', 20)->nullable();
            $table->string('kerapian', 20)->nullable();
            $table->string('kerajinan', 20)->nullable();
            $table->string('akhlak', 20)->nullable();
            $table->text('catatan')->nullable();
            $table->string('kenaikan', 100)->nullable();
            $table->timestamps();
            $table->unique(['siswa_id', 'tahun_ajaran_id', 'semester']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelengkapan');
    }
};
