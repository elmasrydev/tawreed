<?php

namespace App\Actions\Chat;

use App\Enums\MessageType;
use App\Models\Conversation;
use App\Models\Message;
use App\Models\User;
use App\Support\ContactDetailDetector;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;

class SendMessage
{
    public function __construct(private ContactDetailDetector $detector) {}

    /**
     * Contact details are allowed here: the chat only exists once both sides
     * have been introduced, which is the point at which sharing them is safe.
     */
    public function handle(
        Conversation $conversation,
        User $sender,
        string $body,
        MessageType $type = MessageType::Text,
        ?UploadedFile $file = null,
    ): Message {
        return DB::transaction(function () use ($conversation, $sender, $body, $type, $file): Message {
            $message = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => $sender->id,
                'type' => $file !== null ? MessageType::File : $type,
                'body' => $body,
            ]);

            if ($file !== null) {
                $message->addMedia($file)->toMediaCollection('file');
            }

            $conversation->forceFill(['last_message_at' => now()])->save();

            return $message;
        });
    }

    /**
     * Marks everything the other party wrote as read.
     */
    public function markRead(Conversation $conversation, User $reader): void
    {
        $conversation->messages()
            ->whereNot('sender_id', $reader->id)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
