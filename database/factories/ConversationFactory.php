<?php

namespace Database\Factories;

use App\Models\Conversation;
use App\Models\Quote;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Conversation>
 */
class ConversationFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $quote = Quote::factory()->selected()->create();

        return [
            'rfq_id' => $quote->rfq_id,
            'quote_id' => $quote->id,
            'buyer_id' => $quote->rfq->buyer_id,
            'supplier_id' => $quote->supplier_id,
            'opened_at' => now(),
            'last_message_at' => now(),
        ];
    }
}
