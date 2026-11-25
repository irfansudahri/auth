<?php

use App\Http\Controllers\Api\SsoApiController;
use Illuminate\Support\Facades\Route;

Route::prefix('sso')->group(function () {
    Route::post('/verify', [SsoApiController::class, 'verify'])->name('api.sso.verify');
    Route::post('/introspect', [SsoApiController::class, 'introspect'])->name('api.sso.introspect');
    Route::post('/register-session', [SsoApiController::class, 'registerSession'])->name('api.sso.register-session');
});
