<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Dua perbaikan dari hasil import Leger 2021-2022:
     *
     * 1. Kolom salinan_dari_id — menandai mapel khusus jurusan yang
     *    merupakan SALINAN dari mapel umum (fitur "Salin daftar umum").
     *    Mapel salinan MENGGANTIKAN mapel umum asalnya di cetakan, walau
     *    namanya sudah diubah admin. Mapel khusus lain (mis. dari import
     *    Leger — namanya memang berbeda) DITAMBAHKAN di samping mapel
     *    umum, bukan menggantikannya (lihat Mapel::untukJurusanTingkat).
     *
     * 2. Mapel yang ternyata hanya dipakai TEI dipindah dari daftar umum
     *    menjadi khusus TEI. Bukti dari file Leger per kelas: 'Kerja
     *    Bengkel', 'T. Pemograman', 'Penerapan Rangkaian', 'Sistem
     *    Pengendali', 'Mikroprosesor', 'Pengendali Robotik', dan
     *    'Pembuatan Peralatan' hanya muncul di file TEI; 'Dasar Listrik'
     *    tetap umum (dipakai TEI, TITL, TOI) dan 'Produk Kreatif' tetap
     *    umum (dipakai semua jurusan).
     */
    public function up(): void
    {
        Schema::table('mapel', function (Blueprint $table) {
            $table->foreignId('salinan_dari_id')->nullable()->after('jurusan_id')
                ->constrained('mapel')->nullOnDelete();
        });

        $tei = DB::table('jurusan')->where('kode', 'TEI')->value('id');
        if ($tei) {
            DB::table('mapel')->whereNull('jurusan_id')->whereIn('nama', [
                'Kerja Bengkel dan Gambar Teknik',
                'T. Pemograman. M. Prosesor dan M. Kontroler',
                'Penerapan Rangkaian Elektronika',
                'Sistem Pengendali Elektronik',
                'Mikroprosesor dan Mikrokontroler',
                'Pengendali Sistem Robotik',
                'Pembuatan, Perbaik dan Pemel Peralatan Elektronika',
            ])->update(['jurusan_id' => $tei]);
        }
    }

    public function down(): void
    {
        $tei = DB::table('jurusan')->where('kode', 'TEI')->value('id');
        if ($tei) {
            DB::table('mapel')->where('jurusan_id', $tei)->whereIn('nama', [
                'Kerja Bengkel dan Gambar Teknik',
                'T. Pemograman. M. Prosesor dan M. Kontroler',
                'Penerapan Rangkaian Elektronika',
                'Sistem Pengendali Elektronik',
                'Mikroprosesor dan Mikrokontroler',
                'Pengendali Sistem Robotik',
                'Pembuatan, Perbaik dan Pemel Peralatan Elektronika',
            ])->update(['jurusan_id' => null]);
        }

        Schema::table('mapel', function (Blueprint $table) {
            $table->dropConstrainedForeignId('salinan_dari_id');
        });
    }
};
