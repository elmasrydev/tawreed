<?php

namespace App\Policies;

use App\Models\Conversation;
use App\Models\User;

class ConversationPolicy
{
    /**
     * Only the two parties to the awarded deal may read the thread. A supplier
     * additionally needs a running subscription.
     */
    public function view(User $user, Conversation $conversation): bool
    {
        if (! $conversation->isParticipant($user)) {
            return false;
        }

        return $user->isBuyer() || $user->hasActiveSubscription();
    }

    public function reply(User $user, Conversation $conversation): bool
    {
        return $this->view($user, $conversation);
    }
}
