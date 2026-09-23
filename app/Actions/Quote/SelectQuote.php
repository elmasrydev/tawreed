<?php

namespace App\Actions\Quote;

use App\Enums\MessageType;
use App\Enums\QuoteStatus;
use App\Enums\RfqStatus;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\Quote;
use Illuminate\Support\Facades\DB;

class SelectQuote
{
    /**
     * Awarding a request is the moment anonymity ends. In one transaction the
     * quote is selected, the request is awarded, every other pending quote is
     * closed, and the private conversation is opened for both sides.
     */
    public function handle(Quote $quote): Conversation
    {
        return DB::transaction(function () use ($quote): Conversation {
            $rfq = $quote->rfq;

            $quote->forceFill([
                'status' => QuoteStatus::Selected,
                'selected_at' => now(),
            ])->save();

            $rfq->quotes()
                ->whereKeyNot($quote->id)
                ->whereIn('status', [QuoteStatus::Pending, QuoteStatus::Shortlisted])
                ->update([
                    'status' => QuoteStatus::Rejected,
                    'rejected_at' => now(),
                ]);

            $rfq->forceFill([
                'status' => RfqStatus::Awarded,
                'awarded_quote_id' => $quote->id,
                'awarded_at' => now(),
            ])->save();

            $conversation = Conversation::create([
                'rfq_id' => $rfq->id,
                'quote_id' => $quote->id,
                'buyer_id' => $rfq->buyer_id,
                'supplier_id' => $quote->supplier_id,
                'opened_at' => now(),
                'last_message_at' => now(),
            ]);

            Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => null,
                'type' => MessageType::System,
                'body' => __('chat.opened_notice'),
            ]);

            $quote->supplier->supplierProfile?->increment('completed_deals_count');

            return $conversation;
        });
    }
}
