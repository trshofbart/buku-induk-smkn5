<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('kelas', function (Blueprint $table) {
            $table->id();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajaran')->cascadeOnDelete();
            $table->foreignId('jurusan_id')->constrained('jurusan')->cascadeOnDelete();
            $table->enum('tingkat', ['X', 'XI', 'XII', 'XIII']);
            $table->string('nama', 50); // XII RPL A
            $table->string('wali_kelas', 100)->nullable();
            $table->timestamps();
            $table->unique(['tahun_ajaran_id', 'jurusan_id', 'nama']);
            $table->index(['tahun_ajaran_id', 'jurusan_id', 'tingkat']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('kelas');
    }
};
