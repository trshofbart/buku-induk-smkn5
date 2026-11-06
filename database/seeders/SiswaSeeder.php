<?php

namespace Database\Seeders;

use App\Models\Jurusan;
use App\Models\Kelas;
use App\Models\Ledger;
use App\Models\OrangTua;
use App\Models\RiwayatKelas;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\Transkrip;
use Illuminate\Database\Seeder;

/**
 * 24 siswa CONTOH fiktif. Nama-nama di bawah ini bukan siswa asli.
 * NIS dibuat berurutan supaya mudah dicari saat demo.
 */
class SiswaSeeder extends Seeder
{
    public function run(): void
    {
        $aktif = TahunAjaran::where('nama', '2025-2026')->first();
        $lama = TahunAjaran::where('nama', '2021-2022')->first();

        $nama = [
            // [nama, jk, jurusan, tingkat, tahun]
            ['Andi Pratama', 'L', 'RPL', 'XII', '2025-2026'],
            ['Budi Santoso', 'L', 'RPL', 'XII', '2025-2026'],
            ['Citra Lestari', 'P', 'RPL', 'XI', '2025-2026'],
            ['Dewi Anggraini', 'P', 'RPL', 'X', '2025-2026'],
            ['Eko Prasetyo', 'L', 'RPL', 'XIII', '2025-2026'],
            ['Fitri Rahmawati', 'P', 'TEI', 'XII', '2025-2026'],
            ['Galih Saputra', 'L', 'TEI', 'XI', '2025-2026'],
            ['Hana Safitri', 'P', 'TEI', 'X', '2025-2026'],
            ['Irfan Maulana', 'L', 'TEI', 'XII', '2025-2026'],
            ['Joko Susilo', 'L', 'TEI', 'XIII', '2025-2026'],
            ['Kirana Dewi', 'P', 'RPL', 'XI', '2025-2026'],
            ['Lukman Hakim', 'L', 'RPL', 'X', '2025-2026'],
            ['Maya Putri', 'P', 'TEI', 'XI', '2025-2026'],
            ['Nadia Salsabila', 'P', 'RPL', 'XII', '2025-2026'],
            ['Putra Wijaya', 'L', 'TEI', 'X', '2025-2026'],
            ['Rina Marlina', 'P', 'RPL', 'XIII', '2025-2026'],
            ['Sari Wulandari', 'P', 'TEI', 'XII', '2025-2026'],
            ['Tono Wibowo', 'L', 'RPL', 'XI', '2025-2026'],
            ['Umi Kalsum', 'P', 'TEI', 'XIII', '2025-2026'],
            ['Vina Amelia', 'P', 'RPL', 'X', '2025-2026'],
            // Alumni / lulusan tahun 2021-2022 (contoh data lama)
            ['Agus Setiawan', 'L', 'RPL', 'XIII', '2021-2022'],
            ['Ratna Sari', 'P', 'TEI', 'XII', '2021-2022'],
            ['Dedi Kurniawan', 'L', 'RPL', 'XII', '2021-2022'],
            ['Sinta Maharani', 'P', 'TEI', 'XIII', '2021-2022'],
        ];

        $no = 1;
        foreach ($nama as [$namaSiswa, $jk, $kodeJurusan, $tingkat, $tahunNama]) {
            $tahun = $tahunNama === '2025-2026' ? $aktif : $lama;
            $jurusan = Jurusan::where('kode', $kodeJurusan)->first();
            $kelas = Kelas::where('tahun_ajaran_id', $tahun->id)
                ->where('jurusan_id', $jurusan->id)
                ->where('tingkat', $tingkat)->first();

            $isLulus = $tahunNama === '2021-2022';

            $siswa = Siswa::create([
                'nis' => '2025'.str_pad($no, 4, '0', STR_PAD_LEFT),
                'nisn' => '003'.str_pad(1000000 + $no, 7, '0', STR_PAD_LEFT),
                'nama' => $namaSiswa,
                'jenis_kelamin' => $jk,
                'tempat_lahir' => 'Surakarta',
                'tanggal_lahir' => '2008-0'.(($no % 9) + 1).'-'.str_pad(($no % 27) + 1, 2, '0', STR_PAD_LEFT),
                'agama' => 'Islam',
                'alamat' => 'Jl. Contoh No. '.$no.', Surakarta',
                'no_hp' => '0812'.str_pad(34560000 + $no, 8, '0', STR_PAD_LEFT),
                'tahun_ajaran_id' => $tahun->id,
                'jurusan_id' => $jurusan->id,
                'kelas_id' => $kelas?->id,
                'status' => $isLulus ? 'lulus' : 'aktif',
                'tanggal_masuk' => $tahun->tanggal_mulai,
                'tanggal_keluar' => $isLulus ? $tahun->tanggal_selesai : null,
            ]);

            OrangTua::create([
                'siswa_id' => $siswa->id,
                'nama_ayah' => 'Ayah '.$namaSiswa,
                'pekerjaan_ayah' => 'Wiraswasta',
                'nama_ibu' => 'Ibu '.$namaSiswa,
                'pekerjaan_ibu' => 'Ibu Rumah Tangga',
                'no_hp_ortu' => '0813'.str_pad(76540000 + $no, 8, '0', STR_PAD_LEFT),
                'alamat_ortu' => $siswa->alamat,
            ]);

            if ($kelas) {
                RiwayatKelas::create([
                    'siswa_id' => $siswa->id,
                    'kelas_id' => $kelas->id,
                    'tahun_ajaran_id' => $tahun->id,
                    'status_naik' => $isLulus ? 'lulus' : 'naik',
                ]);
            }

            // Ledger contoh: semester 1-2 untuk siswa aktif, 1-8 untuk lulusan.
            $maxSemester = $isLulus ? 8 : 2;
            for ($smt = 1; $smt <= $maxSemester; $smt++) {
                Ledger::create([
                    'siswa_id' => $siswa->id,
                    'tahun_ajaran_id' => $tahun->id,
                    'semester' => $smt,
                    'rata_rata' => 75 + (($no + $smt) % 20),
                    'peringkat' => ($no % 10) + 1,
                ]);
            }

            if ($isLulus) {
                Transkrip::create([
                    'siswa_id' => $siswa->id,
                    'tahun_ajaran_id' => $tahun->id,
                    'nomor_transkrip' => 'TR/'.$tahun->nama.'/'.str_pad($no, 4, '0', STR_PAD_LEFT),
                    'tanggal_terbit' => $tahun->tanggal_selesai,
                    'keterangan' => 'Lulus.',
                ]);
            }

            $no++;
        }
    }
}
