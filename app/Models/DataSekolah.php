<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Satu baris identitas sekolah (id = 1). Dipakai di kop cetakan. */
class DataSekolah extends Model
{
    protected $table = 'data_sekolah';

    protected $fillable = ['nama_sekolah', 'npsn', 'alamat', 'telepon', 'email', 'website', 'kepala_sekolah', 'logo'];

    public static function get(): self
    {
        return static::firstOrCreate(['id' => 1], [
            'nama_sekolah' => 'SMK Negeri 5 Surakarta',
            'alamat' => 'Surakarta, Jawa Tengah',
        ]);
    }
}
