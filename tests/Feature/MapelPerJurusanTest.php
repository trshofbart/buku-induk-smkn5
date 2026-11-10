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
            ->assertSee('Produk Kreatif dan Kewirausahaan')
            ->assertSee('Umum');
    }

    public function test_halaman_mapel_per_jurusan_menampilkan_kelompok_c1(): void
    {
        $admin = User::where('email', 'admin@smkn5.sch.id')->firstOrFail();
        $jurusan = Jurusan::where('kode', 'TKJ')->firstOrFail();

        $this->actingAs($admin)->get(route('admin.mapel.perjurusan', ['jurusan_id' => $jurusan->id]))
            ->assertOk()
            ->assertSee('C1 — Dasar Bidang Keahlian')
            ->assertSee('Simulasi dan Komunikasi Digital')
            ->assertSee('Fisika');

        // Salin juga menyalin mapel C1 umum menjadi khusus jurusan.
        $this->actingAs($admin)->post(route('admin.mapel.salin'), ['jurusan_id' => $jurusan->id])
            ->assertRedirect();
        $this->assertTrue(Mapel::where('jurusan_id', $jurusan->id)->where('kelompok', 'C1')->exists());
    }

    public function test_mapel_agama_hindu_ada_dan_tampil_di_cetak(): void
    {
        $siswa = Siswa::whereNotNull('kelas_id')->firstOrFail();

        $hindu = Mapel::where('nama', 'Pendidikan Agama Hindu dan Budi Pekerti')->first();
        $this->assertNotNull($hindu);
        $this->assertNull($hindu->jurusan_id);
        $this->assertSame('A', $hindu->kelompok);

        $laporan = $siswa->laporanNilai();
        $namaA = collect($laporan['X']['kelompok']['A'] ?? [])->map(fn ($r) => $r['mapel']->nama);
        $this->assertContains('Pendidikan Agama Hindu dan Budi Pekerti', $namaA);
    }

    public function test_salin_daftar_umum_lalu_cetak_memakai_daftar_khusus_jurusan(): void
    {
        $admin = User::where('email', 'admin@smkn5.sch.id')->firstOrFail();
        $siswa = Siswa::whereNotNull('kelas_id')->firstOrFail();
        $jurusan = $siswa->jurusan;

        $this->actingAs($admin)->post(route('admin.mapel.salin'), ['jurusan_id' => $jurusan->id])
            ->assertRedirect();
        $this->assertTrue(Mapel::where('jurusan_id', $jurusan->id)->where('kelompok', 'C3')->exists());

        // Ubah satu mapel salinan dari nama umumnya.
        $khusus = Mapel::where('jurusan_id', $jurusan->id)
            ->where('nama', 'Produk Kreatif dan Kewirausahaan')->firstOrFail();
        $khusus->update(['nama' => 'Produk Kreatif Khusus']);

        $laporan = $siswa->laporanNilai();
        $namaC3 = collect($laporan['X']['kelompok']['C3'] ?? [])->map(fn ($r) => $r['mapel']->nama);
        $this->assertContains('Produk Kreatif Khusus', $namaC3);
        $this->assertNotContains('Produk Kreatif dan Kewirausahaan', $namaC3);
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

    public function test_mapel_khusus_jurusan_tidak_menghapus_mapel_umum_di_cetakan(): void
    {
        // Regresi dari import Leger 2021-2022: jurusan yang punya C2/C3
        // sendiri (mis. DPIB) tetap mencetak mapel umum yang memang
        // diambilnya (Produk Kreatif), dan TIDAK mencetak mapel khusus
        // jurusan lain (Penerapan Rangkaian = khusus TEI).
        $admin = User::where('email', 'admin@smkn5.sch.id')->firstOrFail();
        $jurusan = Jurusan::where('kode', 'TKJ')->firstOrFail();
        $siswa = Siswa::whereNotNull('kelas_id')->firstOrFail();
        $siswa->update(['jurusan_id' => $jurusan->id]);

        // Mapel khusus jurusan gaya import Leger (nama berbeda, bukan salinan).
        Mapel::create([
            'nama' => 'Estimasi Biaya Konstruksi', 'kelompok' => 'C3',
            'jurusan_id' => $jurusan->id, 'tingkat' => null, 'urutan' => 22, 'kkm_default' => 76,
        ]);

        $laporan = $siswa->laporanNilai();
        $namaC3 = collect($laporan['XII']['kelompok']['C3'] ?? [])->map(fn ($r) => $r['mapel']->nama);
        $this->assertContains('Produk Kreatif dan Kewirausahaan', $namaC3);
        $this->assertContains('Estimasi Biaya Konstruksi', $namaC3);
        $this->assertNotContains('Penerapan Rangkaian Elektronika', $namaC3);
    }

    public function test_guru_tidak_bisa_membuka_mapel_per_jurusan(): void
    {
        $guru = User::where('email', 'guru@smkn5.sch.id')->firstOrFail();
        $this->actingAs($guru)->get(route('admin.mapel.perjurusan'))->assertForbidden();
    }
}
