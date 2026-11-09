<?php

namespace Tests\Feature;

use App\Models\DataSekolah;
use App\Models\Kelengkapan;
use App\Models\Mapel;
use App\Models\Nilai;
use App\Models\Siswa;
use App\Models\TahunAjaran;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Alur pengisian nilai ala e-Rapor SMK 2025.1 yang diadaptasi untuk
 * aplikasi ini (keputusan Bart 2026-11-07): KKM tetap, tanpa TP,
 * tanpa predikat A-D, tanpa tarik data Dapodik (import manual),
 * pengisian nilai hanya Admin TU. Tahun ajaran per tingkat mengikuti
 * isian nilai tingkat itu.
 */
class RaporAlurTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    private function admin(): User
    {
        return User::where('email', 'admin@smkn5.sch.id')->firstOrFail();
    }

    private function siswaDenganKelas(): Siswa
    {
        return Siswa::whereNotNull('kelas_id')->firstOrFail();
    }

    public function test_tahun_ajaran_per_tingkat_mengikuti_isian_nilai(): void
    {
        $siswa = $this->siswaDenganKelas();
        $mapelX = Mapel::untukJurusan($siswa->jurusan_id)->untukTingkat('X')->firstOrFail();
        $mapelXI = Mapel::untukJurusan($siswa->jurusan_id)->untukTingkat('XI')->firstOrFail();
        $mapelXII = Mapel::untukJurusan($siswa->jurusan_id)->untukTingkat('XII')->firstOrFail();

        // Contoh Bart: lulus 2025/2026 → X 2022/2023, XI 2023/2024, XII 2025/2026.
        $t22 = TahunAjaran::where('nama', '2022-2023')->firstOrFail();
        $t23 = TahunAjaran::where('nama', '2023-2024')->firstOrFail();
        $t25 = TahunAjaran::where('nama', '2025-2026')->firstOrFail();

        foreach ([1, 2] as $smt) {
            Nilai::create(['siswa_id' => $siswa->id, 'mapel_id' => $mapelX->id, 'tahun_ajaran_id' => $t22->id, 'semester' => $smt, 'kkm' => 76, 'nilai_angka' => 80]);
        }
        foreach ([3, 4] as $smt) {
            Nilai::create(['siswa_id' => $siswa->id, 'mapel_id' => $mapelXI->id, 'tahun_ajaran_id' => $t23->id, 'semester' => $smt, 'kkm' => 76, 'nilai_angka' => 82]);
        }
        foreach ([5, 6] as $smt) {
            Nilai::create(['siswa_id' => $siswa->id, 'mapel_id' => $mapelXII->id, 'tahun_ajaran_id' => $t25->id, 'semester' => $smt, 'kkm' => 76, 'nilai_angka' => 84]);
        }

        $laporan = $siswa->fresh()->laporanNilai();
        $this->assertSame('2022-2023', $laporan['X']['tahun']);
        $this->assertSame('2023-2024', $laporan['XI']['tahun']);
        $this->assertSame('2025-2026', $laporan['XII']['tahun']);
    }

    public function test_tahun_tingkat_tanpa_nilai_tidak_mengambil_tahun_ledger(): void
    {
        $siswa = $this->siswaDenganKelas();
        // Ledger dengan tahun kohort untuk semua semester, tanpa nilai.
        $siswa->ledger()->delete();
        foreach (range(1, 6) as $smt) {
            $siswa->ledger()->create([
                'tahun_ajaran_id' => $siswa->tahun_ajaran_id,
                'semester' => $smt,
                'rata_rata' => 80,
            ]);
        }

        $laporan = $siswa->fresh()->laporanNilai();
        // Tahun kosong (—) lebih jujur daripada tahun kohort yang salah.
        $this->assertNull($laporan['X']['tahun']);
        $this->assertNull($laporan['XI']['tahun']);
        $this->assertNull($laporan['XII']['tahun']);
    }

    public function test_input_nilai_terkunci_saat_ditutup_dan_terbuka_lagi_lewat_toggle(): void
    {
        $admin = $this->admin();
        $siswa = $this->siswaDenganKelas();
        $mapel = Mapel::untukJurusan($siswa->jurusan_id)->untukTingkat('X')->firstOrFail();

        DataSekolah::get()->update(['input_nilai_dibuka' => false]);

        $this->actingAs($admin)->post(route('admin.nilai.store'), [
            'tahun_ajaran_id' => $siswa->tahun_ajaran_id,
            'semester' => 1,
            'mapel_id' => $mapel->id,
            'nilai' => [$siswa->id => ['kkm' => 76, 'angka' => 85]],
        ])->assertForbidden();
        $this->assertDatabaseMissing('nilai', ['siswa_id' => $siswa->id, 'mapel_id' => $mapel->id]);

        // Buka lagi lewat toggle, lalu simpan berhasil.
        $this->actingAs($admin)->post(route('admin.nilai.toggle-input'))->assertRedirect();
        $this->assertTrue(DataSekolah::get()->input_nilai_dibuka);
        $this->actingAs($admin)->post(route('admin.nilai.store'), [
            'tahun_ajaran_id' => $siswa->tahun_ajaran_id,
            'semester' => 1,
            'mapel_id' => $mapel->id,
            'nilai' => [$siswa->id => ['kkm' => 76, 'angka' => 85]],
        ])->assertRedirect();
        $this->assertDatabaseHas('nilai', ['siswa_id' => $siswa->id, 'mapel_id' => $mapel->id, 'nilai_angka' => 85]);
    }

    public function test_kelengkapan_tersimpan_dan_tampil_di_cetak(): void
    {
        $admin = $this->admin();
        $siswa = $this->siswaDenganKelas();
        $tahun = TahunAjaran::where('nama', '2022-2023')->firstOrFail();

        $this->actingAs($admin)->post(route('admin.kelengkapan.store'), [
            'tahun_ajaran_id' => $tahun->id,
            'semester' => 1,
            'kelengkapan' => [$siswa->id => [
                'sakit' => 2, 'izin' => 1, 'alpa' => 0,
                'kelakuan' => 'Baik', 'kerapian' => 'Baik', 'kerajinan' => 'Amat Baik',
                'akhlak' => 'Baik', 'catatan' => 'Pertahankan prestasinya.',
                'kenaikan' => 'Naik ke Tingkat XI',
            ]],
        ])->assertRedirect();
        $this->assertDatabaseHas('kelengkapan', [
            'siswa_id' => $siswa->id, 'semester' => 1, 'sakit' => 2, 'kenaikan' => 'Naik ke Tingkat XI',
        ]);

        // Ekstrakurikuler.
        $this->actingAs($admin)->post(route('admin.kelengkapan.ekstrakurikuler.store'), [
            'tahun_ajaran_id' => $tahun->id,
            'semester' => 1,
            'siswa_id' => $siswa->id,
            'kegiatan' => 'Pramuka',
            'nilai' => 'Baik',
            'keterangan' => 'Aktif',
        ])->assertRedirect();
        $this->assertDatabaseHas('ekstrakurikuler', ['siswa_id' => $siswa->id, 'kegiatan' => 'Pramuka']);

        // Cetak menampilkan kelengkapan + ekskul, tanpa kolom HURUF,
        // dengan bidang Teknologi dan Rekayasa.
        $res = $this->actingAs($admin)->get(route('admin.siswa.cetak', $siswa))->assertOk();
        $html = $res->getContent();
        $this->assertStringContainsString('Pramuka', $html);
        $this->assertStringContainsString('Pertahankan prestasinya.', $html);
        $this->assertStringContainsString('Naik ke Tingkat XI', $html);
        // Bidang ditampilkan huruf besar di cetakan.
        $this->assertStringContainsString('TEKNOLOGI DAN REKAYASA', $html);
        $this->assertStringNotContainsString('HURUF', $html);
        $this->assertStringNotContainsString('nilai_huruf', $html);
        // Nilai yang kosong tampil sebagai tanda strip (–), bukan teks
        // '&ndash;' (Blade meng-escape & di dalam {{ }}, jadi entitas
        // HTML tidak boleh dipakai di sana — pakai karakter langsung).
        $this->assertStringNotContainsString('&amp;ndash;', $html);
        $this->assertStringNotContainsString('&ndash;', $html);
        $this->assertStringContainsString('–', $html);
    }

    public function test_leger_excel_memuat_nilai_per_mapel(): void
    {
        $admin = $this->admin();
        $siswa = $this->siswaDenganKelas();
        $mapel = Mapel::untukJurusan($siswa->jurusan_id)->untukTingkat('X')->firstOrFail();
        Nilai::create([
            'siswa_id' => $siswa->id, 'mapel_id' => $mapel->id,
            'tahun_ajaran_id' => $siswa->tahun_ajaran_id, 'semester' => 1,
            'kkm' => 76, 'nilai_angka' => 88,
        ]);

        $res = $this->actingAs($admin)->get(route('admin.nilai.leger', [
            'tahun_ajaran_id' => $siswa->tahun_ajaran_id,
            'semester' => 1,
            'kelas_id' => $siswa->kelas_id,
        ]))->assertOk();
        $this->assertStringContainsString('.xlsx', $res->headers->get('Content-Disposition'));
        $baris = \App\Support\ExcelExport::baca($res->getContent());
        // Header memuat nama mapel + RATA-RATA; nilai 88 ada di selnya sendiri.
        $this->assertContains($mapel->nama, $baris[0]);
        $this->assertContains('RATA-RATA', $baris[0]);
        $kolomMapel = array_search($mapel->nama, $baris[0]);
        $barisSiswa = null;
        foreach (array_slice($baris, 1) as $r) {
            if (($r[1] ?? null) === $siswa->nis) {
                $barisSiswa = $r;
                break;
            }
        }
        $this->assertNotNull($barisSiswa);
        $this->assertSame($siswa->nama, $barisSiswa[3]);
        $this->assertEquals(88, $barisSiswa[$kolomMapel]);
    }

    public function test_rapor_semester_menampilkan_nilai_dan_kehadiran_semester_itu(): void
    {
        $admin = $this->admin();
        $siswa = $this->siswaDenganKelas();
        $tahun = TahunAjaran::where('nama', '2022-2023')->firstOrFail();
        $mapel = Mapel::untukJurusan($siswa->jurusan_id)->untukTingkat('X')->firstOrFail();
        Nilai::create([
            'siswa_id' => $siswa->id, 'mapel_id' => $mapel->id,
            'tahun_ajaran_id' => $tahun->id, 'semester' => 1,
            'kkm' => 76, 'nilai_angka' => 90,
        ]);
        Kelengkapan::create([
            'siswa_id' => $siswa->id, 'tahun_ajaran_id' => $tahun->id, 'semester' => 1,
            'sakit' => 3, 'izin' => 0, 'alpa' => 1, 'catatan' => 'Rajin.',
        ]);

        $res = $this->actingAs($admin)->get(route('admin.siswa.rapor', [$siswa, 'tahun_ajaran_id' => $tahun->id, 'semester' => 1]))->assertOk();
        $html = $res->getContent();
        $this->assertStringContainsString($mapel->nama, $html);
        $this->assertStringContainsString('90', $html);
        $this->assertStringContainsString('Rajin.', $html);
        $this->assertStringContainsString('2022-2023', $html);
        $this->assertStringNotContainsString('HURUF', $html);
        // Mapel tanpa nilai tampil sebagai strip (–), bukan teks '&ndash;'.
        $this->assertStringNotContainsString('&amp;ndash;', $html);
        $this->assertStringNotContainsString('&ndash;', $html);
        $this->assertStringContainsString('–', $html);
    }
}
