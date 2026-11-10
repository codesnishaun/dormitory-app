<?php

use App\Contracts\Chat\AIProviderInterface;
use App\Contracts\Chat\DocumentIngestionInterface;
use App\Contracts\Chat\EmbeddingProviderInterface;
use App\Contracts\Chat\VectorSearchInterface;
use App\Data\Chat\ChatPrompt;
use App\Data\Chat\KnowledgeChunk;
use App\Exceptions\Chat\ChatProviderException;
use App\Services\Chat\ChatService;
use App\Services\Chat\ChatToolRegistry;
use App\Services\Chat\GeminiAIProvider;
use App\Services\Chat\GeminiApiClient;
use App\Services\Chat\GeminiEmbeddingProvider;
use App\Services\Chat\KnowledgeRetrievalService;
use App\Services\Chat\QdrantDocumentIngestion;
use App\Services\Chat\QdrantVectorSearch;
use App\Services\Chat\UnconfiguredAIProvider;
use App\Services\Chat\UnconfiguredDocumentIngestion;
use App\Services\Chat\UnconfiguredEmbeddingProvider;
use App\Services\Chat\UnconfiguredVectorSearch;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

function configureGeminiChatProvider(): void
{
    config([
        'dora.llm.provider' => 'gemini',
        'dora.llm.model' => 'gemini-test-model-id',
        'dora.llm.api_key' => 'test-gemini-key',
        'dora.llm.base_url' => 'https://generativelanguage.googleapis.com/v1beta',
        'dora.llm.timeout' => 17,
    ]);
}

test('Gemini sends trusted instructions separately and returns a grounded completion', function () {
    configureGeminiChatProvider();
    Http::preventStrayRequests();
    Http::fake([
        'https://generativelanguage.googleapis.com/v1beta/models/gemini-test-model-id:generateContent' => Http::response([
            'candidates' => [[
                'content' => [
                    'parts' => [
                        ['text' => 'An administrator reviews applications.'],
                    ],
                ],
            ]],
        ]),
    ]);
    $prompt = new ChatPrompt(
        'Follow trusted DORA rules.',
        [['role' => 'user', 'content' => 'How are applications reviewed?']],
        [new KnowledgeChunk('faq-1', 'Applications are reviewed by an administrator.', 'Application guide', null, 'guest', isApproved: true)],
        [[
            'name' => 'get_dormitory_information',
            'description' => 'Search approved dormitory information.',
            'parameters' => ['query' => ['type' => 'string', 'required' => true]],
        ]],
        'guest',
    );

    $completion = app(GeminiAIProvider::class)->generate($prompt);

    expect($completion->content)->toBe('An administrator reviews applications.');
    expect($completion->toolCalls)->toBe([]);
    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://generativelanguage.googleapis.com/v1beta/models/gemini-test-model-id:generateContent'
        && $request->hasHeader('x-goog-api-key', 'test-gemini-key')
        && $request->data()['systemInstruction']['parts'][0]['text'] === 'Follow trusted DORA rules.'
        && $request->data()['contents'][0]['parts'][0]['text'] === 'How are applications reviewed?'
        && str_contains($request->data()['contents'][0]['parts'][1]['text'], 'Applications are reviewed by an administrator.')
        && $request->data()['tools'][0]['functionDeclarations'][0]['name'] === 'get_dormitory_information');
});

test('Gemini omits parameter schemas for tools without arguments', function () {
    configureGeminiChatProvider();
    Http::preventStrayRequests();
    Http::fake([
        'https://generativelanguage.googleapis.com/v1beta/models/gemini-test-model-id:generateContent' => Http::response([
            'candidates' => [[
                'content' => [
                    'parts' => [['text' => 'There are 12 active tenants.']],
                ],
            ]],
        ]),
    ]);
    $prompt = new ChatPrompt(
        'Follow trusted DORA rules.',
        [['role' => 'user', 'content' => 'List active tenants.']],
        [],
        [[
            'name' => 'get_admin_tenants',
            'description' => 'Get active tenants.',
            'parameters' => [],
        ]],
        'admin',
    );

    app(GeminiAIProvider::class)->generate($prompt);

    Http::assertSent(fn (Request $request): bool => ! array_key_exists(
        'parameters',
        $request->data()['tools'][0]['functionDeclarations'][0],
    ));
});

test('provider exceptions are logged without reporting their stack trace', function () {
    Log::spy();

    report(new ChatProviderException('Gemini rejected the request (HTTP 400).'));

    Log::shouldHaveReceived('warning')
        ->once()
        ->with('DORA provider operation failed.', [
            'message' => 'Gemini rejected the request (HTTP 400).',
            'error_code' => 'provider_error',
        ]);
    Log::shouldNotHaveReceived('error');
});

test('Gemini logs sanitized structured details for rejected streaming requests', function () {
    configureGeminiChatProvider();
    Http::preventStrayRequests();
    Log::spy();
    Http::fake([
        'https://generativelanguage.googleapis.com/v1beta/models/gemini-test-model-id:streamGenerateContent?alt=sse' => Http::response([
            'error' => [
                'code' => 400,
                'status' => 'INVALID_ARGUMENT',
                'message' => 'Unknown field "thoughtSignature". Credential AIza123456789012345678901234567890123456789. Invalid value private prompt.',
            ],
            'private_detail' => 'must not be logged',
        ], 400),
    ]);

    expect(fn () => app(GeminiApiClient::class)->postStream(
        'gemini-test-model-id',
        ['contents' => [['role' => 'user', 'parts' => [['text' => 'private prompt']]]]],
        'test-gemini-key',
        'https://generativelanguage.googleapis.com/v1beta',
        17,
        fn (array $chunk) => null,
    ))->toThrow(ChatProviderException::class, 'Gemini rejected the request (HTTP 400).');

    Log::shouldHaveReceived('warning')
        ->once()
        ->with('Gemini API request failed.', [
            'action' => 'streamGenerateContent',
            'http_status' => 400,
            'provider_code' => 400,
            'provider_status' => 'INVALID_ARGUMENT',
            'provider_message' => 'Unknown field "thoughtSignature". Credential [REDACTED]. Invalid value [REDACTED_INPUT].',
        ]);
});

test('Gemini streams text deltas while returning the complete validated response', function () {
    configureGeminiChatProvider();
    Http::preventStrayRequests();
    $stream = implode("\n\n", [
        'data: '.json_encode(['candidates' => [['content' => ['parts' => [['text' => 'The quiet hours ']]]]]]),
        'data: '.json_encode(['candidates' => [['content' => ['parts' => [['text' => 'begin at 10 PM.']]]]]]),
    ])."\n\n";
    Http::fake([
        'https://generativelanguage.googleapis.com/v1beta/models/gemini-test-model-id:streamGenerateContent?alt=sse' => Http::response($stream),
    ]);
    $prompt = new ChatPrompt(
        'Follow trusted DORA rules.',
        [['role' => 'user', 'content' => 'When are quiet hours?']],
        [],
        [],
        'guest',
    );
    $deltas = [];

    $completion = app(GeminiAIProvider::class)->generateStream(
        $prompt,
        function (string $text) use (&$deltas): void {
            $deltas[] = $text;
        },
    );

    expect($deltas)->toBe(['The quiet hours ', 'begin at 10 PM.']);
    expect($completion->content)->toBe('The quiet hours begin at 10 PM.');
    Http::assertSent(fn (Request $request): bool => str_contains($request->url(), ':streamGenerateContent?alt=sse')
        && $request->hasHeader('x-goog-api-key', 'test-gemini-key')
        && $request->data()['contents'][0]['parts'][0]['text'] === 'When are quiet hours?');
});

test('Gemini retries a transient streaming server error once', function () {
    configureGeminiChatProvider();
    Http::preventStrayRequests();
    $stream = 'data: '.json_encode(['candidates' => [['content' => ['parts' => [['text' => 'The quiet hours begin at 10 PM.']]]]]])."\n\n";
    $attempts = 0;
    Http::fake([
        'https://generativelanguage.googleapis.com/v1beta/models/gemini-test-model-id:streamGenerateContent?alt=sse' => function () use (&$attempts, $stream) {
            $attempts++;

            return $attempts === 1
                ? Http::response([], 503)
                : Http::response($stream);
        },
    ]);
    $prompt = new ChatPrompt(
        'Follow trusted DORA rules.',
        [['role' => 'user', 'content' => 'When are quiet hours?']],
        [],
        [],
        'guest',
    );

    $completion = app(GeminiAIProvider::class)->generateStream($prompt, fn (string $text) => null);

    expect($attempts)->toBe(2);
    expect($completion->content)->toBe('The quiet hours begin at 10 PM.');
});

test('Gemini preserves streamed function calls for authorized tool execution', function () {
    configureGeminiChatProvider();
    Http::preventStrayRequests();
    $stream = 'data: '.json_encode(['candidates' => [[
        'content' => ['parts' => [[
            'functionCall' => [
                'name' => 'get_dormitory_information',
                'args' => ['query' => 'quiet hours'],
            ],
        ]]],
    ]]])."\n\n";
    Http::fake([
        'https://generativelanguage.googleapis.com/v1beta/models/gemini-test-model-id:streamGenerateContent?alt=sse' => Http::response($stream),
    ]);
    $prompt = new ChatPrompt(
        'Follow trusted DORA rules.',
        [['role' => 'user', 'content' => 'When are quiet hours?']],
        [],
        [[
            'name' => 'get_dormitory_information',
            'description' => 'Search approved dormitory information.',
            'parameters' => ['query' => ['type' => 'string', 'required' => true]],
        ]],
        'guest',
    );
    $deltas = [];

    $completion = app(GeminiAIProvider::class)->generateStream(
        $prompt,
        function (string $text) use (&$deltas): void {
            $deltas[] = $text;
        },
    );

    expect($completion->toolCalls)->toHaveCount(1)
        ->and($completion->toolCalls[0]['name'])->toBe('get_dormitory_information')
        ->and($completion->toolCalls[0]['arguments'])->toBe(['query' => 'quiet hours']);
    expect($deltas)->toBe([]);
});

test('Gemini preserves thought signatures from streamed tool calls', function () {
    configureGeminiChatProvider();
    Http::preventStrayRequests();
    $stream = 'data: '.json_encode(['candidates' => [[
        'content' => ['parts' => [[
            'functionCall' => [
                'name' => 'get_admin_overview',
                'args' => [],
            ],
            'thoughtSignature' => 'opaque-gemini-signature',
        ]]],
    ]]])."\n\n";
    Http::fake([
        'https://generativelanguage.googleapis.com/v1beta/models/gemini-test-model-id:streamGenerateContent?alt=sse' => Http::response($stream),
    ]);
    $prompt = new ChatPrompt(
        'Follow trusted DORA rules.',
        [['role' => 'user', 'content' => 'Summarize dormitory metrics.']],
        [],
        [[
            'name' => 'get_admin_overview',
            'description' => 'Get dormitory metrics.',
            'parameters' => [],
        ]],
        'admin',
    );

    $completion = app(GeminiAIProvider::class)->generateStream($prompt, fn (string $text) => null);

    expect($completion->toolCalls[0]['thoughtSignature'])->toBe('opaque-gemini-signature');
});

test('Gemini returns only tool calls declared by the existing registry', function () {
    configureGeminiChatProvider();
    Http::preventStrayRequests();
    Http::fake([
        'https://generativelanguage.googleapis.com/v1beta/models/gemini-test-model-id:generateContent' => Http::response([
            'candidates' => [[
                'content' => [
                    'parts' => [[
                        'functionCall' => [
                            'name' => 'get_dormitory_information',
                            'args' => ['query' => 'quiet hours'],
                        ],
                    ]],
                ],
            ]],
        ]),
    ]);
    $prompt = new ChatPrompt(
        'Follow trusted DORA rules.',
        [['role' => 'user', 'content' => 'What are quiet hours?']],
        [],
        [[
            'name' => 'get_dormitory_information',
            'description' => 'Search approved dormitory information.',
            'parameters' => ['query' => ['type' => 'string', 'required' => true]],
        ]],
        'guest',
    );

    $completion = app(GeminiAIProvider::class)->generate($prompt);

    expect($completion->toolCalls)->toHaveCount(1)
        ->and($completion->toolCalls[0]['name'])->toBe('get_dormitory_information')
        ->and($completion->toolCalls[0]['arguments'])->toBe(['query' => 'quiet hours']);
});

test('Gemini sends authorized tool calls and results as structured conversation history', function () {
    configureGeminiChatProvider();
    Http::preventStrayRequests();
    Http::fake([
        'https://generativelanguage.googleapis.com/v1beta/models/gemini-test-model-id:generateContent' => Http::response([
            'candidates' => [[
                'content' => [
                    'parts' => [['text' => 'Quiet hours begin at 10 PM.']],
                ],
            ]],
        ]),
    ]);
    $prompt = new ChatPrompt(
        'Follow trusted DORA rules.',
        [
            ['role' => 'user', 'content' => 'What are quiet hours?'],
            [
                'role' => 'assistant',
                'tool_calls' => [[
                    'id' => 'call-1',
                    'name' => 'get_dormitory_information',
                    'arguments' => ['query' => 'quiet hours'],
                ]],
            ],
            [
                'role' => 'tool',
                'tool_call_id' => 'call-1',
                'name' => 'get_dormitory_information',
                'content' => '{"sources":[{"content":"Quiet hours begin at 10 PM."}]}',
            ],
        ],
        [],
        [],
        'guest',
    );

    $completion = app(GeminiAIProvider::class)->generate($prompt);

    expect($completion->content)->toBe('Quiet hours begin at 10 PM.');
    Http::assertSent(fn (Request $request): bool => $request->data()['contents'][1]['role'] === 'model'
        && $request->data()['contents'][1]['parts'][0]['functionCall']['name'] === 'get_dormitory_information'
        && $request->data()['contents'][2]['parts'][0]['functionResponse']['name'] === 'get_dormitory_information'
        && $request->data()['contents'][2]['parts'][0]['functionResponse']['response']['sources'][0]['content'] === 'Quiet hours begin at 10 PM.');
});

test('Gemini encodes empty tool args and list tool results as JSON objects for Struct fields', function () {
    configureGeminiChatProvider();
    Http::preventStrayRequests();
    Http::fake([
        'https://generativelanguage.googleapis.com/v1beta/models/gemini-test-model-id:generateContent' => Http::response([
            'candidates' => [[
                'content' => [
                    'parts' => [['text' => 'Room A-101 is available.']],
                ],
            ]],
        ]),
    ]);
    $prompt = new ChatPrompt(
        'Follow trusted DORA rules.',
        [
            ['role' => 'user', 'content' => 'Which rooms are available?'],
            [
                'role' => 'assistant',
                'tool_calls' => [[
                    'id' => 'call-1',
                    'name' => 'get_room_availability',
                    'arguments' => [],
                ]],
            ],
            [
                'role' => 'tool',
                'tool_call_id' => 'call-1',
                'name' => 'get_room_availability',
                'content' => '[{"room_number":"A-101","availability_status":"available"}]',
            ],
        ],
        [],
        [[
            'name' => 'get_room_availability',
            'description' => 'Get room availability.',
            'parameters' => [],
        ]],
        'guest',
    );

    app(GeminiAIProvider::class)->generate($prompt);

    Http::assertSent(function (Request $request): bool {
        $payload = $request->data();
        $response = $payload['contents'][2]['parts'][0]['functionResponse']['response'] ?? null;
        $body = $request->body();

        return is_array($response)
            && ($response['items'][0]['room_number'] ?? null) === 'A-101'
            && str_contains($body, '"args":{}')
            && str_contains($body, '"response":{"items":')
            && ! str_contains($body, '"args":[]')
            && ! str_contains($body, '"response":[');
    });
});

test('Gemini merges multiple tool results into one user turn with object responses', function () {
    configureGeminiChatProvider();
    Http::preventStrayRequests();
    Http::fake([
        'https://generativelanguage.googleapis.com/v1beta/models/gemini-test-model-id:generateContent' => Http::response([
            'candidates' => [[
                'content' => [
                    'parts' => [['text' => 'Overview ready.']],
                ],
            ]],
        ]),
    ]);
    $prompt = new ChatPrompt(
        'Follow trusted DORA rules.',
        [
            ['role' => 'user', 'content' => 'Give me an overview.'],
            [
                'role' => 'assistant',
                'tool_calls' => [
                    [
                        'id' => 'call-1',
                        'name' => 'get_admin_overview',
                        'arguments' => [],
                    ],
                    [
                        'id' => 'call-2',
                        'name' => 'get_admin_tenants',
                        'arguments' => ['limit' => 1],
                    ],
                ],
            ],
            [
                'role' => 'tool',
                'tool_call_id' => 'call-1',
                'name' => 'get_admin_overview',
                'content' => '{"rooms_total":3}',
            ],
            [
                'role' => 'tool',
                'tool_call_id' => 'call-2',
                'name' => 'get_admin_tenants',
                'content' => '{"tenants":[{"name":"Example Tenant"}]}',
            ],
        ],
        [],
        [],
        'admin',
    );

    app(GeminiAIProvider::class)->generate($prompt);

    Http::assertSent(function (Request $request): bool {
        $payload = $request->data();
        $body = $request->body();

        return count($payload['contents']) === 3
            && $payload['contents'][2]['role'] === 'user'
            && count($payload['contents'][2]['parts']) === 2
            && $payload['contents'][2]['parts'][0]['functionResponse']['name'] === 'get_admin_overview'
            && $payload['contents'][2]['parts'][1]['functionResponse']['name'] === 'get_admin_tenants'
            && str_contains($body, '"args":{}')
            && ! str_contains($body, '"args":[]');
    });
});

test('Gemini resends thought signatures with tool calls in follow-up history', function () {
    configureGeminiChatProvider();
    Http::preventStrayRequests();
    Http::fake([
        'https://generativelanguage.googleapis.com/v1beta/models/gemini-test-model-id:generateContent' => Http::response([
            'candidates' => [[
                'content' => [
                    'parts' => [['text' => 'There are 12 active tenants.']],
                ],
            ]],
        ]),
    ]);
    $prompt = new ChatPrompt(
        'Follow trusted DORA rules.',
        [
            ['role' => 'user', 'content' => 'List active tenants.'],
            [
                'role' => 'assistant',
                'tool_calls' => [[
                    'id' => 'call-1',
                    'name' => 'get_admin_tenants',
                    'arguments' => [],
                    'thoughtSignature' => 'opaque-gemini-signature',
                ]],
            ],
            [
                'role' => 'tool',
                'tool_call_id' => 'call-1',
                'name' => 'get_admin_tenants',
                'content' => '{"tenants":[{"name":"Example Tenant"}]}',
            ],
        ],
        [],
        [[
            'name' => 'get_admin_tenants',
            'description' => 'Get active tenants.',
            'parameters' => ['limit' => ['type' => 'integer', 'required' => false]],
        ]],
        'admin',
    );

    app(GeminiAIProvider::class)->generate($prompt);

    Http::assertSent(fn (Request $request): bool => ($request->data()['contents'][1]['parts'][0]['thoughtSignature'] ?? null) === 'opaque-gemini-signature'
        && $request->data()['contents'][2]['parts'][0]['functionResponse']['name'] === 'get_admin_tenants');
});

test('Gemini returns only cited source IDs supplied in the prompt', function () {
    configureGeminiChatProvider();
    Http::preventStrayRequests();
    Http::fake([
        'https://generativelanguage.googleapis.com/v1beta/models/gemini-test-model-id:generateContent' => Http::response([
            'candidates' => [[
                'content' => [
                    'parts' => [
                        ['text' => 'An administrator reviews applications. [[source:faq-1]]'],
                    ],
                ],
            ]],
        ]),
    ]);
    $prompt = new ChatPrompt(
        'Follow trusted DORA rules.',
        [['role' => 'user', 'content' => 'How are applications reviewed?']],
        [new KnowledgeChunk('faq-1', 'Applications are reviewed by an administrator.', 'Application guide', null, 'guest', isApproved: true)],
        [],
        'guest',
    );

    $completion = app(GeminiAIProvider::class)->generate($prompt);

    expect($completion->content)->toBe('An administrator reviews applications.')
        ->and($completion->sourceIds)->toBe(['faq-1']);
});

test('Gemini rejects malformed chat responses and undeclared tool calls', function (array $response) {
    configureGeminiChatProvider();
    Http::preventStrayRequests();
    Http::fake([
        'https://generativelanguage.googleapis.com/v1beta/models/gemini-test-model-id:generateContent' => Http::response($response),
    ]);
    $prompt = new ChatPrompt(
        'Follow trusted DORA rules.',
        [['role' => 'user', 'content' => 'What is the policy?']],
        [],
        [],
        'guest',
    );

    expect(fn () => app(GeminiAIProvider::class)->generate($prompt))
        ->toThrow(ChatProviderException::class);
})->with([
    'missing candidate' => [['candidates' => []]],
    'undeclared function' => [[
        'candidates' => [[
            'content' => [
                'parts' => [[
                    'functionCall' => ['name' => 'delete_tenant', 'args' => []],
                ]],
            ],
        ]],
    ]],
    'unknown source' => [[
        'candidates' => [[
            'content' => [
                'parts' => [['text' => 'A response. [[source:private-document]]']],
            ],
        ]],
    ]],
]);

test('service bindings select configured Gemini and Qdrant providers and preserve safe fallbacks', function () {
    config([
        'dora.llm.provider' => 'gemini',
        'dora.embedding.provider' => 'gemini',
        'dora.vector.provider' => 'qdrant',
    ]);

    expect(app(AIProviderInterface::class))->toBeInstanceOf(GeminiAIProvider::class)
        ->and(app(EmbeddingProviderInterface::class))->toBeInstanceOf(GeminiEmbeddingProvider::class)
        ->and(app(VectorSearchInterface::class))->toBeInstanceOf(QdrantVectorSearch::class)
        ->and(app(DocumentIngestionInterface::class))->toBeInstanceOf(QdrantDocumentIngestion::class);

    config([
        'dora.llm.provider' => 'unsupported',
        'dora.embedding.provider' => 'unsupported',
        'dora.vector.provider' => 'unsupported',
    ]);

    expect(app(AIProviderInterface::class))->toBeInstanceOf(UnconfiguredAIProvider::class)
        ->and(app(EmbeddingProviderInterface::class))->toBeInstanceOf(UnconfiguredEmbeddingProvider::class)
        ->and(app(VectorSearchInterface::class))->toBeInstanceOf(UnconfiguredVectorSearch::class)
        ->and(app(DocumentIngestionInterface::class))->toBeInstanceOf(UnconfiguredDocumentIngestion::class);
});

test('chat reports configured only when explicitly enabled and every runtime provider is configured', function () {
    config(['dora.ai_enabled' => true]);
    $provider = Mockery::mock(AIProviderInterface::class);
    $provider->shouldReceive('isConfigured')->twice()->andReturnTrue();
    $embeddingProvider = Mockery::mock(EmbeddingProviderInterface::class);
    $embeddingProvider->shouldReceive('isConfigured')->twice()->andReturnTrue();
    $vectorSearch = Mockery::mock(VectorSearchInterface::class);
    $vectorSearch->shouldReceive('isConfigured')->twice()->andReturn(false, true);
    $chat = new ChatService(
        $provider,
        new KnowledgeRetrievalService($embeddingProvider, $vectorSearch),
        Mockery::mock(ChatToolRegistry::class),
    );

    expect($chat->isConfigured())->toBeFalse()
        ->and($chat->isConfigured())->toBeTrue();

    config(['dora.ai_enabled' => false]);

    expect($chat->isConfigured())->toBeFalse();
});

test('chat reports unconfigured when retrieval or conversation limits are invalid', function (string $setting, int $value) {
    config([
        'dora.ai_enabled' => true,
        'dora.'.$setting => $value,
    ]);
    $provider = Mockery::mock(AIProviderInterface::class);
    $provider->shouldNotReceive('isConfigured');
    $embeddingProvider = Mockery::mock(EmbeddingProviderInterface::class);
    $embeddingProvider->shouldNotReceive('isConfigured');
    $vectorSearch = Mockery::mock(VectorSearchInterface::class);
    $vectorSearch->shouldNotReceive('isConfigured');
    $chat = new ChatService(
        $provider,
        new KnowledgeRetrievalService($embeddingProvider, $vectorSearch),
        Mockery::mock(ChatToolRegistry::class),
    );

    expect($chat->isConfigured())->toBeFalse();
})->with([
    'invalid retrieval limit' => ['retrieval_limit', 0],
    'invalid history limit' => ['history_limit', 101],
]);
