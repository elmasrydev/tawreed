<?php

namespace App\Providers;

use App\Contracts\PaymentGateway;
use App\Contracts\SmsSender;
use App\Services\Payment\FakePaymentGateway;
use App\Services\Payment\PaymobGateway;
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

        $this->app->bind(PaymentGateway::class, function (): PaymentGateway {
            $paymob = config('services.paymob');

            if (blank($paymob['secret_key']) || $this->app->runningUnitTests()) {
                return new FakePaymentGateway;
            }

            return new PaymobGateway(
                baseUrl: $paymob['base_url'],
                secretKey: $paymob['secret_key'],
                publicKey: (string) $paymob['public_key'],
                hmacSecret: (string) $paymob['hmac_secret'],
                integrationIds: $paymob['integration_ids'],
            );
        });
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        //
    }
}
