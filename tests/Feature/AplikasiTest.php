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
