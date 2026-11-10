<?php

namespace App\Services\Chat;

use App\Models\ChatConversation;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

class ConversationService
{
    public function guestSessionHash(string $sessionId): string
    {
        return hash_hmac('sha256', $sessionId, (string) config('app.key'));
    }

    public function create(?User $user, string $guestSessionHash): ChatConversation
    {
        return ChatConversation::query()->create([
            'user_id' => $user?->id,
            'guest_session_hash' => $user ? null : $guestSessionHash,
            'title' => 'New conversation',
        ]);
    }

    /**
     * @return Collection<int, ChatConversation>
     */
    public function listForActor(?User $user, string $guestSessionHash): Collection
    {
        return $this->queryForActor($user, $guestSessionHash)
            ->latest('updated_at')
            ->latest('id')
            ->get();
    }

    public function findForActor(?User $user, string $guestSessionHash, int|string $id): ChatConversation
    {
        return $this->queryForActor($user, $guestSessionHash)->findOrFail($id);
    }

    public function updateTitleFromMessage(ChatConversation $conversation, string $message): void
    {
        if ($conversation->title === 'New conversation') {
            $conversation->update(['title' => Str::limit(trim($message), 120)]);
        }
    }

    private function queryForActor(?User $user, string $guestSessionHash): Builder
    {
        if ($user !== null) {
            return ChatConversation::query()->where('user_id', $user->id);
        }

        return ChatConversation::query()
            ->whereNull('user_id')
            ->where('guest_session_hash', $guestSessionHash);
    }
}
