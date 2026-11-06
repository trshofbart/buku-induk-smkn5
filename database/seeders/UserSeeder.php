<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Akun CONTOH — password semuanya "password".
 * WAJIB diganti setelah install di produksi!
 */
class UserSeeder extends Seeder
{
    public function run(): void
    {
        User::create([
            'name' => 'Admin TU (Contoh)',
            'email' => 'admin@smkn5.sch.id',
            'password' => Hash::make('password'),
            'role' => User::ROLE_ADMIN_TU,
            'nip' => '198001012005011001',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Kepala Sekolah (Contoh)',
            'email' => 'kepsek@smkn5.sch.id',
            'password' => Hash::make('password'),
            'role' => User::ROLE_KEPSEK,
            'nip' => '197505152000031002',
            'is_active' => true,
        ]);

        User::create([
            'name' => 'Guru (Contoh)',
            'email' => 'guru@smkn5.sch.id',
            'password' => Hash::make('password'),
            'role' => User::ROLE_GURU,
            'nip' => '199001012015011003',
            'is_active' => true,
        ]);
    }
}
