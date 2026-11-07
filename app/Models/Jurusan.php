<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Jurusan extends Model
{
    protected $table = 'jurusan';

    protected $fillable = ['kode', 'nama', 'program_studi', 'deskripsi'];

    public function kelas() { return $this->hasMany(Kelas::class); }
    public function siswa() { return $this->hasMany(Siswa::class); }
}
