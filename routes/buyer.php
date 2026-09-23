<?php

use App\Http\Controllers\Buyer\AccountController;
use App\Http\Controllers\Buyer\ChatController;
use App\Http\Controllers\Buyer\HomeController;
use App\Http\Controllers\Buyer\QuoteController;
use App\Http\Controllers\Buyer\RfqController;
use App\Http\Controllers\Buyer\SupplierProfileController;
use Illuminate\Support\Facades\Route;

/*
 * Buyer area. Every route here assumes an authenticated, active buyer.
 */

Route::get('/', HomeController::class)->name('home');

Route::get('rfqs', [RfqController::class, 'index'])->name('rfqs.index');
Route::livewire('rfqs/create', 'buyer.create-rfq')->name('rfqs.create');
Route::livewire('rfqs/{rfq}/edit', 'buyer.create-rfq')->name('rfqs.edit');
Route::get('rfqs/{rfq}', [RfqController::class, 'show'])->name('rfqs.show');
Route::delete('rfqs/{rfq}', [RfqController::class, 'destroy'])->name('rfqs.destroy');
Route::livewire('rfqs/{rfq}/quotes', 'buyer.rfq-quotes')->name('rfqs.quotes');

Route::get('quotes', [QuoteController::class, 'index'])->name('quotes.index');
Route::get('suppliers/{supplierProfile}', SupplierProfileController::class)->name('suppliers.show');
Route::get('account', AccountController::class)->name('account');

Route::get('chats', [ChatController::class, 'index'])->name('chats.index');
Route::get('chats/{conversation}', [ChatController::class, 'show'])
    ->middleware('can:view,conversation')
    ->name('chats.show');
