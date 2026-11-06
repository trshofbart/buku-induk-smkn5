<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class DokumenSiswa extends Model
{
    protected $table = 'dokumen_siswa';

    public const JENIS = ['akta' => 'Akta Kelahiran', 'kk' => 'Kartu Keluarga', 'ijazah' => 'Ijazah', 'foto' => 'Foto', 'lain' => 'Lainnya'];

    protected $fillable = ['siswa_id', 'jenis', 'nama_file', 'path'];

    public function siswa() { return $this->belongsTo(Siswa::class); }

    public function jenisLabel(): string { return self::JENIS[$this->jenis] ?? $this->jenis; }
}
