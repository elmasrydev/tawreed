<?php

namespace App\Mail;

use App\Models\Rfq;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * The admin's copy of a newly published request, including the buyer
 * details that suppliers never see.
 */
class RfqPublishedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public Rfq $rfq) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: __('admin.rfq_published_mail_subject', ['reference' => $this->rfq->reference, 'title' => $this->rfq->title]),
        );
    }

    public function content(): Content
    {
        $this->rfq->loadMissing(['buyer.buyerProfile', 'category', 'subcategory', 'unit', 'governorate']);

        return new Content(
            markdown: 'mail.rfq-published',
        );
    }
}
