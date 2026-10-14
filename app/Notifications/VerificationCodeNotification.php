<?php

namespace App\Notifications;

use App\Models\OtpCode;
use App\Services\OtpService;
use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/**
 * Emails the one-time code that confirms a new account's email address. It is
 * sent synchronously: the user is waiting on the verification screen.
 */
class VerificationCodeNotification extends Notification
{
    use Queueable;

    public function __construct(public OtpCode $otpCode) {}

    /**
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('otp.mail_subject', ['code' => $this->otpCode->code]))
            ->greeting(__('otp.mail_greeting', ['name' => $notifiable->name]))
            ->line(__('otp.mail_intro'))
            ->line('**'.$this->otpCode->code.'**')
            ->line(__('otp.mail_expiry', ['minutes' => OtpService::TTL_MINUTES]))
            ->line(__('otp.mail_ignore'));
    }
}
