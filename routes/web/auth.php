<?php

use App\Http\Controllers\Auth\PasswordResetController;
use App\Http\Controllers\Auth\TwoFactorChallengeController;
use App\Http\Controllers\Auth\UserAuthController;
use Illuminate\Support\Facades\Route;

Route::group(['prefix' => 'auth'], function () {
    Route::group(['middleware' => 'guest'], function () {
        Route::get('login', [UserAuthController::class, 'login'])->name('login');
        Route::post('login', [UserAuthController::class, 'attempt'])->name('attempt');

        Route::get('forgot-password', [PasswordResetController::class, 'request'])->name('password.request');
        Route::post('forgot-password', [PasswordResetController::class, 'send'])
            ->middleware('throttle:6,1')
            ->name('password.email');

        Route::get('reset-password/{token}', [PasswordResetController::class, 'reset'])->name('password.reset');
        Route::post('reset-password', [PasswordResetController::class, 'store'])
            ->middleware('throttle:6,1')
            ->name('password.store');

        Route::get('two-factor-challenge', [TwoFactorChallengeController::class, 'show'])
            ->middleware('two-factor')
            ->name('two-factor.login');
        Route::post('two-factor-challenge', [TwoFactorChallengeController::class, 'store'])
            ->middleware(['two-factor', 'throttle:6,1'])
            ->name('two-factor.verify');
    });

    Route::post('logout', [UserAuthController::class, 'logout'])
        ->middleware('auth')
        ->name('logout');
});
