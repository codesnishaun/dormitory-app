<?php

namespace App\Services\Admin;

use App\Models\Announcement;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class AnnouncementCreationService
{
    /**
     * @return array{title: string, category: string, body: string, publish_mode: string, publish_at?: string|null, ends_at?: string|null, is_pinned?: bool}
     */
    public function validatePayload(array $payload): array
    {
        return Validator::make($payload, [
            'title' => ['required', 'string', 'max:255'],
            'category' => ['required', Rule::in(Announcement::CATEGORIES)],
            'body' => ['required', 'string'],
            'publish_mode' => ['required', Rule::in(['now', 'schedule'])],
            'publish_at' => ['nullable', 'required_if:publish_mode,schedule', 'date', 'after:now'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:today'],
            'is_pinned' => ['sometimes', 'boolean'],
        ])->validate();
    }

    public function create(?User $actor, array $payload): Announcement
    {
        if ($actor === null || $actor->role !== 'admin') {
            throw new AuthorizationException('Only administrators can create announcements.');
        }

        $data = $this->validatePayload($payload);

        return Announcement::query()->create([
            'title' => $data['title'],
            'category' => $data['category'],
            'body' => $data['body'],
            'author_name' => $actor->name,
            'is_pinned' => $data['is_pinned'] ?? false,
            'publish_at' => $data['publish_mode'] === 'schedule'
                ? Carbon::parse($data['publish_at'])
                : now(),
            'ends_at' => $data['ends_at'] ?? null,
        ]);
    }
}
