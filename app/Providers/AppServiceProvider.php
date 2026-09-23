<?php

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Contracts\SmsSender;
use App\Services\Payment\FakePaymentGateway;
use App\Services\Sms\LogSmsSender;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->bind(SmsSender::class, LogSmsSender::class);
        $this->app->bind(PaymentGateway::class, FakePaymentGateway::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
