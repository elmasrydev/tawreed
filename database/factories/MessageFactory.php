<?php

namespace Database\Factories;

use App\Enums\MessageType;
use App\Models\Conversation;
use App\Models\Message;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Message>
 */
class MessageFactory extends Factory
{
    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'conversation_id' => Conversation::factory(),
            'sender_id' => fn (array $attributes) => Conversation::find($attributes['conversation_id'])?->buyer_id,
            'type' => MessageType::Text,
            'body' => fake()->sentence(),
        ];
    }

    public function system(): static
    {
        return $this->state(fn (array $attributes): array => [
            'type' => MessageType::System,
            'sender_id' => null,
        ]);
    }
}
