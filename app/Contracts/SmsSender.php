<?php

namespace App\Contracts;

/**
 * The prototype has no SMS provider. Registering a real gateway later means
 * binding another implementation of this contract in a service provider.
 */
interface SmsSender
{
    public function send(string $phone, string $message): void;
}
