<?php

use App\Http\Controllers\Account\AppearanceController;
use App\Http\Controllers\Account\PasswordController;
use App\Http\Controllers\Account\ProfileController;
use App\Http\Controllers\Account\TwoFactorController;
use Illuminate\Support\Facades\Route;

Route::group(['middleware' => 'auth', 'prefix' => 'account'], function () {
    Route::get('profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('profile', [ProfileController::class, 'update'])->name('profile.update');

    Route::get('password', [PasswordController::class, 'edit'])->name('user-password.edit');
    Route::put('password', [PasswordController::class, 'update'])->name('user-password.update');

    Route::get('appearance', [AppearanceController::class, 'edit'])->name('appearance.edit');
});

Route::group(['middleware' => 'auth', 'prefix' => 'account/two-factor', 'as' => 'two-factor.'], function () {
    Route::get('/', [TwoFactorController::class, 'show'])->name('show');
    Route::post('/', [TwoFactorController::class, 'enable'])->name('enable');
    Route::delete('/', [TwoFactorController::class, 'disable'])->name('disable');
    Route::post('confirm', [TwoFactorController::class, 'confirm'])->name('confirm');
    Route::get('qr-code', [TwoFactorController::class, 'qrCode'])->name('qr-code');
    Route::get('secret-key', [TwoFactorController::class, 'secretKey'])->name('secret-key');
    Route::get('recovery-codes', [TwoFactorController::class, 'recoveryCodes'])->name('recovery-codes');
    Route::post('recovery-codes', [TwoFactorController::class, 'regenerateRecoveryCodes'])->name('regenerate-recovery-codes');
});
