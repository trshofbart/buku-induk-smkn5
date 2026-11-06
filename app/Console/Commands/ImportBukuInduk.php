<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;

/**
 * Import data lama Buku Induk SMKN 5 Surakarta dari file Excel (Google Drive)
 * ke database-imported.sqlite.
 *
 * Pembungkus (wrapper) untuk script Python:
 *   storage/imports/import_buku_induk.py
 * yang berisi seluruh logika pemetaan kolom, dedup NIS, dan penulisan SQLite.
 *
 * Pemakaian:
 *   php artisan buku-induk:import                  # import semua tahun
 *   php artisan buku-induk:import --dry-run        # simulasi, DB tidak berubah
 *   php artisan buku-induk:import --tahun=2019-2020
 *   php artisan buku-induk:import --file=2021-2022/DATA...xlsx --tahun=2021-2022
 *   php artisan buku-induk:import --fresh          # buat ulang database-imported.sqlite
 *
 * PERINGATAN PRIVASI: file sumber di storage/imports/source/ dan
 * database-imported.sqlite berisi data asli siswa. Jangan diunggah ke hosting
 * publik / dikirim ke pihak luar tanpa izin sekolah.
 */
class ImportBukuInduk extends Command
{
    protected $signature = 'buku-induk:import
        {--dry-run : Jalankan import penuh terhadap salinan sementara, lalu buang hasilnya}
        {--fresh : Buat ulang database-imported.sqlite dari database.sqlite (data contoh dihapus dari salinan)}
        {--tahun= : Hanya tahun ajaran ini, mis. 2019-2020}
        {--file= : Hanya file sumber ini (path relatif terhadap storage/imports/source)}
        {--db= : Path DB SQLite (default: database/database-imported.sqlite)}';

    protected $description = 'Import data lama Buku Induk SMKN 5 dari file Excel Drive ke SQLite';

    public function handle(): int
    {
        $script = base_path('storage/imports/import_buku_induk.py');

        if (! file_exists($script)) {
            $this->error("Script importer tidak ditemukan: {$script}");
            return self::FAILURE;
        }

        $args = ['python3', $script];

        foreach (['dry-run', 'fresh'] as $flag) {
            if ($this->option($flag)) {
                $args[] = "--{$flag}";
            }
        }

        foreach (['tahun', 'file', 'db'] as $opt) {
            if ($this->option($opt)) {
                $args[] = "--{$opt}={$this->option($opt)}";
            }
        }

        $process = new Process($args, base_path());
        $process->setTimeout(1200); // import penuh ~15 detik; beri ruang 20 menit
        $process->run(function ($type, $buffer) {
            $this->output->write($buffer);
        });

        if (! $process->isSuccessful()) {
            $this->error('Import gagal (lihat output di atas).');
            return self::FAILURE;
        }

        $this->info('Import selesai. Lihat LAPORAN_IMPORT.md untuk ringkasan agregat.');
        return self::SUCCESS;
    }
}
