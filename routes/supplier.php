<?php

use App\Http\Controllers\Supplier\AccountController;
use App\Http\Controllers\Supplier\ChatController;
use App\Http\Controllers\Supplier\DashboardController;
use App\Http\Controllers\Supplier\FeedController;
use App\Http\Controllers\Supplier\QuoteController;
use App\Http\Controllers\Supplier\SubscriptionController;
use Illuminate\Support\Facades\Route;

/*
 * Supplier area. Every route here assumes an authenticated, active supplier.
 */

Route::get('/', DashboardController::class)->name('dashboard');
Route::get('rfqs', [FeedController::class, 'index'])->name('feed');
Route::get('rfqs/{rfq}', [FeedController::class, 'show'])->name('rfqs.show');
Route::get('rfqs/{rfq}/attachments/{media}', [FeedController::class, 'attachment'])->name('rfqs.attachment');
Route::livewire('rfqs/{rfq}/quote', 'supplier.submit-quote')->name('rfqs.quote');

Route::get('quotes', [QuoteController::class, 'index'])->name('quotes.index');
Route::get('account', AccountController::class)->name('account');

Route::get('subscription', [SubscriptionController::class, 'show'])->name('subscription');
Route::post('subscription/plans/{plan}', [SubscriptionController::class, 'store'])->name('subscription.subscribe');
Route::patch('subscription/auto-renew', [SubscriptionController::class, 'toggleAutoRenew'])->name('subscription.auto-renew');
Route::get('invoices/{invoice}', [SubscriptionController::class, 'invoice'])->name('invoices.show');

Route::middleware('subscribed')->group(function (): void {
    Route::get('chats', [ChatController::class, 'index'])->name('chats.index');
    Route::get('chats/{conversation}', [ChatController::class, 'show'])
        ->middleware('can:view,conversation')
        ->name('chats.show');
});
