<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    public const ROLE_ADMIN_TU = 'admin_tu';
    public const ROLE_KEPSEK = 'kepala_sekolah';
    public const ROLE_GURU = 'guru';

    public static function roles(): array
    {
        return [
            self::ROLE_ADMIN_TU => 'Admin TU',
            self::ROLE_KEPSEK => 'Kepala Sekolah',
            self::ROLE_GURU => 'Guru',
        ];
    }

    protected $fillable = ['name', 'email', 'password', 'role', 'nip', 'is_active'];

    protected $hidden = ['password', 'remember_token'];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_active' => 'boolean',
        ];
    }

    // --- Helper role ---
    public function isAdminTu(): bool { return $this->role === self::ROLE_ADMIN_TU; }
    public function isKepsek(): bool { return $this->role === self::ROLE_KEPSEK; }
    public function isGuru(): bool { return $this->role === self::ROLE_GURU; }
    public function canEdit(): bool { return $this->role === self::ROLE_ADMIN_TU; }
    public function roleLabel(): string { return self::roles()[$this->role] ?? $this->role; }

    // --- Relasi ---
    public function berita() { return $this->hasMany(Berita::class); }
    public function activityLogs() { return $this->hasMany(ActivityLog::class); }
}
