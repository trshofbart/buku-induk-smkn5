<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Pengumuman extends Model
{
    protected $table = 'pengumuman';

    protected $fillable = ['judul', 'isi', 'tanggal_mulai', 'tanggal_selesai', 'is_active'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'tanggal_mulai' => 'date', 'tanggal_selesai' => 'date'];
    }

    public function scopeAktif($query)
    {
        $hariIni = today();
        return $query->where('is_active', true)
            ->where(function ($q) use ($hariIni) {
                $q->whereNull('tanggal_mulai')->orWhere('tanggal_mulai', '<=', $hariIni);
            })
            ->where(function ($q) use ($hariIni) {
                $q->whereNull('tanggal_selesai')->orWhere('tanggal_selesai', '>=', $hariIni);
            });
    }
}
