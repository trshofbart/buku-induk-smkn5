<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    protected $table = 'activity_logs';

    public $timestamps = false;

    protected $fillable = ['user_id', 'aksi', 'model_type', 'model_id', 'keterangan', 'ip_address', 'created_at'];

    protected function casts(): array
    {
        return ['created_at' => 'datetime'];
    }

    public function user() { return $this->belongsTo(User::class); }

    public static function catat(string $aksi, ?string $keterangan = null, $model = null): void
    {
        static::create([
            'user_id' => auth()->id(),
            'aksi' => $aksi,
            'model_type' => $model ? get_class($model) : null,
            'model_id' => $model?->getKey(),
            'keterangan' => $keterangan,
            'ip_address' => request()?->ip(),
            'created_at' => now(),
        ]);
    }
}
