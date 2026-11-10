<?php

use App\Exceptions\Chat\ChatProviderException;
use App\Services\Chat\QdrantVectorSearch;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function configureQdrantVectorSearch(): void
{
    config([
        'dora.vector.provider' => 'qdrant',
        'dora.vector.endpoint' => 'http://localhost:6333',
        'dora.vector.api_key' => 'test-qdrant-key',
        'dora.vector.index' => 'dora_knowledge',
        'dora.vector.namespace' => 'dormitory',
        'dora.embedding.dimensions' => 3,
    ]);
}

function qdrantTestCollectionResponse(): array
{
    return [
        'result' => [
            'config' => [
                'params' => [
                    'vectors' => ['size' => 3, 'distance' => 'Cosine'],
                ],
            ],
            'payload_schema' => [
                'effective_from' => ['data_type' => 'datetime'],
                'expires_at' => ['data_type' => 'datetime'],
            ],
        ],
    ];
}

function qdrantTestPayload(array $overrides = []): array
{
    return array_merge([
        'chunk_id' => 'chunk-1',
        'source_document_id' => 'handbook',
        'source' => 'Dormitory handbook',
        'document_version' => 'v1',
        'audience' => 'tenant',
        'approval_status' => 'approved',
        'updated_at' => '2026-11-08T00:00:00Z',
        'effective_from' => null,
        'expires_at' => null,
        'content' => 'Quiet hours start at 10 PM.',
    ], $overrides);
}

test('Qdrant checks collection existence and creates a cosine collection with configured dimensions', function () {
    configureQdrantVectorSearch();
    Http::preventStrayRequests();
    $collectionUrl = 'http://localhost:6333/collections/dora_knowledge';
    Http::fake([
        $collectionUrl => function (Request $request) {
            return $request->method() === 'GET'
                ? Http::response([], 404)
                : Http::response(['result' => true]);
        },
    ]);
    $vectorSearch = app(QdrantVectorSearch::class);

    expect($vectorSearch->collectionExists())->toBeFalse();
    $vectorSearch->ensureCollection();
    Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
        && $request->url() === $collectionUrl
        && $request->hasHeader('api-key', 'test-qdrant-key')
        && $request->data() === ['vectors' => ['size' => 3, 'distance' => 'Cosine']]);
});

test('Qdrant searches with server-side audience, approval, date, and namespace filters', function () {
    configureQdrantVectorSearch();
    $this->travelTo('2026-11-08 12:00:00');
    Http::preventStrayRequests();
    Http::fake([
        'http://localhost:6333/collections/dora_knowledge' => Http::response(qdrantTestCollectionResponse()),
        'http://localhost:6333/collections/dora_knowledge/points/search' => Http::response([
            'result' => [[
                'id' => 'point-1',
                'score' => 0.91,
                'payload' => qdrantTestPayload(),
            ]],
        ]),
    ]);

    $chunks = app(QdrantVectorSearch::class)->search([0.1, 0.2, 0.3], 'tenant', 5);

    expect($chunks)->toHaveCount(1)
        ->and($chunks[0]->id)->toBe('chunk-1')
        ->and($chunks[0]->sourceDocumentId)->toBe('handbook')
        ->and($chunks[0]->documentVersion)->toBe('v1')
        ->and($chunks[0]->isApproved)->toBeTrue();
    Http::assertSent(fn (Request $request): bool => $request->url() === 'http://localhost:6333/collections/dora_knowledge/points/search'
        && $request->hasHeader('api-key', 'test-qdrant-key')
        && $request->data()['filter']['must'][0]['match']['any'] === ['guest', 'tenant']
        && $request->data()['filter']['must'][1]['match']['value'] === 'approved'
        && $request->data()['filter']['must'][2]['should'][0]['range']['lte'] === '2026-11-08T00:00:00Z'
        && $request->data()['filter']['must'][4]['match']['value'] === 'dormitory'
        && $request->data()['limit'] === 5);
});

test('Qdrant creates missing datetime payload indexes before applying date filters', function () {
    configureQdrantVectorSearch();
    $this->travelTo('2026-11-08 12:00:00');
    Http::preventStrayRequests();
    Http::fake([
        'http://localhost:6333/collections/dora_knowledge' => Http::response([
            'result' => [
                ...qdrantTestCollectionResponse()['result'],
                'payload_schema' => [],
            ],
        ]),
        'http://localhost:6333/collections/dora_knowledge/index?wait=true' => Http::response(['result' => true]),
        'http://localhost:6333/collections/dora_knowledge/points/search' => Http::response(['result' => []]),
    ]);

    $chunks = app(QdrantVectorSearch::class)->search([0.1, 0.2, 0.3], 'guest');

    expect($chunks)->toBe([]);
    Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
        && $request->url() === 'http://localhost:6333/collections/dora_knowledge/index?wait=true'
        && $request->data() === ['field_name' => 'effective_from', 'field_schema' => 'datetime']);
    Http::assertSent(fn (Request $request): bool => $request->method() === 'PUT'
        && $request->url() === 'http://localhost:6333/collections/dora_knowledge/index?wait=true'
        && $request->data() === ['field_name' => 'expires_at', 'field_schema' => 'datetime']);
    Http::assertSent(fn (Request $request): bool => $request->url() === 'http://localhost:6333/collections/dora_knowledge/points/search'
        && $request->data()['filter']['must'][2]['should'][0]['range']['lte'] === '2026-11-08T00:00:00Z'
        && $request->data()['filter']['must'][3]['should'][0]['range']['gte'] === '2026-11-08T00:00:00Z');
});

test('Qdrant refuses date filters when an existing payload index has the wrong type', function () {
    configureQdrantVectorSearch();
    Http::preventStrayRequests();
    Http::fake([
        'http://localhost:6333/collections/dora_knowledge' => Http::response([
            'result' => [
                ...qdrantTestCollectionResponse()['result'],
                'payload_schema' => [
                    'effective_from' => ['data_type' => 'keyword'],
                ],
            ],
        ]),
    ]);

    expect(fn () => app(QdrantVectorSearch::class)->search([0.1, 0.2, 0.3], 'guest'))
        ->toThrow(ChatProviderException::class, 'The Qdrant effective_from payload index must use the datetime type.');
});

test('Qdrant rejects missing or malformed knowledge payload metadata', function (array $payload) {
    configureQdrantVectorSearch();
    Http::preventStrayRequests();
    Http::fake([
        'http://localhost:6333/collections/dora_knowledge' => Http::response(qdrantTestCollectionResponse()),
        'http://localhost:6333/collections/dora_knowledge/points/search' => Http::response([
            'result' => [['id' => 'point-1', 'payload' => $payload]],
        ]),
    ]);

    expect(fn () => app(QdrantVectorSearch::class)->search([0.1, 0.2, 0.3], 'guest'))
        ->toThrow(ChatProviderException::class);
})->with([
    'missing payload' => [[]],
    'invalid audience' => [qdrantTestPayload(['audience' => 'tenant_admin'])],
    'invalid approval status' => [qdrantTestPayload(['approval_status' => 'draft'])],
    'invalid date' => [qdrantTestPayload(['updated_at' => '2026-99-99'])],
]);

test('Qdrant rejects mismatched collection configuration and invalid vector dimensions', function () {
    configureQdrantVectorSearch();
    Http::preventStrayRequests();
    Http::fake([
        'http://localhost:6333/collections/dora_knowledge' => Http::response([
            'result' => ['config' => ['params' => ['vectors' => ['size' => 4, 'distance' => 'Dot']]]],
        ]),
    ]);
    $vectorSearch = app(QdrantVectorSearch::class);

    expect(fn () => $vectorSearch->collectionExists())
        ->toThrow(ChatProviderException::class, 'The Qdrant collection has incompatible vector dimensions or distance settings.');
    expect(fn () => $vectorSearch->search([0.1, 0.2], 'guest'))
        ->toThrow(ChatProviderException::class, 'The Qdrant vector dimension does not match the embedding configuration.');
});

test('Qdrant connection failures are surfaced without making a successful fallback', function () {
    configureQdrantVectorSearch();
    Http::preventStrayRequests();
    Http::fake([
        'http://localhost:6333/collections/dora_knowledge' => Http::failedConnection(),
    ]);

    expect(fn () => app(QdrantVectorSearch::class)->collectionExists())
        ->toThrow(ChatProviderException::class, 'Qdrant could not be reached or timed out.');
});
