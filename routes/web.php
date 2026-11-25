<?php

use App\Http\Controllers\Admin\AccessManagementController;
use App\Http\Controllers\Admin\AppManagementController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\LauncherController;
use Illuminate\Support\Facades\Route;

// Public authentication routes
Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
Route::post('/login', [AuthController::class, 'login'])->name('login.post');

// Authenticated SSO portal routes
Route::middleware('sso.auth')->group(function () {
    Route::get('/', [LauncherController::class, 'index'])->name('launcher');
    Route::get('/open/{kode}', [LauncherController::class, 'open'])->name('open');
    Route::get('/authorize', [LauncherController::class, 'authorizeApp'])->name('authorize');
    Route::post('/logout', [AuthController::class, 'logout'])->name('logout');

    // Admin panel (requires superadmin/admin level on 'auth' app)
    Route::prefix('admin')->name('admin.')->middleware('sso.admin')->group(function () {
        Route::get('/akses', [AccessManagementController::class, 'index'])->name('access.index');
        Route::post('/akses', [AccessManagementController::class, 'store'])->name('access.store');
        Route::post('/akses/{userAppLevel}/toggle', [AccessManagementController::class, 'toggle'])->name('access.toggle');

        Route::get('/aplikasi', [AppManagementController::class, 'index'])->name('apps.index');
        Route::post('/aplikasi', [AppManagementController::class, 'store'])->name('apps.store');
        Route::post('/aplikasi/{app}/toggle', [AppManagementController::class, 'toggle'])->name('apps.toggle');
        Route::post('/aplikasi/{app}/rotate-secret', [AppManagementController::class, 'rotateSecret'])->name('apps.rotate');

        Route::get('/audit', [AccessManagementController::class, 'auditLogs'])->name('audit.index');
    });
});
