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

    public function test_menu_ledger_dan_transkrip_sudah_dihapus(): void
    {
        $admin = User::where('email', 'admin@smkn5.sch.id')->firstOrFail();
        $this->actingAs($admin)->get('/admin/buku-induk/ledger')->assertNotFound();
        $this->actingAs($admin)->get('/admin/buku-induk/transkrip')->assertNotFound();
    }

    public function test_template_csv_sesuai_kolom_file_master(): void
    {
        $admin = User::where('email', 'admin@smkn5.sch.id')->firstOrFail();
        $siswa = Siswa::whereNotNull('kelas_id')->firstOrFail();

        $res = $this->actingAs($admin)->get(route('admin.nilai.template', [
            'tahun_ajaran_id' => $siswa->tahun_ajaran_id,
            'semester' => 1,
            'kelas_id' => $siswa->kelas_id,
        ]));
        $res->assertOk();
        $isi = $res->getContent();
        $this->assertStringContainsString('NO', $isi);
        $this->assertStringContainsString('NIS', $isi);
        $this->assertStringContainsString('NAMA', $isi);
        $this->assertStringContainsString($siswa->nis, $isi);
        $this->assertStringContainsString($siswa->nama, $isi);
    }

    public function test_import_csv_mengikuti_kolom_file_master(): void
    {
        $admin = User::where('email', 'admin@smkn5.sch.id')->firstOrFail();
        $siswa = Siswa::whereNotNull('kelas_id')->firstOrFail();
        $siswa2 = Siswa::where('kelas_id', $siswa->kelas_id)->where('id', '!=', $siswa->id)->firstOrFail();
        $mapel = Mapel::untukJurusanTingkat($siswa->jurusan_id, 'X')->take(2);
        [$m1, $m2] = [$mapel[0], $mapel[1]];

        $csv = "NO;NIS;NISN;NAMA;{$m1->nama};{$m2->nama}\n"
            . "1;{$siswa->nis};{$siswa->nisn};{$siswa->nama};80;90\n"
            . "2;99999;;Tidak Ada;70;70\n"
            . "3;{$siswa2->nis};{$siswa2->nisn};{$siswa2->nama};abc;75\n";
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('nilai.csv', $csv);

        $res = $this->actingAs($admin)->post(route('admin.nilai.import'), [
            'tahun_ajaran_id' => $siswa->tahun_ajaran_id,
            'semester' => 1,
            'kelas_id' => $siswa->kelas_id,
            'file' => $file,
        ]);
        $res->assertRedirect();
        $res->assertSessionHas('status');
        $this->assertStringContainsString('3 nilai masuk', session('status'));

        $this->assertDatabaseHas('nilai', ['siswa_id' => $siswa->id, 'mapel_id' => $m1->id, 'semester' => 1, 'nilai_angka' => 80]);
        $this->assertDatabaseHas('nilai', ['siswa_id' => $siswa->id, 'mapel_id' => $m2->id, 'semester' => 1, 'nilai_angka' => 90]);
        $this->assertDatabaseHas('nilai', ['siswa_id' => $siswa2->id, 'mapel_id' => $m2->id, 'semester' => 1, 'nilai_angka' => 75]);
        // Rata-rata ledger siswa pertama = (80+90)/2 = 85.00.
        $this->assertDatabaseHas('ledger', ['siswa_id' => $siswa->id, 'semester' => 1, 'rata_rata' => 85.00]);
        $this->assertCount(2, session('import_galat'));
    }

    public function test_guru_tidak_bisa_mengimport_nilai(): void
    {
        $guru = User::where('email', 'guru@smkn5.sch.id')->firstOrFail();
        $siswa = Siswa::whereNotNull('kelas_id')->firstOrFail();
        $file = \Illuminate\Http\UploadedFile::fake()->createWithContent('nilai.csv', "NO;NIS\n1;{$siswa->nis}\n");

        $this->actingAs($guru)->post(route('admin.nilai.import'), [
            'tahun_ajaran_id' => $siswa->tahun_ajaran_id,
            'semester' => 1,
            'kelas_id' => $siswa->kelas_id,
            'file' => $file,
        ])->assertForbidden();
    }

    public function test_mapel_khusus_jurusan_menggantikan_mapel_umum_di_kelompok_yang_sama(): void
    {
        $siswa = Siswa::whereNotNull('kelas_id')->firstOrFail();
        Mapel::create([
            'nama' => 'Elektronika Khusus',
            'kelompok' => 'C3',
            'jurusan_id' => $siswa->jurusan_id,
            'tingkat' => 'X',
            'urutan' => 1,
            'kkm_default' => 76,
        ]);

        $laporan = $siswa->laporanNilai();
        $c3 = $laporan['X']['kelompok']['C3'] ?? [];
        $this->assertCount(1, $c3);
        $this->assertSame('Elektronika Khusus', $c3[0]['mapel']->nama);
        // Kelompok lain tetap memakai mapel umum.
        $this->assertNotEmpty($laporan['X']['kelompok']['A'] ?? []);
    }

    public function test_cetak_buku_induk_transkrip_beda_halaman_tiap_tahun_ajaran(): void
    {
        $admin = User::where('email', 'admin@smkn5.sch.id')->firstOrFail();
        $siswa = Siswa::whereNotNull('kelas_id')->firstOrFail();

        $res = $this->actingAs($admin)->get(route('admin.siswa.cetak', $siswa))->assertOk();
        // 1 ganti halaman sebelum Laporan Penilaian + 1 sebelum XI + 1 sebelum XII
        // + 1 sebelum Ekstrakurikuler (terpisah dari transkrip nilai).
        $this->assertSame(4, substr_count($res->getContent(), '<div class="halaman-baru">'));
    }
}
