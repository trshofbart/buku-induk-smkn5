<?php

use App\Http\Controllers\Admin\BeritaAdminController;
use App\Http\Controllers\Admin\BukuIndukController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\MasterController;
use App\Http\Controllers\Admin\NilaiController;
use App\Http\Controllers\Admin\PengumumanAdminController;
use App\Http\Controllers\Admin\ProfilAdminController;
use App\Http\Controllers\Admin\SiswaController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\PublicController;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| Route Publik — tanpa login
|--------------------------------------------------------------------------
*/
Route::get('/', [PublicController::class, 'home'])->name('home');
Route::get('/profil', [PublicController::class, 'profil'])->name('profil.index');
Route::get('/profil/{slug}', [PublicController::class, 'profilShow'])->name('profil.show');
Route::get('/berita', [PublicController::class, 'berita'])->name('berita.index');
Route::get('/berita/{slug}', [PublicController::class, 'beritaShow'])->name('berita.show');
Route::get('/pengumuman', [PublicController::class, 'pengumuman'])->name('pengumuman.index');

/*
|--------------------------------------------------------------------------
| Auth pegawai — login saja, TANPA registrasi publik.
| Akun hanya dibuat Admin TU dari menu Pengguna.
|--------------------------------------------------------------------------
*/
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');
Route::post('/logout', [AuthController::class, 'logout'])->name('logout')->middleware('auth');

/*
|--------------------------------------------------------------------------
| Route Admin — wajib login. Buku Induk & Data Siswa hanya untuk pegawai.
|--------------------------------------------------------------------------
*/
Route::prefix('admin')->name('admin.')->middleware(['auth'])->group(function () {
    Route::get('/', [DashboardController::class, 'index'])->name('dashboard');

    // --- Data Siswa ---
    Route::get('/siswa/export', [SiswaController::class, 'export'])->name('siswa.export');
    Route::get('/siswa/import', [SiswaController::class, 'importForm'])->name('siswa.import')->middleware('role:admin_tu');
    Route::post('/siswa/import', [SiswaController::class, 'importStore'])->name('siswa.import.store')->middleware('role:admin_tu');
    Route::get('/siswa/{siswa}/cetak', [SiswaController::class, 'cetak'])->name('siswa.cetak');
    Route::get('/siswa/{siswa}/transkrip-nilai', [NilaiController::class, 'transkrip'])->name('nilai.transkrip');
    Route::resource('siswa', SiswaController::class)->except(['show'])->middleware('role:admin_tu,kepala_sekolah,guru');
    // Catatan: resource di atas dibatasi role lewat middleware grup;
    // aksi ubah (create/store/edit/update/destroy) dijaga ulang di controller (canEdit = Admin TU).
    Route::get('/siswa/{siswa}', [SiswaController::class, 'show'])->name('siswa.show');

    // --- Buku Induk ---
    Route::get('/buku-induk', [BukuIndukController::class, 'index'])->name('buku-induk.index');
    Route::get('/buku-induk/kelas/{kelas}', [BukuIndukController::class, 'perKelas'])->name('buku-induk.per-kelas');
    Route::get('/buku-induk/nominatif', [BukuIndukController::class, 'nominatif'])->name('buku-induk.nominatif');
    Route::get('/buku-induk/ledger', [BukuIndukController::class, 'ledger'])->name('buku-induk.ledger');
    Route::post('/buku-induk/ledger', [BukuIndukController::class, 'ledgerStore'])->name('buku-induk.ledger.store')->middleware('role:admin_tu');
    Route::get('/buku-induk/transkrip', [BukuIndukController::class, 'transkrip'])->name('buku-induk.transkrip');
    Route::post('/buku-induk/transkrip', [BukuIndukController::class, 'transkripStore'])->name('buku-induk.transkrip.store')->middleware('role:admin_tu');

    // --- Input Nilai per mapel / semester / tahun ajaran ---
    Route::get('/nilai', [NilaiController::class, 'index'])->name('nilai.index')->middleware('role:admin_tu,kepala_sekolah,guru');
    Route::post('/nilai', [NilaiController::class, 'store'])->name('nilai.store')->middleware('role:admin_tu');

    // --- Berita / Profil / Pengumuman (kelola konten publik) ---
    Route::resource('berita', BeritaAdminController::class)->except(['show'])->middleware('role:admin_tu');
    Route::get('/profil', [ProfilAdminController::class, 'index'])->name('profil.index')->middleware('role:admin_tu');
    Route::get('/profil/{profil}/edit', [ProfilAdminController::class, 'edit'])->name('profil.edit')->middleware('role:admin_tu');
    Route::put('/profil/{profil}', [ProfilAdminController::class, 'update'])->name('profil.update')->middleware('role:admin_tu');
    Route::resource('pengumuman', PengumumanAdminController::class)->except(['show'])->middleware('role:admin_tu');

    // --- Data master (Admin TU) ---
    Route::middleware('role:admin_tu')->group(function () {
        Route::get('/tahun-ajaran', [MasterController::class, 'tahunIndex'])->name('tahun-ajaran.index');
        Route::post('/tahun-ajaran', [MasterController::class, 'tahunStore'])->name('tahun-ajaran.store');
        Route::post('/tahun-ajaran/{tahun}/aktifkan', [MasterController::class, 'tahunAktif'])->name('tahun-ajaran.aktifkan');
        Route::delete('/tahun-ajaran/{tahun}', [MasterController::class, 'tahunDestroy'])->name('tahun-ajaran.destroy');

        Route::get('/jurusan', [MasterController::class, 'jurusanIndex'])->name('jurusan.index');
        Route::post('/jurusan', [MasterController::class, 'jurusanStore'])->name('jurusan.store');
        Route::delete('/jurusan/{jurusan}', [MasterController::class, 'jurusanDestroy'])->name('jurusan.destroy');

        Route::get('/kelas', [MasterController::class, 'kelasIndex'])->name('kelas.index');
        Route::post('/kelas', [MasterController::class, 'kelasStore'])->name('kelas.store');
        Route::delete('/kelas/{kelas}', [MasterController::class, 'kelasDestroy'])->name('kelas.destroy');

        Route::get('/mapel', [MasterController::class, 'mapelIndex'])->name('mapel.index');
        Route::post('/mapel', [MasterController::class, 'mapelStore'])->name('mapel.store');
        Route::delete('/mapel/{mapel}', [MasterController::class, 'mapelDestroy'])->name('mapel.destroy');

        Route::get('/data-sekolah', [MasterController::class, 'dataSekolahEdit'])->name('data-sekolah.edit');
        Route::put('/data-sekolah', [MasterController::class, 'dataSekolahUpdate'])->name('data-sekolah.update');

        Route::get('/pengguna', [MasterController::class, 'penggunaIndex'])->name('pengguna.index');
        Route::post('/pengguna', [MasterController::class, 'penggunaStore'])->name('pengguna.store');
        Route::post('/pengguna/{user}/toggle', [MasterController::class, 'penggunaToggle'])->name('pengguna.toggle');
        Route::delete('/pengguna/{user}', [MasterController::class, 'penggunaDestroy'])->name('pengguna.destroy');

        Route::get('/log', [MasterController::class, 'logIndex'])->name('log.index');
    });
});
