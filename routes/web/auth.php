<?php

use App\Http\Controllers\Auth\UserAuthController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'auth'], function () {
    Route::group(['middleware' => 'guest'], function () {
        Route::get('login', [UserAuthController::class, 'login'])->name('login');
        Route::post('login', [UserAuthController::class, 'attempt'])->name('attempt');
    });

    Route::post('logout', [UserAuthController::class, 'logout'])
        ->middleware('auth')
        ->name('logout');
});
