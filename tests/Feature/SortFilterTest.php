<?php

namespace Tests\Feature;

use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SortFilterTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    /** Pastikan $pertama muncul sebelum $kedua di isi halaman. */
    protected function assertUrutan(string $content, string $pertama, string $kedua): void
    {
        $p1 = strpos($content, $pertama);
        $p2 = strpos($content, $kedua);
        $this->assertNotFalse($p1, "$pertama tidak ditemukan di halaman");
        $this->assertNotFalse($p2, "$kedua tidak ditemukan di halaman");
        $this->assertLessThan($p2, $p1, "$pertama seharusnya muncul sebelum $kedua");
    }

    public function test_siswa_diurutkan_berdasarkan_nama_naik_turun(): void
    {
        $admin = User::where('email', 'admin@smkn5.sch.id')->firstOrFail();
        Siswa::factory()->create(['nis' => '99990001', 'nama' => 'Aaa Sorttest']);
        Siswa::factory()->create(['nis' => '99990002', 'nama' => 'Zzz Sorttest']);

        // q=Sorttest menyaring hanya kedua siswa ini (satu halaman).
        $asc = $this->actingAs($admin)
            ->get(route('admin.siswa.index', ['q' => 'Sorttest', 'sort' => 'nama', 'dir' => 'asc']))
            ->assertOk();
        $this->assertUrutan($asc->getContent(), 'Aaa Sorttest', 'Zzz Sorttest');

        $desc = $this->actingAs($admin)
            ->get(route('admin.siswa.index', ['q' => 'Sorttest', 'sort' => 'nama', 'dir' => 'desc']))
            ->assertOk();
        $this->assertUrutan($desc->getContent(), 'Zzz Sorttest', 'Aaa Sorttest');
    }

    public function test_siswa_diurutkan_berdasarkan_nis(): void
    {
        $admin = User::where('email', 'admin@smkn5.sch.id')->firstOrFail();
        Siswa::factory()->create(['nis' => '99990001', 'nama' => 'Aaa Sorttest']);
        Siswa::factory()->create(['nis' => '99990002', 'nama' => 'Zzz Sorttest']);

        $asc = $this->actingAs($admin)
            ->get(route('admin.siswa.index', ['q' => 'Sorttest', 'sort' => 'nis', 'dir' => 'asc']))
            ->assertOk();
        $this->assertUrutan($asc->getContent(), '99990001', '99990002');

        $desc = $this->actingAs($admin)
            ->get(route('admin.siswa.index', ['q' => 'Sorttest', 'sort' => 'nis', 'dir' => 'desc']))
            ->assertOk();
        $this->assertUrutan($desc->getContent(), '99990002', '99990001');
    }

    public function test_sort_tidak_valid_kembali_ke_default_tanpa_error(): void
    {
        $admin = User::where('email', 'admin@smkn5.sch.id')->firstOrFail();
        $this->actingAs($admin)
            ->get(route('admin.siswa.index', ['sort' => 'kolom_ngawur', 'dir' => 'desc']))
            ->assertOk();
        $this->actingAs($admin)
            ->get(route('admin.mapel.index', ['sort' => 'kolom_ngawur']))
            ->assertOk();
    }

    public function test_mapel_diurutkan_berdasarkan_urutan_dan_difilter_nama(): void
    {
        $admin = User::where('email', 'admin@smkn5.sch.id')->firstOrFail();

        // Fisika urutan 11, Kimia urutan 12 (seed).
        $asc = $this->actingAs($admin)
            ->get(route('admin.mapel.index', ['sort' => 'urutan', 'dir' => 'asc']))
            ->assertOk();
        $this->assertUrutan($asc->getContent(), 'Fisika', 'Kimia');

        $desc = $this->actingAs($admin)
            ->get(route('admin.mapel.index', ['sort' => 'urutan', 'dir' => 'desc']))
            ->assertOk();
        $this->assertUrutan($desc->getContent(), 'Kimia', 'Fisika');

        // Filter nama: hanya Fisika yang tampil.
        $this->actingAs($admin)->get(route('admin.mapel.index', ['q' => 'Fisika']))
            ->assertOk()
            ->assertSee('Fisika')
            ->assertDontSee('Kimia');
    }

    public function test_pengguna_diurutkan_berdasarkan_email(): void
    {
        $admin = User::where('email', 'admin@smkn5.sch.id')->firstOrFail();

        $asc = $this->actingAs($admin)
            ->get(route('admin.pengguna.index', ['sort' => 'email', 'dir' => 'asc']))
            ->assertOk();
        $this->assertUrutan($asc->getContent(), 'admin@smkn5.sch.id', 'guru@smkn5.sch.id');

        $desc = $this->actingAs($admin)
            ->get(route('admin.pengguna.index', ['sort' => 'email', 'dir' => 'desc']))
            ->assertOk();
        $this->assertUrutan($desc->getContent(), 'kepsek@smkn5.sch.id', 'guru@smkn5.sch.id');
    }

    public function test_mapel_per_jurusan_bisa_difilter_nama(): void
    {
        $admin = User::where('email', 'admin@smkn5.sch.id')->firstOrFail();

        $this->actingAs($admin)->get(route('admin.mapel.perjurusan', ['q' => 'Fisika']))
            ->assertOk()
            ->assertSee('Fisika')
            ->assertDontSee('Kimia');
    }

    public function test_log_diurutkan_berdasarkan_waktu(): void
    {
        $admin = User::where('email', 'admin@smkn5.sch.id')->firstOrFail();
        $this->actingAs($admin)->get(route('admin.log.index', ['sort' => 'created_at', 'dir' => 'asc']))
            ->assertOk();
        $this->actingAs($admin)->get(route('admin.log.index', ['sort' => 'aksi', 'dir' => 'desc']))
            ->assertOk();
    }

    public function test_pilihan_jumlah_data_per_halaman(): void
    {
        $admin = User::where('email', 'admin@smkn5.sch.id')->firstOrFail();

        // Siswa: per_page=50 dipakai, pilihan muncul di halaman.
        $this->actingAs($admin)->get(route('admin.siswa.index', ['per_page' => 50]))
            ->assertOk()
            ->assertSee('Tampilkan')
            ->assertViewHas('siswa', fn ($p) => $p->perPage() === 50);

        // per_page di luar pilihan kembali ke default (15).
        $this->actingAs($admin)->get(route('admin.siswa.index', ['per_page' => 999]))
            ->assertOk()
            ->assertViewHas('siswa', fn ($p) => $p->perPage() === 15);

        // Mapel: default 25, per_page=100 dipakai.
        $this->actingAs($admin)->get(route('admin.mapel.index'))
            ->assertOk()
            ->assertViewHas('mapel', fn ($p) => $p->perPage() === 25);
        $this->actingAs($admin)->get(route('admin.mapel.index', ['per_page' => 100]))
            ->assertOk()
            ->assertViewHas('mapel', fn ($p) => $p->perPage() === 100);
    }
}
