<?php

use App\Enums\UserRole;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\PhoneVerificationController;
use App\Http\Controllers\Auth\RegisteredBuyerController;
use App\Http\Controllers\Auth\RegisteredSupplierController;
use App\Http\Controllers\LocaleController;
use App\Http\Controllers\SplashController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', SplashController::class)->name('splash');

Route::patch('locale/{locale}', [LocaleController::class, 'update'])->name('locale.update');

Route::middleware('guest')->group(function (): void {
    Route::view('register', 'auth.register')->name('register');

    Route::get('register/buyer', [RegisteredBuyerController::class, 'create'])->name('register.buyer');
    Route::post('register/buyer', [RegisteredBuyerController::class, 'store']);

    Route::get('register/supplier', [RegisteredSupplierController::class, 'create'])->name('register.supplier');
    Route::post('register/supplier', [RegisteredSupplierController::class, 'store']);

    Route::get('login', [AuthenticatedSessionController::class, 'create'])->name('login');
    Route::post('login', [AuthenticatedSessionController::class, 'store']);
});

Route::middleware('auth')->group(function (): void {
    Route::post('logout', [AuthenticatedSessionController::class, 'destroy'])->name('logout');

    Route::get('verify-phone', [PhoneVerificationController::class, 'show'])->name('phone.verify');
    Route::post('verify-phone', [PhoneVerificationController::class, 'store']);
    Route::post('verify-phone/resend', [PhoneVerificationController::class, 'resend'])
        ->middleware('throttle:3,1')
        ->name('phone.resend');
});

Route::middleware(['auth', 'role:'.UserRole::Buyer->value])
    ->prefix('buyer')
    ->name('buyer.')
    ->group(base_path('routes/buyer.php'));

Route::middleware(['auth', 'role:'.UserRole::Supplier->value])
    ->prefix('supplier')
    ->name('supplier.')
    ->group(base_path('routes/supplier.php'));

/*
 * Local convenience for walking the app as a seeded demo account without
 * typing credentials. Never registered outside a local environment with the
 * flag explicitly on.
 */
if (app()->isLocal() && env('DEV_LOGIN_AS_ENABLED', false)) {
    Route::get('dev/login-as/{email}', function (string $email) {
        auth()->login(User::where('email', $email)->firstOrFail());

        return redirect()->route(auth()->user()->role->homeRoute());
    })->name('dev.login-as');
}
