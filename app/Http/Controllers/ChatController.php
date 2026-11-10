<?php

namespace App\Http\Controllers;

use App\Exceptions\Chat\ChatProviderException;
use App\Exceptions\Chat\ProviderNotConfiguredException;
use App\Http\Requests\StoreChatMessageRequest;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\User;
use App\Services\Chat\ChatService;
use App\Services\Chat\ConversationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ChatController extends Controller
{
    public function __construct(
        private ConversationService $conversations,
        private ChatService $chat,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $guestSessionHash = $this->guestSessionHash($request);

        return response()->json([
            'data' => $this->conversations
                ->listForActor($request->user(), $guestSessionHash)
                ->map(fn (ChatConversation $conversation): array => $this->conversationSummary($conversation))
                ->all(),
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $conversation = $this->conversations->create(
            $request->user(),
            $this->guestSessionHash($request),
        );

        return response()->json([
            'data' => $this->conversationSummary($conversation),
        ], 201);
    }

    public function show(Request $request, string $conversation): JsonResponse
    {
        $conversation = $this->conversations->findForActor(
            $request->user(),
            $this->guestSessionHash($request),
            $conversation,
        );
        $conversation->load(['messages' => fn ($query) => $query->orderBy('id')]);

        return response()->json([
            'data' => $this->conversationSummary($conversation) + [
                'messages' => $conversation->messages
                    ->map(fn (ChatMessage $message): array => $this->messagePayload($message))
                    ->all(),
            ],
        ]);
    }

    public function destroy(Request $request, string $conversation): JsonResponse
    {
        $this->conversations
            ->findForActor($request->user(), $this->guestSessionHash($request), $conversation)
            ->delete();

        return response()->json(status: 204);
    }

    public function storeMessage(
        StoreChatMessageRequest $request,
        string $conversation,
    ): JsonResponse|StreamedResponse {
        $conversation = $this->conversations->findForActor(
            $request->user(),
            $this->guestSessionHash($request),
            $conversation,
        );
        $userMessage = $conversation->messages()->create([
            'role' => ChatMessage::ROLE_USER,
            'content' => $request->validated('message'),
        ]);
        $this->conversations->updateTitleFromMessage($conversation, $userMessage->content);

        return $request->header('Accept') === 'text/event-stream'
            ? $this->streamReply($conversation, $userMessage, $request->user())
            : $this->generateReply($conversation, $userMessage, $request->user());
    }

    public function retryMessage(
        Request $request,
        string $conversation,
        string $message,
    ): JsonResponse|StreamedResponse {
        $conversation = $this->conversations->findForActor(
            $request->user(),
            $this->guestSessionHash($request),
            $conversation,
        );
        $userMessage = $conversation->messages()
            ->where('role', ChatMessage::ROLE_USER)
            ->findOrFail($message);

        return $request->header('Accept') === 'text/event-stream'
            ? $this->streamReply($conversation, $userMessage, $request->user())
            : $this->generateReply($conversation, $userMessage, $request->user());
    }

    private function streamReply(
        ChatConversation $conversation,
        ChatMessage $userMessage,
        ?User $actor,
    ): JsonResponse|StreamedResponse {
        if (! $this->chat->isConfigured()) {
            $assistantMessage = $this->chat->replyPendingActionIfAny(
                $conversation,
                $userMessage,
                $actor,
            );

            if ($assistantMessage !== null) {
                return response()->stream(function () use ($userMessage, $assistantMessage): void {
                    $this->writeStreamEvent('complete', [
                        'user_message' => $this->messagePayload($userMessage),
                        'assistant_message' => $this->messagePayload($assistantMessage),
                    ]);
                }, 200, [
                    'Content-Type' => 'text/event-stream',
                    'Cache-Control' => 'no-cache',
                    'X-Accel-Buffering' => 'no',
                ]);
            }

            return response()->json([
                'error' => [
                    'code' => 'provider_unavailable',
                    'message' => 'DORA AI and approved knowledge retrieval are not configured.',
                ],
                'user_message' => $this->messagePayload($userMessage),
            ], 503);
        }

        return response()->stream(function () use ($conversation, $userMessage, $actor): void {
            try {
                $assistantMessage = $this->chat->replyStream(
                    $conversation,
                    $userMessage,
                    $actor,
                    function (string $event, string $text): void {
                        $this->writeStreamEvent($event, ['text' => $text]);
                    },
                );

                $this->writeStreamEvent('complete', [
                    'user_message' => $this->messagePayload($userMessage),
                    'assistant_message' => $this->messagePayload($assistantMessage),
                ]);
            } catch (ProviderNotConfiguredException $exception) {
                $this->writeStreamEvent('error', [
                    'error' => [
                        'code' => 'provider_unavailable',
                        'message' => $exception->getMessage(),
                    ],
                    'user_message' => $this->messagePayload($userMessage),
                ]);
            } catch (ChatProviderException $exception) {
                report($exception);

                $this->writeStreamEvent('error', [
                    'error' => [
                        'code' => $exception->errorCode,
                        'message' => $exception->userMessage,
                    ],
                    'user_message' => $this->messagePayload($userMessage),
                ]);
            }
        }, 200, [
            'Content-Type' => 'text/event-stream',
            'Cache-Control' => 'no-cache',
            'X-Accel-Buffering' => 'no',
        ]);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function writeStreamEvent(string $event, array $data): void
    {
        echo 'event: '.$event."\n";
        echo 'data: '.json_encode($data, JSON_INVALID_UTF8_SUBSTITUTE | JSON_UNESCAPED_UNICODE)."\n\n";

        if (ob_get_level() > 0) {
            ob_flush();
        }

        flush();
    }

    private function generateReply(
        ChatConversation $conversation,
        ChatMessage $userMessage,
        ?User $actor,
    ): JsonResponse {
        try {
            $assistantMessage = $this->chat->reply($conversation, $userMessage, $actor);
        } catch (ProviderNotConfiguredException $exception) {
            return response()->json([
                'error' => [
                    'code' => 'provider_unavailable',
                    'message' => $exception->getMessage(),
                ],
                'user_message' => $this->messagePayload($userMessage),
            ], 503);
        } catch (ChatProviderException $exception) {
            report($exception);

            return response()->json([
                'error' => [
                    'code' => $exception->errorCode,
                    'message' => $exception->userMessage,
                ],
                'user_message' => $this->messagePayload($userMessage),
            ], 502);
        }

        return response()->json([
            'user_message' => $this->messagePayload($userMessage),
            'assistant_message' => $this->messagePayload($assistantMessage),
        ], 201);
    }

    private function guestSessionHash(Request $request): string
    {
        return $this->conversations->guestSessionHash($request->session()->getId());
    }

    /**
     * @return array{id: int, title: string, created_at: string, updated_at: string}
     */
    private function conversationSummary(ChatConversation $conversation): array
    {
        return [
            'id' => $conversation->id,
            'title' => $conversation->title,
            'created_at' => $conversation->created_at?->toIso8601String(),
            'updated_at' => $conversation->updated_at?->toIso8601String(),
        ];
    }

    /**
     * @return array{id: int, role: string, content: string, created_at: string}
     */
    private function messagePayload(ChatMessage $message): array
    {
        return [
            'id' => $message->id,
            'role' => $message->role,
            'content' => $message->content,
            'source_references' => $message->source_references ?? [],
            'created_at' => $message->created_at?->toIso8601String(),
        ];
    }
}
