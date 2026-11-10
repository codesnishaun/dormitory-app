<?php

use App\Contracts\Chat\DocumentIngestionInterface;
use App\Exceptions\Chat\ChatProviderException;
use App\Services\Chat\QdrantDocumentIngestion;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function configureDocumentIngestion(): void
{
    config([
        'dora.embedding.provider' => 'gemini',
        'dora.embedding.model' => 'gemini-embedding-test-id',
        'dora.embedding.api_key' => 'test-embedding-key',
        'dora.embedding.base_url' => 'https://generativelanguage.googleapis.com/v1beta',
        'dora.embedding.timeout' => 13,
        'dora.embedding.dimensions' => 2,
        'dora.vector.provider' => 'qdrant',
        'dora.vector.endpoint' => 'http://localhost:6333',
        'dora.vector.api_key' => 'test-qdrant-key',
        'dora.vector.index' => 'dora_knowledge',
        'dora.vector.namespace' => 'dormitory',
    ]);
}

test('approved documents are chunked, reindexed without duplicates, and fully deleted by source', function () {
    configureDocumentIngestion();
    $this->travelTo('2026-11-08 12:00:00');
    Http::preventStrayRequests();
    $firstPointIds = [];
    $scrollCount = 0;
    $upserts = [];
    $deletions = [];
    $collectionResponse = [
        'result' => [
            'config' => [
                'params' => [
                    'vectors' => ['size' => 2, 'distance' => 'Cosine'],
                ],
            ],
            'payload_schema' => [
                'effective_from' => ['data_type' => 'datetime'],
                'expires_at' => ['data_type' => 'datetime'],
            ],
        ],
    ];
    Http::fake([
        'https://generativelanguage.googleapis.com/v1beta/models/gemini-embedding-test-id:embedContent' => Http::response([
            'embedding' => ['values' => [0.1, 0.2]],
        ]),
        'http://localhost:6333/collections/dora_knowledge' => Http::response($collectionResponse),
        'http://localhost:6333/collections/dora_knowledge/points/scroll' => function () use (&$firstPointIds, &$scrollCount) {
            $scrollCount++;
            $ids = match ($scrollCount) {
                1 => [],
                2 => $firstPointIds,
                default => [$firstPointIds[0]],
            };

            return Http::response([
                'result' => [
                    'points' => array_map(static fn (string $id): array => ['id' => $id], $ids),
                    'next_page_offset' => null,
                ],
            ]);
        },
        'http://localhost:6333/collections/dora_knowledge/points?wait=true' => function (Request $request) use (&$firstPointIds, &$upserts) {
            $points = $request->data()['points'];
            $upserts[] = $points;

            if (count($upserts) === 1) {
                $firstPointIds = array_column($points, 'id');
            }

            return Http::response(['result' => ['status' => 'completed']]);
        },
        'http://localhost:6333/collections/dora_knowledge/points/delete?wait=true' => function (Request $request) use (&$deletions) {
            $deletions[] = $request->data()['points'];

            return Http::response(['result' => ['status' => 'completed']]);
        },
    ]);
    $ingestion = app(DocumentIngestionInterface::class);
    $metadata = [
        'document_id' => 'dormitory-handbook',
        'source' => 'Dormitory handbook',
        'document_version' => 'v1',
        'audience' => 'guest',
        'approval_status' => 'approved',
    ];

    $ingestion->ingest(str_repeat('Dormitory policy ', 220), $metadata);
    $ingestion->ingest('Updated dormitory policy.', [...$metadata, 'document_version' => 'v2']);
    $ingestion->deleteSourceDocument('dormitory-handbook');

    expect($upserts)->toHaveCount(2)
        ->and($upserts[0])->toHaveCount(2)
        ->and($upserts[1])->toHaveCount(1)
        ->and($upserts[1][0]['id'])->toBe($upserts[0][0]['id'])
        ->and($upserts[0][0]['payload']['source_document_id'])->toBe('dormitory-handbook')
        ->and($upserts[1][0]['payload']['document_version'])->toBe('v2')
        ->and($upserts[1][0]['payload']['approval_status'])->toBe('approved')
        ->and($upserts[0][0]['payload']['namespace'])->toBe('dormitory')
        ->and($deletions)->toBe([[$firstPointIds[1]], [$firstPointIds[0]]]);
    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://generativelanguage.googleapis.com/v1beta/models/gemini-embedding-test-id:embedContent'
        && $request->data()['taskType'] === 'RETRIEVAL_DOCUMENT'
        && $request->data()['outputDimensionality'] === 2);
});

test('unapproved documents and unsupported private metadata are rejected before indexing', function (array $metadata) {
    configureDocumentIngestion();
    Http::preventStrayRequests();

    expect(fn () => app(QdrantDocumentIngestion::class)->ingest('Do not index this draft.', $metadata))
        ->toThrow(ChatProviderException::class);
    Http::assertNothingSent();
})->with([
    'unapproved document' => [[
        'document_id' => 'draft',
        'source' => 'Draft',
        'document_version' => 'v1',
        'audience' => 'guest',
        'approval_status' => 'pending',
    ]],
    'private metadata' => [[
        'document_id' => 'private',
        'source' => 'Tenant account',
        'document_version' => 'v1',
        'audience' => 'tenant',
        'approval_status' => 'approved',
        'tenant_id' => 42,
    ]],
]);

test('document ingestion rejects invalid metadata and invalid content dates', function (array $metadata) {
    configureDocumentIngestion();
    Http::preventStrayRequests();

    expect(fn () => app(QdrantDocumentIngestion::class)->ingest('Approved public policy.', $metadata))
        ->toThrow(ChatProviderException::class);
    Http::assertNothingSent();
})->with([
    'invalid audience' => [[
        'document_id' => 'policy',
        'source' => 'Policy',
        'document_version' => 'v1',
        'audience' => 'tenant_admin',
        'approval_status' => 'approved',
    ]],
    'invalid date range' => [[
        'document_id' => 'policy',
        'source' => 'Policy',
        'document_version' => 'v1',
        'audience' => 'guest',
        'approval_status' => 'approved',
        'effective_from' => '2027-01-01',
        'expires_at' => '2026-01-01',
    ]],
]);

test('document ingestion refuses obvious credential and token assignments', function () {
    configureDocumentIngestion();
    Http::preventStrayRequests();

    expect(fn () => app(QdrantDocumentIngestion::class)->ingest(
        'Temporary password: dormitory-secret-123.',
        [
            'document_id' => 'policy',
            'source' => 'Dormitory policy',
            'document_version' => 'v1',
            'audience' => 'guest',
            'approval_status' => 'approved',
        ],
    ))->toThrow(ChatProviderException::class, 'DORA document content appears to contain a credential or token.');
    Http::assertNothingSent();
});
