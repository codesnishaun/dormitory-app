<?php

namespace App\Services\Chat;

use App\Data\Chat\KnowledgeChunk;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ChatPendingAction;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\ValidationException;

class ChatToolRegistry
{
    private const TOOLS = [
        'get_current_user' => [
            'roles' => ['guest', 'tenant', 'admin'],
            'description' => 'Identify the current authenticated user and role, or explain that the visitor is a guest.',
            'parameters' => [],
        ],
        'get_dormitory_information' => [
            'roles' => ['guest', 'tenant', 'admin'],
            'description' => 'Search approved dormitory information and policies.',
            'parameters' => ['query' => ['type' => 'string', 'required' => true]],
        ],
        'get_room_availability' => [
            'roles' => ['guest', 'tenant', 'admin'],
            'description' => 'Get room capacities and reserved, occupied, and unassigned spaces.',
            'parameters' => [
                'available_only' => ['type' => 'boolean', 'required' => false],
                'limit' => ['type' => 'integer', 'required' => false],
            ],
        ],
        'get_tenant_room' => [
            'roles' => ['tenant'],
            'description' => 'Get the authenticated tenant’s current or reserved room assignment.',
            'parameters' => [],
        ],
        'get_tenant_balance' => [
            'roles' => ['tenant'],
            'description' => 'Get the authenticated tenant’s charges, payments, and outstanding balance.',
            'parameters' => [],
        ],
        'get_payment_history' => [
            'roles' => ['tenant'],
            'description' => 'Get the authenticated tenant’s recent payment history.',
            'parameters' => ['limit' => ['type' => 'integer', 'required' => false]],
        ],
        'get_electricity_bills' => [
            'roles' => ['tenant'],
            'description' => 'Get finalized electricity allocations for the authenticated tenant.',
            'parameters' => ['limit' => ['type' => 'integer', 'required' => false]],
        ],
        'get_maintenance_status' => [
            'roles' => ['tenant'],
            'description' => 'Get maintenance requests belonging to the authenticated tenant.',
            'parameters' => ['limit' => ['type' => 'integer', 'required' => false]],
        ],
        'get_absence_status' => [
            'roles' => ['tenant'],
            'description' => 'Get absence requests belonging to the authenticated tenant.',
            'parameters' => ['limit' => ['type' => 'integer', 'required' => false]],
        ],
        'get_application_status' => [
            'roles' => ['tenant'],
            'description' => 'Get application records belonging to the authenticated tenant.',
            'parameters' => ['limit' => ['type' => 'integer', 'required' => false]],
        ],
        'get_announcements' => [
            'roles' => ['guest', 'tenant', 'admin'],
            'description' => 'Get currently published dormitory announcements.',
            'parameters' => ['limit' => ['type' => 'integer', 'required' => false]],
        ],
        'get_room_occupancy_summary' => [
            'roles' => ['admin'],
            'description' => 'Summarize reserved and occupied room assignments for administrators.',
            'parameters' => [],
        ],
        'get_admin_overview' => [
            'roles' => ['admin'],
            'description' => 'Get live administrative counts for rooms, tenants, pending requests, applications, and outstanding balances.',
            'parameters' => [],
        ],
        'get_admin_tenants' => [
            'roles' => ['admin'],
            'description' => 'Get a bounded list of active tenants with their assigned room and outstanding balance.',
            'parameters' => ['limit' => ['type' => 'integer', 'required' => false]],
        ],
        'get_admin_pending_applications' => [
            'roles' => ['admin'],
            'description' => 'Get recent pending applications with applicant name, preferred room, and move-in date.',
            'parameters' => ['limit' => ['type' => 'integer', 'required' => false]],
        ],
        'get_admin_pending_absences' => [
            'roles' => ['admin'],
            'description' => 'Get pending absence requests with tenant, room, dates, reason, and reference.',
            'parameters' => ['limit' => ['type' => 'integer', 'required' => false]],
        ],
        'get_admin_maintenance_requests' => [
            'roles' => ['admin'],
            'description' => 'Get pending and in-progress maintenance requests with tenant, room, and issue details.',
            'parameters' => ['limit' => ['type' => 'integer', 'required' => false]],
        ],
        'get_admin_electricity_bills' => [
            'roles' => ['admin'],
            'description' => 'Get recent finalized electricity bills grouped by room and billing period.',
            'parameters' => ['limit' => ['type' => 'integer', 'required' => false]],
        ],
        'prepare_announcement' => [
            'roles' => ['admin'],
            'description' => 'Prepare a dormitory announcement for administrator review and explicit confirmation. Collect a title, one of these categories: General update, Utilities, Maintenance, Community event, Reminder, and the announcement body. Publish immediately by default, or use schedule with a future publish_at. This tool never creates or publishes the announcement.',
            'parameters' => [
                'title' => ['type' => 'string', 'required' => true],
                'category' => ['type' => 'string', 'required' => true],
                'body' => ['type' => 'string', 'required' => true],
                'publish_mode' => ['type' => 'string', 'required' => false],
                'publish_at' => ['type' => 'string', 'required' => false],
                'ends_at' => ['type' => 'string', 'required' => false],
                'is_pinned' => ['type' => 'boolean', 'required' => false],
            ],
        ],
        'prepare_absence_request' => [
            'roles' => ['tenant'],
            'description' => 'Prepare an absence request for the authenticated tenant. Collect and validate start date, end date, and reason, then show the complete details and ask for explicit confirmation. This tool never submits the request.',
            'parameters' => [
                'start_date' => ['type' => 'string', 'required' => true],
                'end_date' => ['type' => 'string', 'required' => true],
                'reason' => ['type' => 'string', 'required' => true],
                'remarks' => ['type' => 'string', 'required' => false],
            ],
        ],
        'prepare_maintenance_request' => [
            'roles' => ['tenant'],
            'description' => 'Prepare a maintenance request for the authenticated tenant. Collect title, category, priority, and description, then show the complete details and ask for explicit confirmation. This tool never submits the request.',
            'parameters' => [
                'title' => ['type' => 'string', 'required' => true],
                'category' => ['type' => 'string', 'required' => true],
                'priority' => ['type' => 'string', 'required' => true],
                'description' => ['type' => 'string', 'required' => true],
            ],
        ],
    ];

    public function __construct(
        private KnowledgeRetrievalService $knowledgeRetrieval,
        private TenantChatDataService $tenantData,
        private ChatPendingActionService $pendingActions,
    ) {}

    /**
     * @return list<array{name: string, description: string, parameters: array<string, mixed>}>
     */
    public function definitionsFor(?User $actor): array
    {
        $role = $actor?->role ?? 'guest';

        return collect(self::TOOLS)
            ->filter(fn (array $tool): bool => in_array($role, $tool['roles'], true))
            ->map(fn (array $tool, string $name): array => [
                'name' => $name,
                'description' => $tool['description'],
                'parameters' => $tool['parameters'],
            ])
            ->values()
            ->all();
    }

    public function execute(
        string $name,
        array $arguments,
        ?User $actor,
        ?ChatConversation $conversation = null,
        ?ChatMessage $sourceMessage = null,
    ): array {
        $tool = self::TOOLS[$name] ?? null;

        if ($tool === null) {
            throw ValidationException::withMessages(['tool' => 'The requested tool is not available.']);
        }

        $role = $actor?->role ?? 'guest';

        if (! in_array($role, $tool['roles'], true)) {
            throw new AuthorizationException('This tool is not available to your account.');
        }

        return match ($name) {
            'get_current_user' => $this->withoutArguments(
                $arguments,
                fn (): array => $this->tenantData->currentUser($actor),
            ),
            'get_dormitory_information' => $this->searchKnowledge($arguments, $role),
            'get_room_availability' => $this->roomAvailability($arguments),
            'get_tenant_room' => $this->withoutArguments($arguments, fn (): array => [
                'assignment' => $this->tenantData->currentRoom($this->tenantActor($actor)),
            ]),
            'get_tenant_balance' => $this->withoutArguments($arguments, fn (): array => $this->tenantData->balance($this->tenantActor($actor))),
            'get_payment_history' => $this->listTool(
                $arguments,
                fn (int $limit): array => $this->tenantData->paymentHistory($this->tenantActor($actor), $limit),
                20,
                'payments',
            ),
            'get_electricity_bills' => $this->listTool(
                $arguments,
                fn (int $limit): array => $this->tenantData->electricityBills($this->tenantActor($actor), $limit),
                12,
                'bills',
            ),
            'get_maintenance_status' => $this->listTool(
                $arguments,
                fn (int $limit): array => $this->tenantData->maintenanceStatus($this->tenantActor($actor), $limit),
                20,
                'requests',
            ),
            'get_absence_status' => $this->listTool(
                $arguments,
                fn (int $limit): array => $this->tenantData->absenceStatus($this->tenantActor($actor), $limit),
                20,
                'requests',
            ),
            'get_application_status' => $this->listTool(
                $arguments,
                fn (int $limit): array => $this->tenantData->applicationStatus($this->tenantActor($actor), $limit),
                10,
                'applications',
            ),
            'get_announcements' => $this->listTool(
                $arguments,
                fn (int $limit): array => $this->tenantData->announcements($limit),
                10,
                'announcements',
            ),
            'get_room_occupancy_summary' => $this->withoutArguments($arguments, fn (): array => $this->occupancySummary()),
            'get_admin_overview' => $this->adminTool($arguments, fn (): array => $this->tenantData->adminOverview()),
            'get_admin_tenants' => $this->listTool(
                $arguments,
                fn (int $limit): array => $this->tenantData->adminTenants($limit),
                50,
                'tenants',
            ),
            'get_admin_pending_applications' => $this->listTool(
                $arguments,
                fn (int $limit): array => $this->tenantData->adminPendingApplications($limit),
                20,
                'applications',
            ),
            'get_admin_pending_absences' => $this->listTool(
                $arguments,
                fn (int $limit): array => $this->tenantData->adminPendingAbsences($limit),
                20,
                'requests',
            ),
            'get_admin_maintenance_requests' => $this->listTool(
                $arguments,
                fn (int $limit): array => $this->tenantData->adminMaintenanceRequests($limit),
                20,
                'requests',
            ),
            'get_admin_electricity_bills' => $this->listTool(
                $arguments,
                fn (int $limit): array => $this->tenantData->adminElectricityBills($limit),
                12,
                'bills',
            ),
            'prepare_announcement' => $this->prepareRequest(
                'announcement',
                $arguments,
                $actor,
                $conversation,
                $sourceMessage,
                ['title', 'category', 'body', 'publish_mode', 'publish_at', 'ends_at', 'is_pinned'],
            ),
            'prepare_absence_request' => $this->prepareRequest(
                'absence_request',
                $arguments,
                $actor,
                $conversation,
                $sourceMessage,
                ['start_date', 'end_date', 'reason', 'remarks'],
            ),
            'prepare_maintenance_request' => $this->prepareRequest(
                'maintenance_request',
                $arguments,
                $actor,
                $conversation,
                $sourceMessage,
                ['title', 'category', 'priority', 'description'],
            ),
        };
    }

    /**
     * @return array{message: string, source: string}|null
     */
    public function handlePendingActionResponse(
        ChatConversation $conversation,
        ChatMessage $message,
        ?User $actor,
    ): ?array {
        if ($actor === null || ! in_array($actor->role, ['tenant', 'admin'], true)) {
            return null;
        }

        return $this->pendingActions->handleConfirmation($conversation, $message, $actor);
    }

    private function searchKnowledge(array $arguments, string $role): array
    {
        $this->assertOnlyKeys($arguments, ['query']);
        $validated = Validator::make($arguments, [
            'query' => ['required', 'string', 'max:1000'],
        ])->validate();

        return [
            'sources' => array_map(
                fn (KnowledgeChunk $chunk): array => [
                    'content' => $chunk->content,
                    'source' => $chunk->source,
                    'updated_at' => $chunk->updatedAt,
                ],
                $this->knowledgeRetrieval->retrieve($validated['query'], $role),
            ),
        ];
    }

    private function withoutArguments(array $arguments, callable $operation): array
    {
        $this->assertOnlyKeys($arguments, []);

        return $operation();
    }

    private function listTool(
        array $arguments,
        callable $operation,
        int $defaultLimit,
        string $resultKey,
    ): array {
        $this->assertOnlyKeys($arguments, ['limit']);
        $validated = Validator::make($arguments, [
            'limit' => ['sometimes', 'integer', 'min:1', 'max:50'],
        ])->validate();

        return [
            $resultKey => $operation((int) ($validated['limit'] ?? $defaultLimit)),
        ];
    }

    private function assertOnlyKeys(array $arguments, array $allowedKeys): void
    {
        $unexpectedKeys = array_diff(array_keys($arguments), $allowedKeys);

        if ($unexpectedKeys !== []) {
            throw ValidationException::withMessages(['arguments' => 'The tool received unsupported arguments.']);
        }
    }

    private function occupancySummary(): array
    {
        $rooms = $this->tenantData->roomAvailability();

        return [
            'rooms_total' => count($rooms),
            'occupied_assignments' => array_sum(array_column($rooms, 'occupied_assignments')),
            'reserved_assignments' => array_sum(array_column($rooms, 'reserved_assignments')),
            'unassigned_spaces' => array_sum(array_column($rooms, 'remaining_capacity')),
        ];
    }

    private function roomAvailability(array $arguments): array
    {
        $this->assertOnlyKeys($arguments, ['available_only', 'limit']);
        $validated = Validator::make($arguments, [
            'available_only' => ['sometimes', 'boolean'],
            'limit' => ['sometimes', 'integer', 'min:1', 'max:100'],
        ])->validate();

        return [
            'rooms' => $this->tenantData->roomAvailability(
                in_array($validated['available_only'] ?? false, [true, 1, '1'], true),
                isset($validated['limit']) ? (int) $validated['limit'] : null,
            ),
        ];
    }

    private function adminTool(array $arguments, callable $operation): array
    {
        $this->assertOnlyKeys($arguments, []);

        return $operation();
    }

    private function prepareRequest(
        string $actionType,
        array $arguments,
        ?User $actor,
        ?ChatConversation $conversation,
        ?ChatMessage $sourceMessage,
        array $allowedKeys,
    ): array {
        $this->assertOnlyKeys($arguments, $allowedKeys);

        $requiredRole = $actionType === 'announcement' ? 'admin' : 'tenant';

        if ($actor === null || $actor->role !== $requiredRole) {
            throw new AuthorizationException('This chat action is not available to your account.');
        }

        if ($conversation === null || $sourceMessage === null) {
            throw ValidationException::withMessages([
                'conversation' => 'A chat conversation is required to prepare this request.',
            ]);
        }

        if ($actionType === 'announcement') {
            $arguments['publish_mode'] ??= 'now';
        }

        $action = $this->pendingActions->prepare(
            $actionType,
            $arguments,
            $conversation,
            $sourceMessage,
            $actor,
        );
        $status = $action->status === ChatPendingAction::STATUS_PENDING && $action->expires_at->isFuture()
            ? 'awaiting_confirmation'
            : $action->status;
        $actionLabel = $actionType === 'announcement' ? 'announcement' : 'request';
        $actorLabel = $actionType === 'announcement' ? 'administrator' : 'tenant';

        return [
            'status' => $status,
            'expires_at' => $action->expires_at->toIso8601String(),
            'details' => $action->payload,
            'result' => $action->result,
            'instructions' => $status === 'awaiting_confirmation'
                ? "Show these details to the {$actorLabel} and ask them to reply Confirm or Cancel. Do not claim the {$actionLabel} has been submitted or published."
                : "This {$actionLabel} draft is no longer pending. Use the saved result if present, and do not claim a new draft is waiting or submit it again.",
        ];
    }

    private function tenantActor(?User $actor): User
    {
        if ($actor === null || $actor->role !== 'tenant') {
            throw new AuthorizationException('This tool is available to tenants only.');
        }

        return $actor;
    }
}
