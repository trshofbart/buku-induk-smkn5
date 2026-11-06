<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Kolom tambahan agar halaman cetak bisa mengikuti formulir
     * HAL_PRINT pada file MASTER (.xlsm): sekolah asal, ijazah,
     * diterima di tingkat, mutasi, dan alamat/telepon wali.
     * Semua nullable — data lama tetap valid tanpa backfill.
     */
    public function up(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            $table->string('sekolah_asal', 150)->nullable()->after('alamat');
            $table->text('alamat_sekolah_asal')->nullable()->after('sekolah_asal');
            $table->string('tahun_lulus', 9)->nullable()->after('alamat_sekolah_asal');
            $table->string('no_ijazah', 50)->nullable()->after('tahun_lulus');
            $table->string('diterima_tingkat', 30)->nullable()->after('no_ijazah');
            $table->string('mutasi_ke', 100)->nullable()->after('tanggal_keluar');
            $table->string('mutasi_dari', 100)->nullable()->after('mutasi_ke');
        });

        Schema::table('orang_tua', function (Blueprint $table) {
            $table->text('alamat_wali')->nullable()->after('alamat_ortu');
            $table->string('no_hp_wali', 20)->nullable()->after('alamat_wali');
        });
    }

    public function down(): void
    {
        Schema::table('siswa', function (Blueprint $table) {
            $table->dropColumn([
                'sekolah_asal', 'alamat_sekolah_asal', 'tahun_lulus', 'no_ijazah',
                'diterima_tingkat', 'mutasi_ke', 'mutasi_dari',
            ]);
        });

        Schema::table('orang_tua', function (Blueprint $table) {
            $table->dropColumn(['alamat_wali', 'no_hp_wali']);
        });
    }
};
