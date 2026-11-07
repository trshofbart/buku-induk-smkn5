<?php

namespace Tests\Feature;

use App\Models\Siswa;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ExportExcelTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
    }

    public function test_export_siswa_menghasilkan_xlsx_dengan_kolom_terpisah(): void
    {
        $admin = User::where('email', 'admin@smkn5.sch.id')->firstOrFail();
        $siswa = Siswa::firstOrFail();

        $res = $this->actingAs($admin)->get(route('admin.siswa.export'));
        $res->assertOk();
        $this->assertStringContainsString('.xlsx', $res->headers->get('Content-Disposition'));

        $baris = \App\Support\ExcelExport::baca($res->getContent());
        $this->assertSame(
            ['NIS', 'NISN', 'Nama', 'JK', 'Tempat Lahir', 'Tanggal Lahir', 'Agama', 'Alamat', 'No HP', 'Tahun Ajaran', 'Jurusan', 'Kelas', 'Status'],
            $baris[0]
        );
        $ditemukan = false;
        foreach (array_slice($baris, 1) as $r) {
            if (($r[0] ?? null) === $siswa->nis) {
                $this->assertSame($siswa->nama, $r[2]);
                $ditemukan = true;
            }
        }
        $this->assertTrue($ditemukan, 'NIS siswa harus ada di barisnya sendiri');
    }
}
