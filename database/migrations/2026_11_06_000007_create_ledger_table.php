<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Pengganti folder LEDGER ... SEMESTER 1-8 di Drive.
        Schema::create('ledger', function (Blueprint $table) {
            $table->id();
            $table->foreignId('siswa_id')->constrained('siswa')->cascadeOnDelete();
            $table->foreignId('tahun_ajaran_id')->constrained('tahun_ajaran');
            $table->tinyInteger('semester'); // 1-8
            $table->decimal('rata_rata', 5, 2)->nullable();
            $table->integer('peringkat')->nullable();
            $table->text('catatan')->nullable();
            $table->timestamps();
            $table->unique(['siswa_id', 'semester', 'tahun_ajaran_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ledger');
    }
};
