<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Mapel extends Model
{
    protected $table = 'mapel';

    protected $fillable = [
        'kode', 'nama', 'kelompok', 'jurusan_id', 'tingkat', 'urutan', 'kkm_default',
        'salinan_dari_id',
    ];

    protected function casts(): array
    {
        return ['kkm_default' => 'decimal:2'];
    }

    public const KELOMPOK = [
        'A' => 'A. Muatan Nasional',
        'B' => 'B. Muatan Kewilayahan',
        'C1' => 'C1. Dasar Bidang Keahlian',
        'C2' => 'C2. Dasar Program Keahlian',
        'C3' => 'C3. Kompetensi Keahlian',
    ];

    public function jurusan() { return $this->belongsTo(Jurusan::class); }
    public function nilai() { return $this->hasMany(Nilai::class); }

    /** Mapel umum yang disalin menjadi mapel khusus jurusan ini (bila ada). */
    public function salinanDari() { return $this->belongsTo(static::class, 'salinan_dari_id'); }

    public function kelompokLabel(): string
    {
        return static::KELOMPOK[$this->kelompok] ?? $this->kelompok;
    }

    /** Mapel yang berlaku untuk jurusan ini (khusus jurusan itu + yang umum). */
    public function scopeUntukJurusan($query, ?int $jurusanId)
    {
        return $query->where(fn ($q) => $q->whereNull('jurusan_id')->orWhere('jurusan_id', $jurusanId));
    }

    /**
     * Daftar mapel untuk satu jurusan + tingkat: GABUNGAN mapel umum
     * (jurusan_id NULL) dan mapel khusus jurusan itu.
     *
     * Data Leger 2021-2022 membuktikan keduanya berdampingan: tiap
     * jurusan mengambil mapel umum (mis. Produk Kreatif dan
     * Kewirausahaan, Dasar Listrik) PLUS mapel C2/C3-nya sendiri —
     * jadi aturan lama (mapel khusus MENGGANTIKAN seluruh kelompok)
     * justru menghilangkan nilai yang benar-benar diambil siswa.
     *
     * Pengecualian: mapel khusus yang merupakan SALINAN dari mapel
     * umum (salinan_dari_id terisi — fitur "Salin daftar umum")
     * menggantikan mapel umum asalnya, walau namanya sudah diubah
     * admin; dan mapel khusus yang namanya sama persis dengan mapel
     * umum juga menggantikan yang umum (bukan duplikat).
     */
    public static function untukJurusanTingkat(?int $jurusanId, ?string $tingkat): \Illuminate\Support\Collection
    {
        $semua = static::untukTingkat($tingkat)->terurut()->get();
        $khusus = $semua->where('jurusan_id', $jurusanId);
        $umum = $semua->whereNull('jurusan_id');

        $digantiId = $khusus->map(fn ($m) => $m->salinan_dari_id)->filter()->all();
        $namaKhusus = $khusus->map(
            fn ($m) => [mb_strtolower($m->nama), $m->kelompok, $m->tingkat]
        )->all();
        $umum = $umum->reject(function ($m) use ($digantiId, $namaKhusus) {
            if (in_array($m->id, $digantiId, true)) {
                return true;
            }

            return in_array(
                [mb_strtolower($m->nama), $m->kelompok, $m->tingkat],
                $namaKhusus,
                true
            );
        });

        return $umum->merge($khusus)
            ->sortBy([['urutan', 'asc'], ['nama', 'asc']])->values();
    }

    /** Mapel yang berlaku untuk tingkat ini (khusus tingkat itu + yang umum). */
    public function scopeUntukTingkat($query, ?string $tingkat)
    {
        return $query->where(fn ($q) => $q->whereNull('tingkat')->orWhere('tingkat', $tingkat));
    }

    public function scopeTerurut($query)
    {
        return $query->orderBy('urutan')->orderBy('nama');
    }
}
