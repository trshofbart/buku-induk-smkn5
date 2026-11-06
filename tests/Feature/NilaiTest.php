<?php

namespace Tests\Feature;

use App\Models\Mapel;
use App\Models\Nilai;
use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class NilaiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_predikat_mengikuti_skala_sekolah_dari_file_master(): void
    {
        $this->assertSame('A', Nilai::predikat(92));
        $this->assertSame('A', Nilai::predikat(98));
        $this->assertSame('B', Nilai::predikat(91));
        $this->assertSame('B', Nilai::predikat(84));
        $this->assertSame('C', Nilai::predikat(83));
        $this->assertSame('C', Nilai::predikat(76));
        $this->assertSame('D', Nilai::predikat(75));
        $this->assertNull(Nilai::predikat(null));
    }

    public function test_admin_dapat_menginput_nilai_dan_rata_rata_ledger_terhitung(): void
    {
        $admin = User::where('email', 'admin@smkn5.sch.id')->firstOrFail();
        $siswa = Siswa::whereNotNull('kelas_id')->firstOrFail();
        $mapel = Mapel::untukJurusan($siswa->jurusan_id)->untukTingkat('X')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.nilai.store'), [
            'tahun_ajaran_id' => $siswa->tahun_ajaran_id,
            'semester' => 1,
            'mapel_id' => $mapel->id,
            'nilai' => [$siswa->id => ['kkm' => 76, 'angka' => 85]],
        ])->assertRedirect();

        $this->assertDatabaseHas('nilai', [
            'siswa_id' => $siswa->id,
            'mapel_id' => $mapel->id,
            'semester' => 1,
            'nilai_angka' => 85,
        ]);
        // Rata-rata ledger = rata-rata nilai mapel semester itu.
        $this->assertDatabaseHas('ledger', [
            'siswa_id' => $siswa->id,
            'semester' => 1,
            'rata_rata' => 85.00,
        ]);
    }

    public function test_halaman_input_nilai_dan_transkrip_tampil(): void
    {
        $admin = User::where('email', 'admin@smkn5.sch.id')->firstOrFail();
        $siswa = Siswa::whereNotNull('kelas_id')->firstOrFail();

        $this->actingAs($admin)->get(route('admin.nilai.index'))->assertOk();
        $this->actingAs($admin)->get(route('admin.nilai.index', [
            'tahun_ajaran_id' => $siswa->tahun_ajaran_id,
            'semester' => 1,
            'kelas_id' => $siswa->kelas_id,
        ]))->assertOk();
        $this->actingAs($admin)->get(route('admin.nilai.transkrip', $siswa))
            ->assertOk()
            ->assertSee('Laporan Penilaian');
    }

    public function test_guru_tidak_bisa_menyimpan_nilai(): void
    {
        $guru = User::where('email', 'guru@smkn5.sch.id')->firstOrFail();
        $siswa = Siswa::whereNotNull('kelas_id')->firstOrFail();
        $mapel = Mapel::firstOrFail();

        $this->actingAs($guru)->post(route('admin.nilai.store'), [
            'tahun_ajaran_id' => $siswa->tahun_ajaran_id,
            'semester' => 1,
            'mapel_id' => $mapel->id,
            'nilai' => [$siswa->id => ['angka' => 90]],
        ])->assertForbidden();
    }
}
