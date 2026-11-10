<?php

namespace App\Services\Chat;

use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ChatPendingAction;
use App\Models\User;
use App\Services\Admin\AnnouncementCreationService;
use App\Services\TenantRequestService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class ChatPendingActionService
{
    public function __construct(
        private TenantRequestService $tenantRequests,
        private AnnouncementCreationService $announcements,
    ) {}

    public function prepare(
        string $actionType,
        array $payload,
        ChatConversation $conversation,
        ChatMessage $sourceMessage,
        User $actor,
    ): ChatPendingAction {
        $this->ensureActorOwnsConversation($conversation, $sourceMessage, $actor);
        $this->ensureRoleCanPrepare($actionType, $actor);

        $validatedPayload = match ($actionType) {
            'absence_request' => $this->tenantRequests->validateAbsenceRequest($payload),
            'maintenance_request' => $this->tenantRequests->validateMaintenanceRequest($payload),
            'announcement' => $this->announcements->validatePayload($payload),
            default => throw ValidationException::withMessages([
                'action_type' => 'This chat action is not available.',
            ]),
        };

        return DB::transaction(function () use (
            $actionType,
            $validatedPayload,
            $conversation,
            $sourceMessage,
            $actor,
        ): ChatPendingAction {
            ChatConversation::query()
                ->whereKey($conversation->id)
                ->lockForUpdate()
                ->firstOrFail();

            $existingAction = ChatPendingAction::query()
                ->where('source_message_id', $sourceMessage->id)
                ->lockForUpdate()
                ->first();

            if ($existingAction !== null) {
                if ($existingAction->action_type !== $actionType || $existingAction->payload !== $validatedPayload) {
                    throw ValidationException::withMessages([
                        'action' => 'This message already prepared a different request. Start a new chat to prepare another request.',
                    ]);
                }

                if (
                    $existingAction->status === ChatPendingAction::STATUS_PENDING
                    && $existingAction->expires_at->isPast()
                ) {
                    $existingAction->update(['status' => ChatPendingAction::STATUS_EXPIRED]);
                }

                return $existingAction;
            }

            ChatPendingAction::query()
                ->whereBelongsTo($conversation, 'conversation')
                ->whereBelongsTo($actor, 'user')
                ->where('status', ChatPendingAction::STATUS_PENDING)
                ->update(['status' => ChatPendingAction::STATUS_CANCELLED]);

            return ChatPendingAction::query()->create([
                'chat_conversation_id' => $conversation->id,
                'user_id' => $actor->id,
                'source_message_id' => $sourceMessage->id,
                'action_type' => $actionType,
                'payload' => $validatedPayload,
                'status' => ChatPendingAction::STATUS_PENDING,
                'expires_at' => now()->addMinutes(15),
            ]);
        });
    }

    /**
     * @return array{message: string, source: string}|null
     */
    public function handleConfirmation(
        ChatConversation $conversation,
        ChatMessage $confirmationMessage,
        User $actor,
    ): ?array {
        $this->ensureActorOwnsConversation($conversation, $confirmationMessage, $actor);

        $resolvedAction = $this->resolvedAction($conversation, $confirmationMessage, $actor);

        if ($resolvedAction !== null) {
            return $resolvedAction->result;
        }

        $decision = $this->decision($confirmationMessage->content);

        return DB::transaction(function () use (
            $conversation,
            $confirmationMessage,
            $actor,
            $decision,
        ): ?array {
            $action = ChatPendingAction::query()
                ->whereBelongsTo($actor, 'user')
                ->whereBelongsTo($conversation, 'conversation')
                ->where('status', ChatPendingAction::STATUS_PENDING)
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if ($action === null) {
                return $this->resolvedAction($conversation, $confirmationMessage, $actor, lockForUpdate: true)
                    ?->result;
            }

            $this->ensureRoleCanPrepare($action->action_type, $actor);

            if ($action->expires_at->isPast()) {
                $result = $this->actionResult(
                    $action->action_type,
                    $action->action_type === 'announcement'
                        ? 'That announcement draft has expired. Please describe it again to start a new one.'
                        : 'That request draft has expired. Please describe the request again to start a new one.',
                );
                $action->update([
                    'confirmation_message_id' => $confirmationMessage->id,
                    'status' => ChatPendingAction::STATUS_EXPIRED,
                    'result' => $result,
                ]);

                return $result;
            }

            if ($decision === 'cancel') {
                $result = $this->actionResult(
                    $action->action_type,
                    $action->action_type === 'announcement'
                        ? 'I cancelled that draft. No announcement was created or published.'
                        : 'I cancelled that draft. No request was submitted.',
                );
                $action->update([
                    'confirmation_message_id' => $confirmationMessage->id,
                    'status' => ChatPendingAction::STATUS_CANCELLED,
                    'result' => $result,
                ]);

                return $result;
            }

            if ($decision !== 'confirm') {
                return $this->actionResult(
                    $action->action_type,
                    $action->action_type === 'announcement'
                        ? 'I have an announcement draft waiting for your decision. Reply “Confirm” to publish or schedule it, or “Cancel” to discard it.'
                        : 'I have a request draft waiting for your decision. Reply “Confirm” to submit the details shown above, or “Cancel” to discard it.',
                );
            }

            try {
                $request = match ($action->action_type) {
                    'absence_request' => $this->tenantRequests->createAbsenceRequest($actor, $action->payload),
                    'maintenance_request' => $this->tenantRequests->createMaintenanceRequest($actor, $action->payload),
                    'announcement' => $this->announcements->create($actor, $action->payload),
                    default => throw ValidationException::withMessages([
                        'action_type' => 'This chat action is no longer available.',
                    ]),
                };
            } catch (ValidationException) {
                $result = $this->actionResult(
                    $action->action_type,
                    $action->action_type === 'announcement'
                        ? 'I could not publish this announcement because its details are no longer valid. Nothing was created. Please review it in the admin announcements module.'
                        : 'I could not submit that request because its details no longer meet the dormitory requirements. No request was created. Please review the request in the appropriate tenant form.',
                );
                $action->update([
                    'confirmation_message_id' => $confirmationMessage->id,
                    'status' => ChatPendingAction::STATUS_FAILED,
                    'result' => $result,
                ]);

                return $result;
            }

            $result = match ($action->action_type) {
                'announcement' => [
                    'message' => ($request->publish_at->isFuture() ? 'Announcement scheduled for ' : 'Announcement published: ')
                        .$request->title
                        .($request->publish_at->isFuture() ? ' on '.$request->publish_at->format('F j, Y g:i A').'.' : '.'),
                    'source' => 'Announcement',
                ],
                default => [
                    'message' => sprintf(
                        '%s %s was submitted successfully and is pending administrator review.',
                        $action->action_type === 'absence_request' ? 'Absence request' : 'Maintenance request',
                        $request->reference,
                    ),
                    'source' => $action->action_type === 'absence_request' ? 'Absence request' : 'Maintenance request',
                ],
            };

            $action->update([
                'confirmation_message_id' => $confirmationMessage->id,
                'status' => ChatPendingAction::STATUS_CONFIRMED,
                'confirmed_at' => now(),
                'result' => $result,
            ]);

            return $result;
        }, attempts: 3);
    }

    private function resolvedAction(
        ChatConversation $conversation,
        ChatMessage $message,
        User $actor,
        bool $lockForUpdate = false,
    ): ?ChatPendingAction {
        $query = ChatPendingAction::query()
            ->whereBelongsTo($actor, 'user')
            ->whereBelongsTo($conversation, 'conversation')
            ->where('confirmation_message_id', $message->id)
            ->whereIn('status', [
                ChatPendingAction::STATUS_CONFIRMED,
                ChatPendingAction::STATUS_CANCELLED,
                ChatPendingAction::STATUS_EXPIRED,
                ChatPendingAction::STATUS_FAILED,
            ]);

        if ($lockForUpdate) {
            $query->lockForUpdate();
        }

        return $query->first();
    }

    private function decision(string $message): ?string
    {
        $normalized = mb_strtolower(trim($message));
        $normalized = trim($normalized, " \t\n\r\0\x0B.!?");
        $confirmations = ['yes', 'yes please', 'confirm', 'confirm and submit', 'submit', 'please submit', 'go ahead'];
        $cancellations = ['no', 'no thanks', 'cancel', 'cancel it', 'discard'];

        if (in_array($normalized, $confirmations, true)) {
            return 'confirm';
        }

        if (in_array($normalized, $cancellations, true)) {
            return 'cancel';
        }

        return null;
    }

    private function ensureActorOwnsConversation(
        ChatConversation $conversation,
        ChatMessage $message,
        User $actor,
    ): void {
        if (
            ! in_array($actor->role, ['tenant', 'admin'], true)
            || (int) $conversation->user_id !== (int) $actor->id
            || (int) $message->chat_conversation_id !== (int) $conversation->id
            || $message->role !== ChatMessage::ROLE_USER
        ) {
            throw new AuthorizationException('This chat action is available only to the tenant who owns this conversation.');
        }
    }

    private function ensureRoleCanPrepare(string $actionType, User $actor): void
    {
        $requiredRole = match ($actionType) {
            'absence_request', 'maintenance_request' => 'tenant',
            'announcement' => 'admin',
            default => null,
        };

        if ($requiredRole === null || $actor->role !== $requiredRole) {
            throw new AuthorizationException('This chat action is not available to your account.');
        }
    }

    /**
     * @return array{message: string, source: string}
     */
    private function actionResult(string $actionType, string $message): array
    {
        return [
            'message' => $message,
            'source' => $actionType === 'announcement' ? 'Announcement' : 'Request confirmation',
        ];
    }
}
