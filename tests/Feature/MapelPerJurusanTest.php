<?php

namespace Tests\Feature;

use App\Models\Jurusan;
use App\Models\Mapel;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MapelPerJurusanTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_halaman_mapel_per_jurusan_menampilkan_daftar_umum(): void
    {
        $admin = User::where('email', 'admin@smkn5.sch.id')->firstOrFail();
        $jurusan = Jurusan::where('kode', 'TKJ')->firstOrFail();

        $this->actingAs($admin)->get(route('admin.mapel.perjurusan', ['jurusan_id' => $jurusan->id]))
            ->assertOk()
            ->assertSee('Mapel per Jurusan')
            ->assertSee('Penerapan Rangkaian Elektronika')
            ->assertSee('Umum');
    }

    public function test_salin_daftar_umum_lalu_cetak_memakai_daftar_khusus_jurusan(): void
    {
        $admin = User::where('email', 'admin@smkn5.sch.id')->firstOrFail();
        $siswa = Siswa::whereNotNull('kelas_id')->firstOrFail();
        $jurusan = $siswa->jurusan;

        $this->actingAs($admin)->post(route('admin.mapel.salin'), ['jurusan_id' => $jurusan->id])
            ->assertRedirect();
        $this->assertTrue(Mapel::where('jurusan_id', $jurusan->id)->where('kelompok', 'C3')->exists());

        // Ubah satu mapel khusus TPM dari nama umumnya.
        $khusus = Mapel::where('jurusan_id', $jurusan->id)
            ->where('nama', 'Penerapan Rangkaian Elektronika')->firstOrFail();
        $khusus->update(['nama' => 'Teknik Jaringan Khusus']);

        $laporan = $siswa->laporanNilai();
        $namaC3 = collect($laporan['X']['kelompok']['C3'] ?? [])->map(fn ($r) => $r['mapel']->nama);
        $this->assertContains('Teknik Jaringan Khusus', $namaC3);
        $this->assertNotContains('Penerapan Rangkaian Elektronika', $namaC3);
        // Kelompok lain tetap memakai daftar umum.
        $namaA = collect($laporan['X']['kelompok']['A'] ?? [])->map(fn ($r) => $r['mapel']->nama);
        $this->assertContains('Pendidikan Agama Islam dan Budi Pekerti', $namaA);
    }

    public function test_tambah_mapel_khusus_jurusan_lewat_halaman_per_jurusan(): void
    {
        $admin = User::where('email', 'admin@smkn5.sch.id')->firstOrFail();
        $jurusan = Jurusan::where('kode', 'TKJ')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.mapel.store'), [
            'nama' => 'Gambar Teknik Mesin',
            'kelompok' => 'C2',
            'jurusan_id' => $jurusan->id,
            'tingkat' => 'X',
            'urutan' => 1,
            'kkm_default' => 76,
        ])->assertRedirect();

        $this->assertDatabaseHas('mapel', ['nama' => 'Gambar Teknik Mesin', 'jurusan_id' => $jurusan->id, 'kelompok' => 'C2']);
        $this->actingAs($admin)->get(route('admin.mapel.perjurusan', ['jurusan_id' => $jurusan->id]))
            ->assertOk()
            ->assertSee('Gambar Teknik Mesin')
            ->assertSee('Khusus TKJ');
    }

    public function test_guru_tidak_bisa_membuka_mapel_per_jurusan(): void
    {
        $guru = User::where('email', 'guru@smkn5.sch.id')->firstOrFail();
        $this->actingAs($guru)->get(route('admin.mapel.perjurusan'))->assertForbidden();
    }
}
