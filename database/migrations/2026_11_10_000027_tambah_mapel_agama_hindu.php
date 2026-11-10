<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Mapel agama Hindu belum ada — padahal ada siswa beragama Hindu.
     * Daftar mapel agama sebelumnya hanya Islam, Kristen, Katholik,
     * Buddha (semua umum, kelompok A, urutan 1).
     */
    public function up(): void
    {
        $ada = DB::table('mapel')
            ->where('nama', 'Pendidikan Agama Hindu dan Budi Pekerti')
            ->whereNull('jurusan_id')->whereNull('tingkat')
            ->exists();

        if (! $ada) {
            DB::table('mapel')->insert([
                'nama' => 'Pendidikan Agama Hindu dan Budi Pekerti',
                'kelompok' => 'A',
                'jurusan_id' => null,
                'tingkat' => null,
                'urutan' => 1,
                'kkm_default' => 76,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
    }

    public function down(): void
    {
        DB::table('mapel')
            ->where('nama', 'Pendidikan Agama Hindu dan Budi Pekerti')
            ->whereNull('jurusan_id')->whereNull('tingkat')
            ->delete();
    }
};
