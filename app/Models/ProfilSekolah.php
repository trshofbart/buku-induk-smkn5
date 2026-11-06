<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Halaman profil dinamis — satu baris per slug. */
class ProfilSekolah extends Model
{
    protected $table = 'profil_sekolah';

    protected $fillable = ['slug', 'judul', 'isi', 'gambar', 'urutan', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public static function halaman(): array
    {
        return [
            'sejarah' => 'Sejarah Sekolah',
            'visi-misi' => 'Visi & Misi',
            'sambutan-kepala-sekolah' => 'Sambutan Kepala Sekolah',
            'struktur-organisasi' => 'Struktur Organisasi',
            'guru-staff' => 'Guru & Staff',
            'fasilitas' => 'Fasilitas',
            'kontak' => 'Kontak',
        ];
    }
}
