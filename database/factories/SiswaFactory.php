<?php

namespace Database\Factories;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * Factory siswa — data CONTOH fiktif (bukan data asli).
 */
class SiswaFactory extends Factory
{
    protected $model = Siswa::class;

    public function definition(): array
    {
        $jk = $this->faker->randomElement(['L', 'P']);
        $tahun = TahunAjaran::aktif() ?? TahunAjaran::first();
        $jurusan = Jurusan::inRandomOrder()->first();
        $kelas = $jurusan ? Kelas::where('jurusan_id', $jurusan->id)->inRandomOrder()->first() : null;

        return [
            'nis' => (string) $this->faker->unique()->numerify('2025####'),
            'nisn' => (string) $this->faker->unique()->numerify('00########'),
            'nama' => $this->faker->name($jk === 'L' ? 'male' : 'female'),
            'jenis_kelamin' => $jk,
            'tempat_lahir' => $this->faker->randomElement(['Surakarta', 'Sukoharjo', 'Karanganyar', 'Klaten', 'Boyolali']),
            'tanggal_lahir' => $this->faker->dateTimeBetween('-19 years', '-16 years')->format('Y-m-d'),
            'agama' => $this->faker->randomElement(['Islam', 'Kristen', 'Katolik', 'Hindu', 'Buddha']),
            'alamat' => $this->faker->address(),
            'no_hp' => $this->faker->numerify('08##########'),
            'tahun_ajaran_id' => $tahun?->id,
            'jurusan_id' => $jurusan?->id,
            'kelas_id' => $kelas?->id,
            'status' => 'aktif',
            'tanggal_masuk' => $tahun?->tanggal_mulai,
        ];
    }
}
