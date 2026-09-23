<?php

namespace App\Http\Controllers\Supplier;

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
            'shell' => 'supplier',
        ]);
    }

    public function show(Request $request, Conversation $conversation): View
    {
        Gate::authorize('view', $conversation);

        return view('chat.show', [
            'conversation' => $conversation,
            'conversations' => $this->conversationsFor($request->user()),
            'shell' => 'supplier',
        ]);
    }

    /**
     * The supplier's threads for the list pane, newest activity first, with the
     * buyer profile, last message and unread count preloaded.
     *
     * @return Collection<int, Conversation>
     */
    private function conversationsFor(User $supplier): Collection
    {
        return Conversation::query()
            ->forParticipant($supplier)
            ->with(['rfq', 'buyer.buyerProfile.businessType', 'latestMessage'])
            ->withCount(['messages as unread_count' => fn (Builder $query) => $query
                ->whereNot('sender_id', $supplier->id)
                ->whereNull('read_at')])
            ->latest('last_message_at')
            ->get();
    }
}
