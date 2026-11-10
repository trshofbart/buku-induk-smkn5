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
}
