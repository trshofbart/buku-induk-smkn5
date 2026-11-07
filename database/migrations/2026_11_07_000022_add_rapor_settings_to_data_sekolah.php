<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Pengaturan rapor ala e-Rapor (referensi Admin): bidang studi keahlian,
     * tempat & tanggal rapor untuk blok tanda tangan, dan kunci buka/tutup
     * input nilai. Keputusan Bart 2026-11-07: KKM tetap, tanpa TP,
     * tanpa predikat A-D, tanpa tarik data Dapodik, input nilai hanya Admin.
     */
    public function up(): void
    {
        Schema::table('data_sekolah', function (Blueprint $table) {
            $table->string('bidang_studi_keahlian', 100)->default('Teknologi dan Rekayasa')->after('kepala_sekolah');
            $table->string('tempat_ttd', 100)->nullable()->after('bidang_studi_keahlian');
            $table->date('tanggal_rapor')->nullable()->after('tempat_ttd');
            $table->boolean('input_nilai_dibuka')->default(true)->after('tanggal_rapor');
        });
    }

    public function down(): void
    {
        Schema::table('data_sekolah', function (Blueprint $table) {
            $table->dropColumn(['bidang_studi_keahlian', 'tempat_ttd', 'tanggal_rapor', 'input_nilai_dibuka']);
        });
    }
};
