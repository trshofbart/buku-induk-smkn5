<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

/** Nilai/rekap per semester (pengganti folder LEDGER ... SEMESTER 1-8). */
class Ledger extends Model
{
    protected $table = 'ledger';

    protected $fillable = ['siswa_id', 'tahun_ajaran_id', 'semester', 'rata_rata', 'peringkat', 'catatan'];

    protected function casts(): array
    {
        return ['rata_rata' => 'decimal:2', 'semester' => 'integer', 'peringkat' => 'integer'];
    }

    public function siswa() { return $this->belongsTo(Siswa::class); }
    public function tahunAjaran() { return $this->belongsTo(TahunAjaran::class); }
}
