<?php

namespace Tests\Feature;

use Tests\TestCase;

class LoginTest extends TestCase
{
    public function test_halaman_login_tidak_menampilkan_keterangan_akun(): void
    {
        $res = $this->get(route('login'));
        $res->assertOk();
        $res->assertDontSee('Akun contoh');
        $res->assertDontSee('admin@smkn5.sch.id');
        $res->assertDontSee('kepsek@smkn5.sch.id');
        $res->assertDontSee('guru@smkn5.sch.id');
        // Petunjuk yang benar tetap ada: akun dibuat oleh Admin TU.
        $res->assertSee('akun dibuat oleh Admin TU');
    }
}
