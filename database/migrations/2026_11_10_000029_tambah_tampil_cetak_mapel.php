<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Saklar "Tampilkan di lembar cetak" per mapel. Admin mengatur
     * lewat menu Mata Pelajaran (Edit) — mapel yang dimatikan tidak
     * muncul di cetak Buku Induk / Transkrip, tapi nilainya tetap
     * tersimpan dan tetap bisa diinput. Default: tampil (true),
     * sehingga perilaku lama tidak berubah sampai admin menyembunyikan
     * sesuatu.
     */
    public function up(): void
    {
        Schema::table('mapel', function (Blueprint $table) {
            $table->boolean('tampil_cetak')->default(true)->after('urutan');
        });
    }

    public function down(): void
    {
        Schema::table('mapel', function (Blueprint $table) {
            $table->dropColumn('tampil_cetak');
        });
    }
};
