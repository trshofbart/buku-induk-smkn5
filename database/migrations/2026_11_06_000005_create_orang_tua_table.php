<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orang_tua', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->unique()->constrained('siswa')->cascadeOnDelete();
            $table->string('nama_ayah', 150)->nullable();
            $table->string('pekerjaan_ayah', 100)->nullable();
            $table->string('nama_ibu', 150)->nullable();
            $table->string('pekerjaan_ibu', 100)->nullable();
            $table->string('nama_wali', 150)->nullable();
            $table->string('pekerjaan_wali', 100)->nullable();
            $table->string('no_hp_ortu', 20)->nullable();
            $table->text('alamat_ortu')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('orang_tua');
    }
};
