<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Pindahkan nilai agama siswa beragama Hindu dari mapel Islam ke
     * mapel Hindu (migrasi 000027). Nilai itu tercatat di mapel Islam
     * karena file sumber hanya punya satu kolom agama per siswa dan
     * mapel Hindu belum ada saat import — isi nilainya tetap nilai
     * agama siswa itu sendiri, hanya mapelnya yang salah.
     *
     * Idempotent: bila dijalankan lagi, tidak ada lagi nilai Islam
     * milik siswa Hindu, jadi tidak ada yang berubah.
     */
    public function up(): void
    {
        $islam = DB::table('mapel')
            ->where('nama', 'Pendidikan Agama Islam dan Budi Pekerti')
            ->whereNull('jurusan_id')->whereNull('tingkat')->value('id');
        $hindu = DB::table('mapel')
            ->where('nama', 'Pendidikan Agama Hindu dan Budi Pekerti')
            ->whereNull('jurusan_id')->whereNull('tingkat')->value('id');

        if (! $islam || ! $hindu) {
            return;
        }

        $siswaHindu = DB::table('siswa')->where('agama', 'Hindu')->pluck('id');

        DB::table('nilai')
            ->whereIn('siswa_id', $siswaHindu)
            ->where('mapel_id', $islam)
            ->update(['mapel_id' => $hindu]);
    }

    public function down(): void
    {
        $islam = DB::table('mapel')
            ->where('nama', 'Pendidikan Agama Islam dan Budi Pekerti')
            ->whereNull('jurusan_id')->whereNull('tingkat')->value('id');
        $hindu = DB::table('mapel')
            ->where('nama', 'Pendidikan Agama Hindu dan Budi Pekerti')
            ->whereNull('jurusan_id')->whereNull('tingkat')->value('id');

        if (! $islam || ! $hindu) {
            return;
        }

        $siswaHindu = DB::table('siswa')->where('agama', 'Hindu')->pluck('id');

        DB::table('nilai')
            ->whereIn('siswa_id', $siswaHindu)
            ->where('mapel_id', $hindu)
            ->update(['mapel_id' => $islam]);
    }
};
