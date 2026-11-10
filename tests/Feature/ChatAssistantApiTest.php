<?php

use App\Contracts\Chat\AIProviderInterface;
use App\Contracts\Chat\EmbeddingProviderInterface;
use App\Contracts\Chat\VectorSearchInterface;
use App\Data\Chat\ChatCompletion;
use App\Data\Chat\ChatPrompt;
use App\Data\Chat\KnowledgeChunk;
use App\Exceptions\Chat\ChatProviderException;
use App\Models\AbsenceRequest;
use App\Models\ChatConversation;
use App\Models\ChatMessage;
use App\Models\ChatPendingAction;
use App\Models\Room;
use App\Models\RoomAssignment;
use App\Models\Transaction;
use App\Models\User;
use App\Services\Chat\ChatToolRegistry;
use App\Services\Chat\ConversationService;
use App\Services\Chat\KnowledgeRetrievalService;
use App\Services\TenantRequestService;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Validation\ValidationException;
use Mockery;

uses(RefreshDatabase::class);

test('guests can save conversations but receive a clear unavailable response until AI is configured', function () {
    $this->withSession([]);
    $this->withCredentials();
    $createResponse = $this->postJson(route('chat.conversations.store'))->assertCreated();
    $sessionCookie = config('session.cookie');
    $this->withCookie($sessionCookie, $createResponse->getCookie($sessionCookie)->getValue());
    $conversation = $createResponse->json('data');

    $messageResponse = $this->postJson(route('chat.messages.store', $conversation['id']), [
        'message' => 'How do I apply?',
    ]);
    $this->withCookie($sessionCookie, $messageResponse->getCookie($sessionCookie)->getValue());

    $messageResponse->assertStatus(503)
        ->assertJsonPath('error.code', 'provider_unavailable')
        ->assertJsonPath('user_message.content', 'How do I apply?');

    $this->assertDatabaseHas('chat_conversations', [
        'id' => $conversation['id'],
        'user_id' => null,
        'title' => 'How do I apply?',
    ]);
    $this->assertDatabaseHas('chat_messages', [
        'chat_conversation_id' => $conversation['id'],
        'role' => ChatMessage::ROLE_USER,
        'content' => 'How do I apply?',
    ]);
    $this->assertDatabaseMissing('chat_messages', [
        'chat_conversation_id' => $conversation['id'],
        'role' => ChatMessage::ROLE_ASSISTANT,
    ]);

    $this->getJson(route('chat.conversations.show', $conversation['id']))
        ->assertOk()
        ->assertJsonPath('data.messages.0.content', 'How do I apply?');
});

test('tenants cannot retrieve or delete another tenant conversation', function () {
    $owner = User::factory()->tenant()->create();
    $otherTenant = User::factory()->tenant()->create();

    $conversationId = $this->actingAs($owner)
        ->postJson(route('chat.conversations.store'))
        ->assertCreated()
        ->json('data.id');

    $this->actingAs($otherTenant)
        ->getJson(route('chat.conversations.show', $conversationId))
        ->assertNotFound();

    $this->actingAs($otherTenant)
        ->deleteJson(route('chat.conversations.destroy', $conversationId))
        ->assertNotFound();

    $this->assertDatabaseHas('chat_conversations', [
        'id' => $conversationId,
        'user_id' => $owner->id,
    ]);
});

test('guest session hashes cannot retrieve another guest conversation', function () {
    $conversation = app(ConversationService::class)->create(null, hash('sha256', 'guest-session-one'));

    expect(fn () => app(ConversationService::class)->findForActor(
        null,
        hash('sha256', 'guest-session-two'),
        $conversation->id,
    ))->toThrow(ModelNotFoundException::class);
});

test('the chat endpoint rejects invalid messages without persisting them', function () {
    $conversationId = $this->postJson(route('chat.conversations.store'))
        ->assertCreated()
        ->json('data.id');

    $this->postJson(route('chat.messages.store', $conversationId), ['message' => ''])
        ->assertUnprocessable()
        ->assertJsonValidationErrors('message');

    $this->assertDatabaseCount('chat_messages', 0);
});

test('chat requests are rate limited for guests', function () {
    for ($request = 0; $request < 10; $request++) {
        $this->getJson(route('chat.conversations.index'))->assertOk();
    }

    $this->getJson(route('chat.conversations.index'))->assertTooManyRequests();
});

test('tool permissions and tenant data stay scoped to the authenticated tenant', function () {
    $tenant = User::factory()->tenant()->create();
    $otherTenant = User::factory()->tenant()->create();
    Transaction::query()->create([
        'user_id' => $tenant->id,
        'type' => 'charge',
        'description' => 'Rent',
        'amount' => 5000,
        'transaction_date' => today()->toDateString(),
    ]);
    Transaction::query()->create([
        'user_id' => $otherTenant->id,
        'type' => 'charge',
        'description' => 'Private charge',
        'amount' => 9000,
        'transaction_date' => today()->toDateString(),
    ]);
    $registry = app(ChatToolRegistry::class);

    $balance = $registry->execute('get_tenant_balance', [], $tenant);

    expect($balance['outstanding_balance'])->toBe(5000.0);
    expect(array_column($registry->definitionsFor(null), 'name'))->not->toContain('get_tenant_balance');
    $admin = User::factory()->admin()->create();
    expect(array_column($registry->definitionsFor($admin), 'name'))->toContain('get_room_occupancy_summary');
    expect(fn () => $registry->execute('get_room_occupancy_summary', [], $tenant))
        ->toThrow(AuthorizationException::class);
    expect($registry->execute('get_room_occupancy_summary', [], $admin)['rooms_total'])->toBe(0);
    expect(fn () => $registry->execute('get_tenant_balance', [], null))
        ->toThrow(AuthorizationException::class);
    expect(fn () => $registry->execute('get_tenant_balance', ['tenant_id' => $otherTenant->id], $tenant))
        ->toThrow(ValidationException::class);
});

test('identity and administrative tools return role-scoped live data', function () {
    $admin = User::factory()->admin()->create(['name' => 'DORA Admin']);
    $tenant = User::factory()->tenant()->create(['name' => 'DORA Tenant']);
    Room::query()->create([
        'room_number' => 'A-101',
        'capacity' => 2,
        'monthly_rent' => 5000,
        'status' => 'available',
    ]);
    $registry = app(ChatToolRegistry::class);

    $identity = $registry->execute('get_current_user', [], $tenant);
    $rooms = $registry->execute('get_room_availability', ['available_only' => true], $admin);
    $overview = $registry->execute('get_admin_overview', [], $admin);

    expect($identity)->toBe([
        'authenticated' => true,
        'name' => 'DORA Tenant',
        'role' => 'tenant',
    ]);
    expect($rooms['rooms'])->toHaveCount(1)
        ->and($rooms['rooms'][0]['remaining_capacity'])->toBe(2)
        ->and($overview['rooms_total'])->toBe(1);
    $tenantTools = array_column($registry->definitionsFor($tenant), 'name');
    expect($tenantTools)->not->toContain('get_admin_overview');
    expect($tenantTools)->toContain('prepare_absence_request');
    expect($tenantTools)->toContain('prepare_maintenance_request');
    expect(fn () => $registry->execute('get_admin_overview', [], $tenant))
        ->toThrow(AuthorizationException::class);
    $adminTools = array_column($registry->definitionsFor($admin), 'name');
    expect($adminTools)->not->toContain('prepare_absence_request');
    expect($adminTools)->not->toContain('prepare_maintenance_request');
});

test('list tools expose and enforce optional result limits', function () {
    $admin = User::factory()->admin()->create();
    $tenant = User::factory()->tenant()->create();
    User::factory()->tenant()->create();
    Transaction::query()->create([
        'user_id' => $tenant->id,
        'type' => 'payment',
        'description' => 'First payment',
        'amount' => 100,
        'transaction_date' => today()->toDateString(),
    ]);
    Transaction::query()->create([
        'user_id' => $tenant->id,
        'type' => 'payment',
        'description' => 'Second payment',
        'amount' => 200,
        'transaction_date' => today()->toDateString(),
    ]);
    $registry = app(ChatToolRegistry::class);
    $adminDefinitions = $registry->definitionsFor($admin);
    $tenantDefinitions = $registry->definitionsFor($tenant);
    $adminTenantTool = collect($adminDefinitions)->firstWhere('name', 'get_admin_tenants');
    $tenantPaymentTool = collect($tenantDefinitions)->firstWhere('name', 'get_payment_history');

    $adminTenants = $registry->execute('get_admin_tenants', ['limit' => 1], $admin);
    $paymentHistory = $registry->execute('get_payment_history', ['limit' => 1], $tenant);

    expect($adminTenantTool['parameters'])->toHaveKey('limit')
        ->and($tenantPaymentTool['parameters'])->toHaveKey('limit');
    expect($adminTenants['tenants'])->toHaveCount(1)
        ->and($paymentHistory['payments'])->toHaveCount(1);
    expect(fn () => $registry->execute('get_admin_tenants', ['limit' => 51], $admin))
        ->toThrow(ValidationException::class);
});

test('admins can prepare and confirm announcement publication in chat', function () {
    $admin = User::factory()->admin()->create(['name' => 'DORA Admin']);
    config(['dora.ai_enabled' => true, 'dora.embedding.dimensions' => 2]);
    $provider = Mockery::mock(AIProviderInterface::class);
    $provider->shouldReceive('isConfigured')->once()->andReturnTrue();
    $provider->shouldReceive('generate')
        ->twice()
        ->with(Mockery::type(ChatPrompt::class))
        ->andReturn(
            new ChatCompletion('', toolCalls: [[
                'id' => 'announcement-draft',
                'name' => 'prepare_announcement',
                'arguments' => [
                    'title' => 'Community meeting',
                    'category' => 'Community event',
                    'body' => 'Join us in the common room on Friday.',
                ],
            ]]),
            new ChatCompletion('Review this announcement and reply Confirm or Cancel.'),
        );
    $embeddingProvider = Mockery::mock(EmbeddingProviderInterface::class);
    $embeddingProvider->shouldReceive('isConfigured')->twice()->andReturnTrue();
    $embeddingProvider->shouldReceive('embed')
        ->once()
        ->with('Make an announcement about a community meeting.')
        ->andReturn([0.25, 0.75]);
    $vectorSearch = Mockery::mock(VectorSearchInterface::class);
    $vectorSearch->shouldReceive('isConfigured')->twice()->andReturnTrue();
    $vectorSearch->shouldReceive('search')
        ->once()
        ->with([0.25, 0.75], 'admin', 5)
        ->andReturn([]);
    app()->instance(AIProviderInterface::class, $provider);
    app()->instance(EmbeddingProviderInterface::class, $embeddingProvider);
    app()->instance(VectorSearchInterface::class, $vectorSearch);

    $conversationId = $this->actingAs($admin)
        ->postJson(route('chat.conversations.store'))
        ->assertCreated()
        ->json('data.id');
    $draftResponse = $this->postJson(route('chat.messages.store', $conversationId), [
        'message' => 'Make an announcement about a community meeting.',
    ]);

    $draftResponse->assertCreated()
        ->assertJsonPath('assistant_message.content', 'Review this announcement and reply Confirm or Cancel.')
        ->assertJsonPath('assistant_message.source_references.0.source', 'Announcement draft');
    $this->assertDatabaseCount('announcements', 0);
    expect(array_column(app(ChatToolRegistry::class)->definitionsFor($admin), 'name'))
        ->toContain('prepare_announcement');

    $confirmationResponse = $this->postJson(route('chat.messages.store', $conversationId), [
        'message' => 'Confirm',
    ]);

    $confirmationResponse->assertCreated()
        ->assertJsonPath('assistant_message.content', 'Announcement published: Community meeting.');
    $this->assertDatabaseHas('announcements', [
        'title' => 'Community meeting',
        'category' => 'Community event',
        'body' => 'Join us in the common room on Friday.',
        'author_name' => 'DORA Admin',
        'is_pinned' => false,
    ]);
    $this->assertDatabaseCount('announcements', 1);
});

test('successful announcement drafts survive a failed Gemini follow-up without duplicate creation', function () {
    $admin = User::factory()->admin()->create(['name' => 'DORA Admin']);
    config(['dora.ai_enabled' => true, 'dora.embedding.dimensions' => 2]);
    $provider = Mockery::mock(AIProviderInterface::class);
    $provider->shouldReceive('isConfigured')->once()->andReturnTrue();
    $provider->shouldReceive('generate')
        ->once()
        ->ordered()
        ->with(Mockery::type(ChatPrompt::class))
        ->andReturn(new ChatCompletion('', toolCalls: [[
            'id' => 'announcement-draft',
            'name' => 'prepare_announcement',
            'arguments' => [
                'title' => 'Water outage',
                'category' => 'Utilities',
                'body' => 'Water will be off from 1 PM to 3 PM.',
            ],
        ]]));
    $provider->shouldReceive('generate')
        ->once()
        ->ordered()
        ->with(Mockery::type(ChatPrompt::class))
        ->andThrow(new ChatProviderException('Gemini rejected the request (HTTP 400).'));
    $embeddingProvider = Mockery::mock(EmbeddingProviderInterface::class);
    $embeddingProvider->shouldReceive('isConfigured')->twice()->andReturnTrue();
    $embeddingProvider->shouldReceive('embed')
        ->once()
        ->with('Announce a water outage this afternoon.')
        ->andReturn([0.25, 0.75]);
    $vectorSearch = Mockery::mock(VectorSearchInterface::class);
    $vectorSearch->shouldReceive('isConfigured')->twice()->andReturnTrue();
    $vectorSearch->shouldReceive('search')
        ->once()
        ->with([0.25, 0.75], 'admin', 5)
        ->andReturn([]);
    app()->instance(AIProviderInterface::class, $provider);
    app()->instance(EmbeddingProviderInterface::class, $embeddingProvider);
    app()->instance(VectorSearchInterface::class, $vectorSearch);

    $conversationId = $this->actingAs($admin)
        ->postJson(route('chat.conversations.store'))
        ->assertCreated()
        ->json('data.id');

    $response = $this->postJson(route('chat.messages.store', $conversationId), [
        'message' => 'Announce a water outage this afternoon.',
    ]);

    $response->assertCreated()
        ->assertJsonPath('assistant_message.source_references.0.source', 'Announcement draft');
    expect($response->json('assistant_message.content'))
        ->toContain('Water outage')
        ->toContain('Confirm')
        ->not->toContain('temporarily unable');
    $this->assertDatabaseCount('announcements', 0);
    $this->assertDatabaseCount('chat_pending_actions', 1);
    $this->assertDatabaseHas('chat_pending_actions', [
        'action_type' => 'announcement',
        'status' => ChatPendingAction::STATUS_PENDING,
    ]);

    $userMessageId = $response->json('user_message.id');
    $this->postJson(route('chat.messages.retry', [$conversationId, $userMessageId]))
        ->assertCreated();

    $this->assertDatabaseCount('chat_pending_actions', 1);
    $this->assertDatabaseCount('announcements', 0);

    $this->postJson(route('chat.messages.store', $conversationId), [
        'message' => 'Confirm',
    ])->assertCreated()
        ->assertJsonPath('assistant_message.content', 'Announcement published: Water outage.');
    $this->assertDatabaseCount('announcements', 1);
});

test('guests cannot prepare announcements', function () {
    $registry = app(ChatToolRegistry::class);

    expect(fn () => $registry->execute('prepare_announcement', [
        'title' => 'Guest notice',
        'category' => 'General update',
        'body' => 'Guests should not publish this.',
    ], null))->toThrow(AuthorizationException::class);
    expect(array_column($registry->definitionsFor(null), 'name'))
        ->not->toContain('prepare_announcement');
});

test('tenants cannot prepare announcements and administrators can cancel a draft', function () {
    $tenant = User::factory()->tenant()->create();
    $admin = User::factory()->admin()->create();
    $registry = app(ChatToolRegistry::class);

    expect(fn () => $registry->execute('prepare_announcement', [
        'title' => 'Closed laundry room',
        'category' => 'Maintenance',
        'body' => 'The laundry room is closed today.',
    ], $tenant))
        ->toThrow(AuthorizationException::class);
    expect(array_column($registry->definitionsFor($tenant), 'name'))
        ->not->toContain('prepare_announcement');

    $conversation = app(ConversationService::class)->create($admin, '');
    $sourceMessage = $conversation->messages()->create([
        'role' => ChatMessage::ROLE_USER,
        'content' => 'Tell residents the laundry room is closed.',
    ]);
    $registry->execute(
        'prepare_announcement',
        [
            'title' => 'Closed laundry room',
            'category' => 'Maintenance',
            'body' => 'The laundry room is closed today.',
        ],
        $admin,
        $conversation,
        $sourceMessage,
    );

    $this->actingAs($admin)->postJson(
        route('chat.messages.store', $conversation->id),
        ['message' => 'Cancel'],
    )->assertCreated()
        ->assertJsonPath('assistant_message.content', 'I cancelled that draft. No announcement was created or published.');
    $this->assertDatabaseCount('announcements', 0);
});

test('admins can schedule an announcement after explicit confirmation', function () {
    $admin = User::factory()->admin()->create();
    $publishAt = now()->addDays(3)->startOfMinute();
    $conversation = app(ConversationService::class)->create($admin, '');
    $sourceMessage = $conversation->messages()->create([
        'role' => ChatMessage::ROLE_USER,
        'content' => 'Schedule a utility notice for later this week.',
    ]);

    app(ChatToolRegistry::class)->execute(
        'prepare_announcement',
        [
            'title' => 'Water service update',
            'category' => 'Utilities',
            'body' => 'Water service will be interrupted for maintenance.',
            'publish_mode' => 'schedule',
            'publish_at' => $publishAt->toDateTimeString(),
        ],
        $admin,
        $conversation,
        $sourceMessage,
    );
    $this->assertDatabaseCount('announcements', 0);

    $this->actingAs($admin)->postJson(
        route('chat.messages.store', $conversation->id),
        ['message' => 'Confirm'],
    )->assertCreated()
        ->assertJsonPath('assistant_message.content', 'Announcement scheduled for Water service update on '.$publishAt->format('F j, Y g:i A').'.');

    $this->assertDatabaseHas('announcements', [
        'title' => 'Water service update',
        'publish_at' => $publishAt->toDateTimeString(),
    ]);
    $this->assertDatabaseCount('announcements', 1);
});

test('AI tool calls save tenant request drafts without submitting them', function () {
    $tenant = User::factory()->tenant()->create();
    $room = Room::query()->create([
        'room_number' => 'A-105',
        'capacity' => 1,
        'monthly_rent' => 5000,
        'status' => 'available',
    ]);
    RoomAssignment::query()->create([
        'user_id' => $tenant->id,
        'room_id' => $room->id,
        'status' => 'occupied',
        'start_date' => today()->toDateString(),
    ]);
    config(['dora.ai_enabled' => true, 'dora.embedding.dimensions' => 2]);
    $provider = Mockery::mock(AIProviderInterface::class);
    $provider->shouldReceive('isConfigured')->once()->andReturnTrue();
    $provider->shouldReceive('generate')
        ->twice()
        ->with(Mockery::type(ChatPrompt::class))
        ->andReturn(
            new ChatCompletion('', toolCalls: [[
                'id' => 'absence-draft',
                'name' => 'prepare_absence_request',
                'arguments' => [
                    'start_date' => today()->addDays(2)->toDateString(),
                    'end_date' => today()->addDays(4)->toDateString(),
                    'reason' => 'Family visit',
                ],
            ]]),
            new ChatCompletion('Please review the absence details and reply Confirm or Cancel.'),
        );
    $embeddingProvider = Mockery::mock(EmbeddingProviderInterface::class);
    $embeddingProvider->shouldReceive('isConfigured')->twice()->andReturnTrue();
    $embeddingProvider->shouldReceive('embed')
        ->once()
        ->with('I need to be away next week.')
        ->andReturn([0.25, 0.75]);
    $vectorSearch = Mockery::mock(VectorSearchInterface::class);
    $vectorSearch->shouldReceive('isConfigured')->twice()->andReturnTrue();
    $vectorSearch->shouldReceive('search')
        ->once()
        ->with([0.25, 0.75], 'tenant', 5)
        ->andReturn([]);
    app()->instance(AIProviderInterface::class, $provider);
    app()->instance(EmbeddingProviderInterface::class, $embeddingProvider);
    app()->instance(VectorSearchInterface::class, $vectorSearch);

    $conversationId = $this->actingAs($tenant)
        ->postJson(route('chat.conversations.store'))
        ->assertCreated()
        ->json('data.id');
    $response = $this->postJson(route('chat.messages.store', $conversationId), [
        'message' => 'I need to be away next week.',
    ]);

    $response->assertCreated()
        ->assertJsonPath('assistant_message.content', 'Please review the absence details and reply Confirm or Cancel.')
        ->assertJsonPath('assistant_message.source_references.0.source', 'Absence request draft');
    expect(ChatPendingAction::query()->sole()->status)->toBe(ChatPendingAction::STATUS_PENDING);
    $this->assertDatabaseCount('absence_requests', 0);
});

test('tenants can confirm a prepared absence while AI is unavailable and retries do not duplicate it', function () {
    $tenant = User::factory()->tenant()->create();
    $room = Room::query()->create([
        'room_number' => 'A-102',
        'capacity' => 1,
        'monthly_rent' => 5000,
        'status' => 'available',
    ]);
    RoomAssignment::query()->create([
        'user_id' => $tenant->id,
        'room_id' => $room->id,
        'status' => 'occupied',
        'start_date' => today()->toDateString(),
    ]);
    $conversation = app(ConversationService::class)->create($tenant, '');
    $sourceMessage = $conversation->messages()->create([
        'role' => ChatMessage::ROLE_USER,
        'content' => 'I need to be away next week.',
    ]);
    $prepared = app(ChatToolRegistry::class)->execute(
        'prepare_absence_request',
        [
            'start_date' => today()->addDays(2)->toDateString(),
            'end_date' => today()->addDays(4)->toDateString(),
            'reason' => 'Family visit',
        ],
        $tenant,
        $conversation,
        $sourceMessage,
    );

    expect($prepared['status'])->toBe('awaiting_confirmation');
    $this->assertDatabaseCount('absence_requests', 0);

    $response = $this->actingAs($tenant)->json(
        'POST',
        route('chat.messages.store', $conversation->id),
        ['message' => 'Confirm'],
        ['Accept' => 'text/event-stream'],
    );
    $streamedContent = $response->assertOk()->streamedContent();
    expect($streamedContent)->toContain('event: complete');
    expect($streamedContent)->toContain('Absence request');
    expect($streamedContent)->toContain('submitted successfully');
    $confirmationMessageId = $conversation->messages()
        ->where('role', ChatMessage::ROLE_USER)
        ->latest('id')
        ->value('id');
    $action = ChatPendingAction::query()->sole();

    expect($action->status)->toBe(ChatPendingAction::STATUS_CONFIRMED);
    $this->assertDatabaseCount('absence_requests', 1);

    $this->postJson(route('chat.messages.retry', [$conversation->id, $confirmationMessageId]), [])
        ->assertCreated()
        ->assertJsonPath('assistant_message.content', $action->result['message']);
    $this->assertDatabaseCount('absence_requests', 1);
});

test('tenants can confirm a prepared maintenance request', function () {
    $tenant = User::factory()->tenant()->create();
    $room = Room::query()->create([
        'room_number' => 'A-106',
        'capacity' => 1,
        'monthly_rent' => 5000,
        'status' => 'available',
    ]);
    RoomAssignment::query()->create([
        'user_id' => $tenant->id,
        'room_id' => $room->id,
        'status' => 'occupied',
        'start_date' => today()->toDateString(),
    ]);
    $conversation = app(ConversationService::class)->create($tenant, '');
    $sourceMessage = $conversation->messages()->create([
        'role' => ChatMessage::ROLE_USER,
        'content' => 'The sink is leaking.',
    ]);
    $prepared = app(ChatToolRegistry::class)->execute(
        'prepare_maintenance_request',
        [
            'title' => 'Leaking sink',
            'category' => 'Plumbing',
            'priority' => 'High',
            'description' => 'Water leaks from the kitchen sink pipe.',
        ],
        $tenant,
        $conversation,
        $sourceMessage,
    );

    expect($prepared['status'])->toBe('awaiting_confirmation');

    $this->actingAs($tenant)->postJson(
        route('chat.messages.store', $conversation->id),
        ['message' => 'Confirm'],
    )->assertCreated()
        ->assertJsonPath('assistant_message.source_references.0.source', 'Maintenance request');
    $this->assertDatabaseHas('maintenance_requests', [
        'user_id' => $tenant->id,
        'room_assignment_id' => RoomAssignment::query()->sole()->id,
        'title' => 'Leaking sink',
        'category' => 'Plumbing',
        'priority' => 'High',
        'status' => 'Pending',
    ]);
    $this->assertDatabaseCount('maintenance_requests', 1);
});

test('tenants can cancel or expire a prepared absence without creating a request', function () {
    $tenant = User::factory()->tenant()->create();
    $room = Room::query()->create([
        'room_number' => 'A-103',
        'capacity' => 1,
        'monthly_rent' => 5000,
        'status' => 'available',
    ]);
    RoomAssignment::query()->create([
        'user_id' => $tenant->id,
        'room_id' => $room->id,
        'status' => 'occupied',
        'start_date' => today()->toDateString(),
    ]);
    $conversation = app(ConversationService::class)->create($tenant, '');
    $sourceMessage = $conversation->messages()->create([
        'role' => ChatMessage::ROLE_USER,
        'content' => 'Please record my absence.',
    ]);
    app(ChatToolRegistry::class)->execute(
        'prepare_absence_request',
        [
            'start_date' => today()->addDays(2)->toDateString(),
            'end_date' => today()->addDays(3)->toDateString(),
            'reason' => 'Visiting family',
        ],
        $tenant,
        $conversation,
        $sourceMessage,
    );

    $cancelResponse = $this->actingAs($tenant)->postJson(
        route('chat.messages.store', $conversation->id),
        ['message' => 'Cancel'],
    )->assertCreated()
        ->assertJsonPath('assistant_message.content', 'I cancelled that draft. No request was submitted.');
    $cancelMessageId = $cancelResponse->json('user_message.id');

    expect(ChatPendingAction::query()->sole()->status)->toBe(ChatPendingAction::STATUS_CANCELLED);
    $this->assertDatabaseCount('absence_requests', 0);

    $this->postJson(route('chat.messages.retry', [$conversation->id, $cancelMessageId]), [])
        ->assertCreated()
        ->assertJsonPath('assistant_message.content', $cancelResponse->json('assistant_message.content'));

    $nextSourceMessage = $conversation->messages()->create([
        'role' => ChatMessage::ROLE_USER,
        'content' => 'Prepare another absence.',
    ]);
    app(ChatToolRegistry::class)->execute(
        'prepare_absence_request',
        [
            'start_date' => today()->addDays(5)->toDateString(),
            'end_date' => today()->addDays(6)->toDateString(),
            'reason' => 'Another visit',
        ],
        $tenant,
        $conversation,
        $nextSourceMessage,
    );
    $this->travel(16)->minutes();

    $this->postJson(route('chat.messages.store', $conversation->id), ['message' => 'Confirm'])
        ->assertCreated()
        ->assertJsonPath('assistant_message.content', 'That request draft has expired. Please describe the request again to start a new one.');
    expect(ChatPendingAction::query()->latest('id')->firstOrFail()->status)
        ->toBe(ChatPendingAction::STATUS_EXPIRED);
    $this->assertDatabaseCount('absence_requests', 0);
});

test('absence requests block pending or approved overlaps but allow rejected dates to be reused', function () {
    $tenant = User::factory()->tenant()->create();
    $room = Room::query()->create([
        'room_number' => 'A-104',
        'capacity' => 1,
        'monthly_rent' => 5000,
        'status' => 'available',
    ]);
    $assignment = RoomAssignment::query()->create([
        'user_id' => $tenant->id,
        'room_id' => $room->id,
        'status' => 'occupied',
        'start_date' => today()->toDateString(),
    ]);
    $dates = [
        'start_date' => today()->addDays(2)->toDateString(),
        'end_date' => today()->addDays(4)->toDateString(),
        'reason' => 'Family visit',
    ];
    $assignment->absenceRequests()->create($dates + ['status' => AbsenceRequest::STATUS_PENDING]);

    expect(fn () => app(TenantRequestService::class)->createAbsenceRequest($tenant, $dates))
        ->toThrow(ValidationException::class);
    $assignment->absenceRequests()->firstOrFail()->update(['status' => AbsenceRequest::STATUS_REJECTED]);

    $request = app(TenantRequestService::class)->createAbsenceRequest($tenant, $dates);

    expect($request->status)->toBe(AbsenceRequest::STATUS_PENDING);
    $request->update(['status' => AbsenceRequest::STATUS_REJECTED]);
    $assignment->absenceRequests()->create($dates + ['status' => AbsenceRequest::STATUS_APPROVED]);

    expect(fn () => app(TenantRequestService::class)->createAbsenceRequest($tenant, $dates))
        ->toThrow(ValidationException::class);
    $this->assertDatabaseCount('absence_requests', 3);
});

test('configured provider contracts can return a grounded reply with an approved source reference', function () {
    $this->withSession([]);
    $this->withCredentials();
    config(['dora.ai_enabled' => true, 'dora.embedding.dimensions' => 2]);
    $source = new KnowledgeChunk(
        'faq-1',
        'Applications are reviewed by an administrator.',
        'Application guide',
        '2026-10-01',
        'guest',
        isApproved: true,
    );
    $provider = Mockery::mock(AIProviderInterface::class);
    $provider->shouldReceive('isConfigured')->once()->andReturnTrue();
    $provider->shouldReceive('generate')
        ->once()
        ->with(Mockery::type(ChatPrompt::class))
        ->andReturn(new ChatCompletion('An administrator reviews applications.', sourceIds: ['faq-1']));
    $embeddingProvider = Mockery::mock(EmbeddingProviderInterface::class);
    $embeddingProvider->shouldReceive('isConfigured')->twice()->andReturnTrue();
    $embeddingProvider->shouldReceive('embed')->once()->with('How are applications reviewed?')->andReturn([0.25, 0.75]);
    $vectorSearch = Mockery::mock(VectorSearchInterface::class);
    $vectorSearch->shouldReceive('isConfigured')->twice()->andReturnTrue();
    $vectorSearch->shouldReceive('search')
        ->once()
        ->with([0.25, 0.75], 'guest', 5)
        ->andReturn([$source]);
    app()->instance(AIProviderInterface::class, $provider);
    app()->instance(EmbeddingProviderInterface::class, $embeddingProvider);
    app()->instance(VectorSearchInterface::class, $vectorSearch);
    $createResponse = $this->postJson(route('chat.conversations.store'))->assertCreated();
    $sessionCookie = config('session.cookie');
    $this->withCookie($sessionCookie, $createResponse->getCookie($sessionCookie)->getValue());
    $conversationId = $createResponse->json('data.id');

    $response = $this->postJson(route('chat.messages.store', $conversationId), [
        'message' => 'How are applications reviewed?',
    ]);

    $response->assertCreated()
        ->assertJsonPath('assistant_message.content', 'An administrator reviews applications.')
        ->assertJsonPath('assistant_message.source_references.0.type', 'knowledge')
        ->assertJsonPath('assistant_message.source_references.0.source', 'Application guide');
    $this->assertDatabaseHas('chat_messages', [
        'chat_conversation_id' => $conversationId,
        'role' => ChatMessage::ROLE_ASSISTANT,
        'content' => 'An administrator reviews applications.',
    ]);
});

test('assistant responses identify tenant records checked through authorized tools', function () {
    $tenant = User::factory()->tenant()->create();
    config(['dora.ai_enabled' => true, 'dora.embedding.dimensions' => 2]);
    $provider = Mockery::mock(AIProviderInterface::class);
    $provider->shouldReceive('isConfigured')->once()->andReturnTrue();
    $provider->shouldReceive('generate')
        ->twice()
        ->with(Mockery::type(ChatPrompt::class))
        ->andReturn(
            new ChatCompletion('', toolCalls: [[
                'id' => 'balance-check',
                'name' => 'get_tenant_balance',
                'arguments' => [],
            ]]),
            new ChatCompletion('Your current balance is recorded in your account.'),
        );
    $embeddingProvider = Mockery::mock(EmbeddingProviderInterface::class);
    $embeddingProvider->shouldReceive('isConfigured')->twice()->andReturnTrue();
    $embeddingProvider->shouldReceive('embed')->once()->with('What is my balance?')->andReturn([0.25, 0.75]);
    $vectorSearch = Mockery::mock(VectorSearchInterface::class);
    $vectorSearch->shouldReceive('isConfigured')->twice()->andReturnTrue();
    $vectorSearch->shouldReceive('search')->once()->with([0.25, 0.75], 'tenant', 5)->andReturn([]);
    app()->instance(AIProviderInterface::class, $provider);
    app()->instance(EmbeddingProviderInterface::class, $embeddingProvider);
    app()->instance(VectorSearchInterface::class, $vectorSearch);

    $conversationResponse = $this->actingAs($tenant)
        ->postJson(route('chat.conversations.store'))
        ->assertCreated();
    $conversationId = $conversationResponse->json('data.id');

    $this->postJson(route('chat.messages.store', $conversationId), [
        'message' => 'What is my balance?',
    ])->assertCreated()
        ->assertJsonPath('assistant_message.source_references.0.type', 'system_data')
        ->assertJsonPath('assistant_message.source_references.0.source', 'Account balance');
});

test('chat messages can stream assistant text and persist the completed response', function () {
    $this->withSession([]);
    $this->withCredentials();
    config(['dora.ai_enabled' => true, 'dora.embedding.dimensions' => 2]);
    $provider = Mockery::mock(AIProviderInterface::class);
    $provider->shouldReceive('isConfigured')->times(2)->andReturnTrue();
    $provider->shouldReceive('generate')
        ->once()
        ->with(Mockery::type(ChatPrompt::class))
        ->andReturn(new ChatCompletion('DORA streamed reply.'));
    $embeddingProvider = Mockery::mock(EmbeddingProviderInterface::class);
    $embeddingProvider->shouldReceive('isConfigured')->times(3)->andReturnTrue();
    $embeddingProvider->shouldReceive('embed')->once()->with('Hello DORA')->andReturn([0.25, 0.75]);
    $vectorSearch = Mockery::mock(VectorSearchInterface::class);
    $vectorSearch->shouldReceive('isConfigured')->times(3)->andReturnTrue();
    $vectorSearch->shouldReceive('search')->once()->with([0.25, 0.75], 'guest', 5)->andReturn([]);
    app()->instance(AIProviderInterface::class, $provider);
    app()->instance(EmbeddingProviderInterface::class, $embeddingProvider);
    app()->instance(VectorSearchInterface::class, $vectorSearch);
    $conversationResponse = $this->postJson(route('chat.conversations.store'))->assertCreated();
    $sessionCookie = config('session.cookie');
    $this->withCookie($sessionCookie, $conversationResponse->getCookie($sessionCookie)->getValue());
    $conversationId = $conversationResponse->json('data.id');

    $response = $this->json(
        'POST',
        route('chat.messages.store', $conversationId),
        ['message' => 'Hello DORA'],
        ['Accept' => 'text/event-stream'],
    );

    $response->assertOk()->assertHeader('Content-Type', 'text/event-stream; charset=utf-8');
    $streamedContent = $response->streamedContent();

    expect($streamedContent)->toContain('event: delta')
        ->toContain('DORA streamed reply.')
        ->toContain('event: complete');
    $this->assertDatabaseHas('chat_messages', [
        'chat_conversation_id' => $conversationId,
        'role' => ChatMessage::ROLE_ASSISTANT,
        'content' => 'DORA streamed reply.',
    ]);
});

test('chat streaming returns a safe error event and preserves the user message when the provider fails', function () {
    $this->withSession([]);
    $this->withCredentials();
    config(['dora.ai_enabled' => true, 'dora.embedding.dimensions' => 2]);
    $provider = Mockery::mock(AIProviderInterface::class);
    $provider->shouldReceive('isConfigured')->times(2)->andReturnTrue();
    $provider->shouldReceive('generate')
        ->once()
        ->with(Mockery::type(ChatPrompt::class))
        ->andThrow(new ChatProviderException('Gemini timed out.'));
    $embeddingProvider = Mockery::mock(EmbeddingProviderInterface::class);
    $embeddingProvider->shouldReceive('isConfigured')->times(3)->andReturnTrue();
    $embeddingProvider->shouldReceive('embed')->once()->with('Hello DORA')->andReturn([0.25, 0.75]);
    $vectorSearch = Mockery::mock(VectorSearchInterface::class);
    $vectorSearch->shouldReceive('isConfigured')->times(3)->andReturnTrue();
    $vectorSearch->shouldReceive('search')->once()->with([0.25, 0.75], 'guest', 5)->andReturn([]);
    app()->instance(AIProviderInterface::class, $provider);
    app()->instance(EmbeddingProviderInterface::class, $embeddingProvider);
    app()->instance(VectorSearchInterface::class, $vectorSearch);
    $conversationResponse = $this->postJson(route('chat.conversations.store'))->assertCreated();
    $sessionCookie = config('session.cookie');
    $this->withCookie($sessionCookie, $conversationResponse->getCookie($sessionCookie)->getValue());
    $conversationId = $conversationResponse->json('data.id');

    $response = $this->json(
        'POST',
        route('chat.messages.store', $conversationId),
        ['message' => 'Hello DORA'],
        ['Accept' => 'text/event-stream'],
    );

    $streamedContent = $response->assertOk()->streamedContent();

    expect($streamedContent)->toContain('event: error')
        ->toContain('DORA is temporarily unable to generate a response. Please try again.')
        ->not->toContain('Gemini timed out.');
    $this->assertDatabaseHas('chat_messages', [
        'chat_conversation_id' => $conversationId,
        'role' => ChatMessage::ROLE_USER,
        'content' => 'Hello DORA',
    ]);
    $this->assertDatabaseMissing('chat_messages', [
        'chat_conversation_id' => $conversationId,
        'role' => ChatMessage::ROLE_ASSISTANT,
    ]);
});

test('knowledge retrieval excludes unapproved private and expired documents for guests', function () {
    config(['dora.embedding.dimensions' => 1]);
    $embeddingProvider = Mockery::mock(EmbeddingProviderInterface::class);
    $embeddingProvider->shouldReceive('isConfigured')->once()->andReturnTrue();
    $embeddingProvider->shouldReceive('embed')->once()->with('What are the rules?')->andReturn([0.5]);
    $vectorSearch = Mockery::mock(VectorSearchInterface::class);
    $vectorSearch->shouldReceive('isConfigured')->once()->andReturnTrue();
    $vectorSearch->shouldReceive('search')
        ->once()
        ->with([0.5], 'guest', 5)
        ->andReturn([
            new KnowledgeChunk('public', 'Public policy.', 'Rules', null, 'guest', isApproved: true),
            new KnowledgeChunk('tenant', 'Private policy.', 'Tenant guide', null, 'tenant', isApproved: true),
            new KnowledgeChunk('expired', 'Old policy.', 'Old rules', null, 'guest', expiresAt: '2020-01-01', isApproved: true),
            new KnowledgeChunk('unapproved', 'Draft policy.', 'Draft', null, 'guest'),
        ]);
    app()->instance(EmbeddingProviderInterface::class, $embeddingProvider);
    app()->instance(VectorSearchInterface::class, $vectorSearch);

    $chunks = app(KnowledgeRetrievalService::class)->retrieve('What are the rules?', 'guest');

    expect(array_column($chunks, 'id'))->toBe(['public']);
});

test('clearing a conversation deletes its saved messages', function () {
    $this->withSession([]);
    $this->withCredentials();
    $createResponse = $this->postJson(route('chat.conversations.store'))->assertCreated();
    $sessionCookie = config('session.cookie');
    $this->withCookie($sessionCookie, $createResponse->getCookie($sessionCookie)->getValue());
    $conversationId = $createResponse->json('data.id');
    $conversation = ChatConversation::query()->findOrFail($conversationId);
    $conversation->messages()->create([
        'role' => ChatMessage::ROLE_USER,
        'content' => 'Please clear this.',
    ]);

    $this->deleteJson(route('chat.conversations.destroy', $conversation->id))->assertNoContent();

    $this->assertDatabaseMissing('chat_conversations', ['id' => $conversation->id]);
    $this->assertDatabaseMissing('chat_messages', ['chat_conversation_id' => $conversation->id]);
});
