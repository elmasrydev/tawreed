<x-mail::message>
# {{ __('landing.contact_mail_heading') }}

**{{ __('landing.contact_name') }}:** {{ $name }}<br>
**{{ __('landing.contact_email') }}:** {{ $email }}<br>
**{{ __('landing.contact_phone') }}:** {{ $phone ?: '—' }}

<x-mail::panel>
{!! nl2br(e($messageBody)) !!}
</x-mail::panel>

{{ __('landing.contact_mail_reply_hint') }}
</x-mail::message>
