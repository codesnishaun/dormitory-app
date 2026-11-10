<?php

use App\Contracts\Chat\EmbeddingProviderInterface;
use App\Contracts\Chat\VectorSearchInterface;
use App\Data\Chat\KnowledgeChunk;
use App\Exceptions\Chat\ChatProviderException;
use App\Services\Chat\KnowledgeRetrievalService;

function retrievalTestChunk(
    string $id,
    string $audience,
    bool $approved = true,
    ?string $effectiveFrom = null,
    ?string $expiresAt = null,
    ?string $updatedAt = '2026-11-01',
): KnowledgeChunk {
    return new KnowledgeChunk(
        $id,
        'Approved dormitory guidance.',
        'Dormitory handbook',
        $updatedAt,
        $audience,
        $effectiveFrom,
        $expiresAt,
        $approved,
    );
}

test('retrieval enforces the guest tenant and admin audience matrix', function (string $audience, array $expectedIds) {
    config(['dora.embedding.dimensions' => 2, 'dora.retrieval_limit' => 5]);
    $embeddingProvider = Mockery::mock(EmbeddingProviderInterface::class);
    $embeddingProvider->shouldReceive('isConfigured')->once()->andReturnTrue();
    $embeddingProvider->shouldReceive('embed')->once()->with('What is the policy?')->andReturn([0.1, 0.2]);
    $vectorSearch = Mockery::mock(VectorSearchInterface::class);
    $vectorSearch->shouldReceive('isConfigured')->once()->andReturnTrue();
    $vectorSearch->shouldReceive('search')->once()->with([0.1, 0.2], $audience, 5)->andReturn([
        retrievalTestChunk('guest', 'guest'),
        retrievalTestChunk('tenant', 'tenant'),
        retrievalTestChunk('admin', 'admin'),
    ]);
    app()->instance(EmbeddingProviderInterface::class, $embeddingProvider);
    app()->instance(VectorSearchInterface::class, $vectorSearch);

    $chunks = app(KnowledgeRetrievalService::class)->retrieve('What is the policy?', $audience);

    expect(array_column($chunks, 'id'))->toBe($expectedIds);
})->with([
    'guest sees guest documents only' => ['guest', ['guest']],
    'tenant sees guest and tenant documents' => ['tenant', ['guest', 'tenant']],
    'admin sees all approved audiences' => ['admin', ['guest', 'tenant', 'admin']],
]);

test('retrieval excludes unapproved expired and future-effective knowledge', function () {
    $this->travelTo('2026-11-08 12:00:00');
    config(['dora.embedding.dimensions' => 2, 'dora.retrieval_limit' => 5]);
    $embeddingProvider = Mockery::mock(EmbeddingProviderInterface::class);
    $embeddingProvider->shouldReceive('isConfigured')->once()->andReturnTrue();
    $embeddingProvider->shouldReceive('embed')->once()->andReturn([0.1, 0.2]);
    $vectorSearch = Mockery::mock(VectorSearchInterface::class);
    $vectorSearch->shouldReceive('isConfigured')->once()->andReturnTrue();
    $vectorSearch->shouldReceive('search')->once()->andReturn([
        retrievalTestChunk('valid', 'guest'),
        retrievalTestChunk('unapproved', 'guest', approved: false),
        retrievalTestChunk('expired', 'guest', expiresAt: '2026-11-07'),
        retrievalTestChunk('future', 'guest', effectiveFrom: '2026-11-09'),
    ]);
    app()->instance(EmbeddingProviderInterface::class, $embeddingProvider);
    app()->instance(VectorSearchInterface::class, $vectorSearch);

    $chunks = app(KnowledgeRetrievalService::class)->retrieve('What is the policy?', 'guest');

    expect(array_column($chunks, 'id'))->toBe(['valid']);
});

test('retrieval rejects duplicate IDs invalid audience and malformed date metadata', function (KnowledgeChunk $chunk) {
    config(['dora.embedding.dimensions' => 2, 'dora.retrieval_limit' => 5]);
    $embeddingProvider = Mockery::mock(EmbeddingProviderInterface::class);
    $embeddingProvider->shouldReceive('isConfigured')->once()->andReturnTrue();
    $embeddingProvider->shouldReceive('embed')->once()->andReturn([0.1, 0.2]);
    $vectorSearch = Mockery::mock(VectorSearchInterface::class);
    $vectorSearch->shouldReceive('isConfigured')->once()->andReturnTrue();
    $vectorSearch->shouldReceive('search')->once()->andReturn([$chunk, $chunk]);
    app()->instance(EmbeddingProviderInterface::class, $embeddingProvider);
    app()->instance(VectorSearchInterface::class, $vectorSearch);

    expect(fn () => app(KnowledgeRetrievalService::class)->retrieve('What is the policy?', 'guest'))
        ->toThrow(ChatProviderException::class);
})->with([
    'duplicate chunk id' => [retrievalTestChunk('duplicate', 'guest')],
    'invalid audience' => [retrievalTestChunk('invalid', 'tenant_admin')],
    'invalid updated date' => [retrievalTestChunk('invalid-date', 'guest', updatedAt: '2026-99-99')],
]);

test('retrieval rejects a dimension mismatch and unknown request audience', function (string $audience, array $embedding) {
    config(['dora.embedding.dimensions' => 2, 'dora.retrieval_limit' => 5]);
    $embeddingProvider = Mockery::mock(EmbeddingProviderInterface::class);
    $embeddingProvider->shouldReceive('isConfigured')->once()->andReturnTrue();
    $vectorSearch = Mockery::mock(VectorSearchInterface::class);
    $vectorSearch->shouldReceive('isConfigured')->once()->andReturnTrue();

    if ($audience === 'tenant_admin') {
        $embeddingProvider->shouldNotReceive('embed');
    } else {
        $embeddingProvider->shouldReceive('embed')->once()->andReturn($embedding);
        $vectorSearch->shouldNotReceive('search');
    }

    app()->instance(EmbeddingProviderInterface::class, $embeddingProvider);
    app()->instance(VectorSearchInterface::class, $vectorSearch);

    expect(fn () => app(KnowledgeRetrievalService::class)->retrieve('What is the policy?', $audience))
        ->toThrow(ChatProviderException::class);
})->with([
    'mismatched vector' => ['guest', [0.1]],
    'unknown audience' => ['tenant_admin', [0.1, 0.2]],
]);
