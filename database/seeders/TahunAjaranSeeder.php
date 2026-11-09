<?php

namespace Database\Seeders;

use App\Models\TahunAjaran;
use Illuminate\Database\Seeder;

/**
 * Tahun ajaran sesuai struktur folder Drive referensi:
 * 2011-2012 s.d. 2021-2022 + 2025-2026. Tahun 2011-2012 dan
 * 2012-2013 dibutuhkan karena kohort lulus 2013-2014 menempuh
 * X di 2011-2012 dan XI di 2012-2013. Tahun 2022-2023,
 * 2023-2024, 2024-2025 sengaja dibuat juga di sini supaya
 * tidak ada kekosongan di aplikasi (di Drive belum ada foldernya).
 */
class TahunAjaranSeeder extends Seeder
{
    public function run(): void
    {
        $tahun = [
            '2011-2012', '2012-2013',
            '2013-2014', '2014-2015', '2015-2016', '2016-2017',
            '2017-2018', '2018-2019', '2019-2020', '2020-2021',
            '2021-2022', '2022-2023', '2023-2024', '2024-2025',
            '2025-2026',
        ];

        foreach ($tahun as $nama) {
            [$mulai] = explode('-', $nama);
            TahunAjaran::create([
                'nama' => $nama,
                'is_active' => $nama === '2025-2026',
                'tanggal_mulai' => $mulai.'-07-01',
                'tanggal_selesai' => ((int) $mulai + 1).'-06-30',
            ]);
        }
    }
}
