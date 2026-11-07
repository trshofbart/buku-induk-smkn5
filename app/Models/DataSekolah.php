<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Satu baris identitas sekolah (id = 1). Dipakai di kop cetakan. */
class DataSekolah extends Model
{
    protected $table = 'data_sekolah';

    protected $fillable = ['nama_sekolah', 'npsn', 'alamat', 'telepon', 'email', 'website', 'kepala_sekolah', 'logo', 'bidang_studi_keahlian', 'tempat_ttd', 'tanggal_rapor', 'input_nilai_dibuka'];

    protected function casts(): array
    {
        return [
            'tanggal_rapor' => 'date',
            'input_nilai_dibuka' => 'boolean',
        ];
    }

    public static function get(): self
    {
        return static::firstOrCreate(['id' => 1], [
            'nama_sekolah' => 'SMK Negeri 5 Surakarta',
            'alamat' => 'Surakarta, Jawa Tengah',
            'bidang_studi_keahlian' => 'Teknologi dan Rekayasa',
            'tempat_ttd' => 'Surakarta',
            'input_nilai_dibuka' => true,
        ]);
    }
}
