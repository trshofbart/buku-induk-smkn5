<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Berita extends Model
{
    protected $table = 'berita';

    protected $fillable = [
        'kategori_berita_id', 'user_id', 'judul', 'slug', 'ringkasan',
        'isi', 'foto', 'status', 'published_at', 'views',
    ];

    protected function casts(): array
    {
        return ['published_at' => 'datetime'];
    }

    public function kategori() { return $this->belongsTo(KategoriBerita::class, 'kategori_berita_id'); }
    public function penulis() { return $this->belongsTo(User::class, 'user_id'); }

    public function scopePublished($query)
    {
        return $query->where('status', 'publish')
            ->where(function ($q) {
                $q->whereNull('published_at')->orWhere('published_at', '<=', now());
            });
    }

    /** Buat slug unik dari judul. */
    public static function buatSlug(string $judul, ?int $kecualiId = null): string
    {
        $slug = Str::slug($judul) ?: 'berita';
        $dasar = $slug;
        $i = 2;
        while (static::where('slug', $slug)->when($kecualiId, fn ($q) => $q->where('id', '!=', $kecualiId))->exists()) {
            $slug = $dasar.'-'.$i++;
        }
        return $slug;
    }
}
