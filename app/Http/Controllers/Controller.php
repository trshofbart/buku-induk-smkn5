<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

/**
 * Base controller — kelas dasar kosong seperti bawaan skeleton Laravel 12.
 * Semua controller aplikasi mewarisi kelas ini.
 */
abstract class Controller
{
    /**
     * Terapkan sort dari query string (?sort=kolom&dir=asc|desc) ke query
     * builder. Hanya kolom dalam $boleh yang diterima (mencegah sort
     * berdasarkan kolom sembarangan); bila tidak valid, pakai default.
     */
    protected function terapkanSort(Request $request, $query, array $boleh, string $defaultKolom, string $defaultArah = 'asc')
    {
        $sort = $request->input('sort', $defaultKolom);
        $arah = strtolower((string) $request->input('dir', $defaultArah)) === 'desc' ? 'desc' : 'asc';
        if (! in_array($sort, $boleh, true)) {
            $sort = $defaultKolom;
        }
        return $query->orderBy($sort, $arah);
    }

    /**
     * Jumlah data per halaman dari query string (?per_page=15|25|50|100).
     * Di luar pilihan itu kembali ke default (mencegah per_page besar
     * yang membebani server, mis. menampilkan 6.548 siswa sekaligus).
     */
    protected function perPage(Request $request, int $default = 15, array $boleh = [15, 25, 50, 100]): int
    {
        $p = (int) $request->input('per_page', $default);
        return in_array($p, $boleh, true) ? $p : $default;
    }
}
