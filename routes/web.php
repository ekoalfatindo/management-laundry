<?php

use App\Http\Controllers\AkunController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\NewPasswordController;
use App\Http\Controllers\Auth\PasswordResetLinkController;
use App\Http\Controllers\BarangController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\JenisLayananController;
use App\Http\Controllers\KaryawanController;
use App\Http\Controllers\KiloanController;
use App\Http\Controllers\NotaController;
use App\Http\Controllers\PelangganController;
use App\Http\Controllers\PengaturanController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::middleware('guest')->group(function (): void {
    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);

    Route::get('forgot-password', [PasswordResetLinkController::class, 'create'])->name('password.request');
    Route::post('forgot-password', [PasswordResetLinkController::class, 'store'])
        ->middleware('throttle:6,1')
        ->name('password.email');
    Route::get('reset-password/{token}', [NewPasswordController::class, 'create'])->name('password.reset');
    Route::post('reset-password', [NewPasswordController::class, 'store'])->name('password.store');
});

Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])
    ->middleware('auth')
    ->name('logout');

Route::middleware(['auth', 'pelanggan'])->prefix('akun')->name('akun.')->group(function (): void {
    Route::get('/', [AkunController::class, 'index'])->name('index');
    Route::get('nota/{nota}', [AkunController::class, 'show'])->name('nota.show');
    Route::get('nota/{nota}/pdf', [AkunController::class, 'pdf'])->name('nota.pdf');
});

Route::middleware('auth')->group(function (): void {
    Route::get('/', [DashboardController::class, 'index'])
        ->middleware(['staff', 'permission:dashboard.view'])
        ->name('dashboard');

    Route::middleware('permission:users.manage')->group(function (): void {
        Route::get('users', [UserController::class, 'index'])->name('users.index');
        Route::post('users', [UserController::class, 'store'])->name('users.store');
        Route::put('users/{user}', [UserController::class, 'update'])->name('users.update');
        Route::delete('users/{user}', [UserController::class, 'destroy'])->name('users.destroy');
    });

    Route::middleware('permission:pengaturan.manage')->group(function (): void {
        Route::get('pengaturan', [PengaturanController::class, 'edit'])->name('pengaturan.edit');
        Route::post('pengaturan', [PengaturanController::class, 'update'])->name('pengaturan.update');
    });

    Route::middleware('permission:karyawan.manage')->group(function (): void {
        Route::get('karyawan', [KaryawanController::class, 'index'])->name('karyawan.index');
        Route::post('karyawan', [KaryawanController::class, 'store'])->name('karyawan.store');
        Route::put('karyawan/{user}', [KaryawanController::class, 'update'])->name('karyawan.update');
        Route::delete('karyawan/{user}', [KaryawanController::class, 'destroy'])->name('karyawan.destroy');
    });

    Route::middleware('permission:pelanggan.manage')->group(function (): void {
        Route::get('pelanggan', [PelangganController::class, 'index'])->name('pelanggan.index');
        Route::post('pelanggan', [PelangganController::class, 'store'])->name('pelanggan.store');
        Route::put('pelanggan/{user}', [PelangganController::class, 'update'])->name('pelanggan.update');
        Route::delete('pelanggan/{user}', [PelangganController::class, 'destroy'])->name('pelanggan.destroy');
    });

    Route::middleware('permission:katalog.view')->group(function (): void {
        Route::get('barang', [BarangController::class, 'index'])->name('barang.index');
        Route::get('kiloan', [KiloanController::class, 'index'])->name('kiloan.index');
        Route::get('jenis-layanan', [JenisLayananController::class, 'index'])->name('jenis-layanan.index');
    });

    Route::middleware('permission:katalog.manage')->group(function (): void {
        Route::post('barang', [BarangController::class, 'store'])->name('barang.store');
        Route::put('barang/{barang}', [BarangController::class, 'update'])->name('barang.update');
        Route::delete('barang/{barang}', [BarangController::class, 'destroy'])->name('barang.destroy');

        Route::post('kiloan', [KiloanController::class, 'store'])->name('kiloan.store');
        Route::put('kiloan/{kiloan}', [KiloanController::class, 'update'])->name('kiloan.update');
        Route::delete('kiloan/{kiloan}', [KiloanController::class, 'destroy'])->name('kiloan.destroy');

        Route::post('jenis-layanan', [JenisLayananController::class, 'store'])->name('jenis-layanan.store');
        Route::put('jenis-layanan/{jenisLayanan}', [JenisLayananController::class, 'update'])->name('jenis-layanan.update');
        Route::delete('jenis-layanan/{jenisLayanan}', [JenisLayananController::class, 'destroy'])->name('jenis-layanan.destroy');
    });

    Route::get('nota', [NotaController::class, 'index'])
        ->middleware('permission:nota.view')
        ->name('nota.index');
    Route::get('nota/export', [NotaController::class, 'export'])
        ->middleware('permission:nota.export')
        ->name('nota.export');
    Route::get('nota/create', [NotaController::class, 'create'])
        ->middleware('permission:nota.create')
        ->name('nota.create');
    Route::post('nota/kiloan', [NotaController::class, 'storeKiloan'])
        ->middleware('permission:nota.create')
        ->name('nota.kiloan.store');
    Route::post('nota/satuan', [NotaController::class, 'storeSatuan'])
        ->middleware('permission:nota.create')
        ->name('nota.satuan.store');
    Route::get('nota/{nota}', [NotaController::class, 'show'])
        ->middleware('permission:nota.view')
        ->name('nota.show');
    Route::get('nota/{nota}/edit', [NotaController::class, 'edit'])
        ->middleware('permission:nota.update')
        ->name('nota.edit');
    Route::put('nota/{nota}', [NotaController::class, 'update'])
        ->middleware('permission:nota.update')
        ->name('nota.update');
    Route::delete('nota/{nota}', [NotaController::class, 'destroy'])
        ->middleware('permission:nota.delete')
        ->name('nota.destroy');
});
