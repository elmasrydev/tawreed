<?php

namespace App\Http\Controllers;

use App\Http\Requests\ContactMessageRequest;
use App\Mail\ContactMessageMail;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

class ContactController extends Controller
{
    /**
     * Emails a landing page "Contact us" message to the team inbox. It is
     * sent synchronously so it never waits on a queue worker.
     */
    public function store(ContactMessageRequest $request): RedirectResponse
    {
        Mail::to(config('mail.admin_address'))
            ->send(new ContactMessageMail(
                name: $request->validated('name'),
                email: $request->validated('email'),
                phone: $request->validated('phone'),
                messageBody: $request->validated('message'),
            ));

        return redirect()
            ->to(route('splash').'#contact')
            ->with('status', __('landing.contact_sent'));
    }
}
