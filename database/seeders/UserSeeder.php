<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

/**
 * Akun CONTOH.
 *
 * Password TIDAK lagi hardcode di repo (repo ini publik).
 * Isi lewat .env (SEED_ADMIN_PASSWORD / SEED_KEPSEK_PASSWORD /
 * SEED_GURU_PASSWORD); kalau kosong, dibuat acak 12 karakter dan
 * ditampilkan sekali saat seeding — catat, lalu ganti berkala.
 * Akun yang sudah ada di database TIDAK diubah oleh seeder ini;
 * untuk mengganti password akun yang sudah terpasang, pakai
 * `php artisan tinker` (lihat README bagian Keamanan).
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        $this->seedUser(
            'Admin TU (Contoh)',
            'admin@smkn5.sch.id',
            User::ROLE_ADMIN_TU,
            '198001012005011001',
            env('SEED_ADMIN_PASSWORD')
        );

        $this->seedUser(
            'Kepala Sekolah (Contoh)',
            'kepsek@smkn5.sch.id',
            User::ROLE_KEPSEK,
            '197505152000031002',
            env('SEED_KEPSEK_PASSWORD')
        );

        $this->seedUser(
            'Guru (Contoh)',
            'guru@smkn5.sch.id',
            User::ROLE_GURU,
            '199001012015011003',
            env('SEED_GURU_PASSWORD')
        );
    }

    private function seedUser(string $name, string $email, string $role, string $nip, ?string $password): void
    {
        $generated = $password === null || $password === '';
        $password = $generated ? Str::random(12) : $password;

        User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            'role' => $role,
            'nip' => $nip,
            'is_active' => true,
        ]);

        if ($generated && $this->command) {
            $this->command->info("Akun contoh {$email} dibuat dengan password acak: {$password}");
        }
    }
}
