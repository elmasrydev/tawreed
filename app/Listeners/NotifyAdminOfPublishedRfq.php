<?php

namespace App\Listeners;

use App\Events\RfqPublished;
use App\Mail\RfqPublishedMail;
use Illuminate\Support\Facades\Mail;

class NotifyAdminOfPublishedRfq
{
    /**
     * Emails the admin inbox a copy of every newly published request so the
     * team can track it. A mail failure is reported but never blocks the
     * buyer's publish.
     */
    public function handle(RfqPublished $event): void
    {
        rescue(fn () => Mail::to(config('mail.admin_address'))->send(new RfqPublishedMail($event->rfq)));
    }
}
