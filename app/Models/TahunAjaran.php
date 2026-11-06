<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class TahunAjaran extends Model
{
    protected $table = 'tahun_ajaran';

    protected $fillable = ['nama', 'is_active', 'tanggal_mulai', 'tanggal_selesai'];

    protected function casts(): array
    {
        return ['is_active' => 'boolean', 'tanggal_mulai' => 'date', 'tanggal_selesai' => 'date'];
    }

    public function kelas() { return $this->hasMany(Kelas::class); }
    public function siswa() { return $this->hasMany(Siswa::class); }
    public function ledger() { return $this->hasMany(Ledger::class); }
    public function transkrip() { return $this->hasMany(Transkrip::class); }

    public static function aktif(): ?self
    {
        return static::where('is_active', true)->first()
            ?? static::orderByDesc('nama')->first();
    }

    /** Set satu tahun ajaran aktif, nonaktifkan yang lain. */
    public function setAktif(): void
    {
        static::where('id', '!=', $this->id)->update(['is_active' => false]);
        $this->update(['is_active' => true]);
    }
}
