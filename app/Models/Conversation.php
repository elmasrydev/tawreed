<?php

namespace App\Models;

use Database\Factories\ConversationFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Scope;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

#[Fillable(['rfq_id', 'quote_id', 'buyer_id', 'supplier_id', 'opened_at', 'last_message_at'])]
class Conversation extends Model
{
    /** @use HasFactory<ConversationFactory> */
    use HasFactory;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'opened_at' => 'datetime',
            'last_message_at' => 'datetime',
        ];
    }

    public function rfq(): BelongsTo
    {
        return $this->belongsTo(Rfq::class);
    }

    public function quote(): BelongsTo
    {
        return $this->belongsTo(Quote::class);
    }

    public function buyer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'buyer_id');
    }

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(User::class, 'supplier_id');
    }

    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    public function latestMessage(): HasOne
    {
        return $this->hasOne(Message::class)->latestOfMany();
    }

    public function isParticipant(User $user): bool
    {
        return in_array($user->id, [$this->buyer_id, $this->supplier_id], true);
    }

    public function counterpartFor(User $user): User
    {
        return $user->id === $this->buyer_id ? $this->supplier : $this->buyer;
    }

    public function unreadCountFor(User $user): int
    {
        return $this->messages()
            ->whereNot('sender_id', $user->id)
            ->whereNull('read_at')
            ->count();
    }

    #[Scope]
    protected function forParticipant(Builder $query, User $user): void
    {
        $query->where(function (Builder $query) use ($user): void {
            $query->where('buyer_id', $user->id)->orWhere('supplier_id', $user->id);
        });
    }
}
