<?php

namespace App\Http\Controllers\Buyer;

use App\Http\Controllers\Controller;
use App\Models\Conversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\View\View;

class ChatController extends Controller
{
    public function index(Request $request): View
    {
        return view('chat.index', [
            'conversations' => $this->conversationsFor($request->user()),
            'shell' => 'buyer',
        ]);
    }

    public function show(Request $request, Conversation $conversation): View
    {
        Gate::authorize('view', $conversation);

        return view('chat.show', [
            'conversation' => $conversation,
            'conversations' => $this->conversationsFor($request->user()),
            'shell' => 'buyer',
        ]);
    }

    /**
     * The buyer's threads for the list pane, newest activity first, with the
     * supplier profile (and logo), last message and unread count preloaded.
     *
     * @return Collection<int, Conversation>
     */
    private function conversationsFor(User $buyer): Collection
    {
        return Conversation::query()
            ->forParticipant($buyer)
            ->with(['rfq', 'supplier.supplierProfile.media', 'latestMessage'])
            ->withCount(['messages as unread_count' => fn (Builder $query) => $query
                ->whereNot('sender_id', $buyer->id)
                ->whereNull('read_at')])
            ->latest('last_message_at')
            ->get();
    }
}
