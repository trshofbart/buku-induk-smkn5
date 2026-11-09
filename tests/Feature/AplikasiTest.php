<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AplikasiTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Seeder tidak lagi hardcode password: di test, isi lewat env
        // supaya login contoh di bawah tetap memakai 'password'.
        putenv('SEED_ADMIN_PASSWORD=password');
        putenv('SEED_KEPSEK_PASSWORD=password');
        putenv('SEED_GURU_PASSWORD=password');
        $this->seed();
    }

    public function test_halaman_depan_tampil_dengan_berita_contoh(): void
    {
        $response = $this->get('/');
        $response->assertOk();
        $response->assertSee('SMK Negeri 5 Surakarta');
    }

    public function test_halaman_berita_dan_profil_tampil(): void
    {
        $this->get('/berita')->assertOk();
        $this->get('/profil')->assertOk();
        $this->get('/profil/sejarah')->assertOk();
    }

    public function test_halaman_admin_wajib_login(): void
    {
        $this->get('/admin')->assertRedirect('/login');
        $this->get('/admin/siswa')->assertRedirect('/login');
        $this->get('/admin/buku-induk')->assertRedirect('/login');
    }

    public function test_login_admin_contoh_berhasil_dan_dashboard_tampil(): void
    {
        $admin = User::where('email', 'admin@smkn5.sch.id')->first();
        $this->assertNotNull($admin);

        $this->post('/login', ['email' => 'admin@smkn5.sch.id', 'password' => 'password'])
            ->assertRedirect(route('admin.dashboard'));

        $this->actingAs($admin)->get('/admin')->assertOk();
        $this->actingAs($admin)->get('/admin/siswa')->assertOk();
        $this->actingAs($admin)->get('/admin/buku-induk')->assertOk();
    }

    public function test_sidebar_admin_fixed_saat_scroll(): void
    {
        $admin = User::where('email', 'admin@smkn5.sch.id')->firstOrFail();
        $res = $this->actingAs($admin)->get('/admin')->assertOk();
        // Sidebar menempel di atas viewport setinggi layar di desktop, menu bisa di-scroll sendiri.
        $res->assertSee('md:sticky', false);
        $res->assertSee('top-0 h-screen', false);
        $res->assertSee('overflow-y-auto', false);
    }

    public function test_menu_mobile_jadi_sidebar_yang_bisa_disembunyikan(): void
    {
        $admin = User::where('email', 'admin@smkn5.sch.id')->firstOrFail();
        $html = $this->actingAs($admin)->get('/admin')->assertOk()->getContent();
        // Sidebar jadi drawer: tersembunyi di HP (-translate-x-full), terlihat di desktop.
        $this->assertStringContainsString('id="sidebar"', $html);
        $this->assertStringContainsString('-translate-x-full', $html);
        $this->assertStringContainsString('md:translate-x-0', $html);
        // Tombol hamburger, tombol tutup, dan latar penutup ada.
        $this->assertStringContainsString('id="sidebar-open"', $html);
        $this->assertStringContainsString('id="sidebar-close"', $html);
        $this->assertStringContainsString('id="sidebar-backdrop"', $html);
        // Navigasi bawah HP sudah dihapus.
        $this->assertStringNotContainsString('fixed bottom-0', $html);
    }

    public function test_sidebar_punya_submenu_yang_bisa_dilipat(): void
    {
        $admin = User::where('email', 'admin@smkn5.sch.id')->firstOrFail();
        $html = $this->actingAs($admin)->get('/admin')->assertOk()->getContent();
        // Tiga parent (Buku Induk & Siswa, Konten Publik, Master & Pengguna).
        // (Dihitung lewat aria-controls, karena string data-submenu-toggle
        // juga muncul sekali di kode JS penutupnya.)
        $this->assertSame(3, substr_count($html, 'aria-controls="submenu-')); 
        $this->assertStringContainsString('Buku Induk &amp; Siswa', $html);
        $this->assertStringContainsString('Konten Publik', $html);
        $this->assertStringContainsString('Master &amp; Pengguna', $html);
        // Di dashboard tidak ada anak yang aktif: semua submenu tertutup.
        $this->assertSame(3, substr_count($html, 'aria-expanded="false"'));
    }

    public function test_submenu_parent_terbuka_saat_halaman_anaknya_aktif(): void
    {
        $admin = User::where('email', 'admin@smkn5.sch.id')->firstOrFail();
        $html = $this->actingAs($admin)->get('/admin/siswa')->assertOk()->getContent();
        // Hanya parent "Buku Induk & Siswa" yang terbuka; dua lainnya tertutup.
        $this->assertSame(1, substr_count($html, 'aria-expanded="true"'));
        $this->assertSame(2, substr_count($html, 'aria-expanded="false"'));
        // Link anak yang aktif ditandai.
        $this->assertStringContainsString('bg-green-800 font-semibold', $html);
    }

    public function test_guru_tidak_bisa_membuat_siswa(): void
    {
        $guru = User::where('email', 'guru@smkn5.sch.id')->first();
        $this->actingAs($guru)
            ->post('/admin/siswa', [])
            ->assertForbidden();
    }

    public function test_pencarian_siswa_berdasarkan_nis(): void
    {
        $admin = User::where('email', 'admin@smkn5.sch.id')->first();
        $this->actingAs($admin)
            ->get('/admin/siswa?q=20250001')
            ->assertOk()
            ->assertSee('Andi Pratama');
    }
}
