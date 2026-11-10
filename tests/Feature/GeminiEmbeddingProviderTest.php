<?php

use App\Exceptions\Chat\ChatProviderException;
use App\Exceptions\Chat\ProviderNotConfiguredException;
use App\Services\Chat\GeminiEmbeddingProvider;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\Request;
use Illuminate\Support\Facades\Http;

function configureGeminiEmbeddingProvider(): void
{
    config([
        'dora.embedding.provider' => 'gemini',
        'dora.embedding.model' => 'gemini-embedding-test-id',
        'dora.embedding.api_key' => 'test-embedding-key',
        'dora.embedding.base_url' => 'https://generativelanguage.googleapis.com/v1beta',
        'dora.embedding.timeout' => 13,
        'dora.embedding.dimensions' => 3,
    ]);
}

test('Gemini returns an embedding with the configured model and dimensions', function () {
    configureGeminiEmbeddingProvider();
    Http::preventStrayRequests();
    Http::fake([
        'https://generativelanguage.googleapis.com/v1beta/models/gemini-embedding-test-id:embedContent' => Http::response([
            'embedding' => ['values' => [0.1, -0.25, 0.75]],
        ]),
    ]);

    $embedding = app(GeminiEmbeddingProvider::class)->embed('Dormitory policies');

    expect($embedding)->toBe([0.1, -0.25, 0.75]);
    Http::assertSent(fn (Request $request): bool => $request->url() === 'https://generativelanguage.googleapis.com/v1beta/models/gemini-embedding-test-id:embedContent'
        && $request->hasHeader('x-goog-api-key', 'test-embedding-key')
        && $request->data()['model'] === 'models/gemini-embedding-test-id'
        && $request->data()['content']['parts'][0]['text'] === 'Dormitory policies'
        && $request->data()['taskType'] === 'RETRIEVAL_QUERY'
        && $request->data()['outputDimensionality'] === 3);
});

test('Gemini requests document embeddings with the retrieval document task type', function () {
    configureGeminiEmbeddingProvider();
    Http::preventStrayRequests();
    Http::fake([
        'https://generativelanguage.googleapis.com/v1beta/models/gemini-embedding-test-id:embedContent' => Http::response([
            'embedding' => ['values' => [0.1, 0.2, 0.3]],
        ]),
    ]);

    $embedding = app(GeminiEmbeddingProvider::class)->embedDocument('Approved dormitory handbook.');

    expect($embedding)->toHaveCount(3);
    Http::assertSent(fn (Request $request): bool => $request->data()['taskType'] === 'RETRIEVAL_DOCUMENT');
});

test('Gemini embedding uses the LLM key only when both configured providers are Gemini', function () {
    configureGeminiEmbeddingProvider();
    config([
        'dora.embedding.api_key' => null,
        'dora.llm.provider' => 'gemini',
        'dora.llm.api_key' => 'shared-gemini-key',
    ]);
    Http::preventStrayRequests();
    Http::fake([
        'https://generativelanguage.googleapis.com/v1beta/models/gemini-embedding-test-id:embedContent' => Http::response([
            'embedding' => ['values' => [0.1, 0.2, 0.3]],
        ]),
    ]);

    $embedding = app(GeminiEmbeddingProvider::class)->embed('Dormitory policies');

    expect($embedding)->toHaveCount(3);
    Http::assertSent(fn (Request $request): bool => $request->hasHeader('x-goog-api-key', 'shared-gemini-key'));
});

test('Gemini embedding does not fall back to credentials for another LLM provider', function () {
    configureGeminiEmbeddingProvider();
    config([
        'dora.embedding.api_key' => null,
        'dora.llm.provider' => 'another-provider',
        'dora.llm.api_key' => 'unrelated-key',
    ]);

    expect(app(GeminiEmbeddingProvider::class)->isConfigured())->toBeFalse();
    expect(fn () => app(GeminiEmbeddingProvider::class)->embed('Dormitory policies'))
        ->toThrow(ProviderNotConfiguredException::class);
});

test('Gemini rejects malformed, non-finite, and incorrectly sized embedding vectors', function (array $body) {
    configureGeminiEmbeddingProvider();
    Http::preventStrayRequests();
    Http::fake([
        'https://generativelanguage.googleapis.com/v1beta/models/gemini-embedding-test-id:embedContent' => Http::response($body),
    ]);

    expect(fn () => app(GeminiEmbeddingProvider::class)->embed('Dormitory policies'))
        ->toThrow(ChatProviderException::class);
})->with([
    'missing embedding' => [['']],
    'non-numeric value' => [['embedding' => ['values' => [0.1, 'invalid', 0.3]]]],
    'wrong dimensions' => [['embedding' => ['values' => [0.1, 0.2]]]],
]);

test('Gemini reports authentication rate-limit and outage errors without exposing credentials', function (int $status, string $message) {
    configureGeminiEmbeddingProvider();
    Http::preventStrayRequests();
    Http::fake([
        'https://generativelanguage.googleapis.com/v1beta/models/gemini-embedding-test-id:embedContent' => Http::response(['error' => 'private provider detail'], $status),
    ]);

    try {
        app(GeminiEmbeddingProvider::class)->embed('Dormitory policies');
        test()->fail('The provider error was not raised.');
    } catch (ChatProviderException $exception) {
        expect($exception->getMessage())->toBe($message)
            ->not->toContain('test-embedding-key')
            ->not->toContain('private provider detail');
    }
})->with([
    'authentication failure' => [401, 'Gemini authentication failed.'],
    'rate limit' => [429, 'Gemini rate limit was exceeded.'],
    'provider outage' => [503, 'Gemini is temporarily unavailable (HTTP 503).'],
]);

test('Gemini translates request timeouts into a safe provider error', function () {
    configureGeminiEmbeddingProvider();
    Http::preventStrayRequests();
    Http::fake([
        'https://generativelanguage.googleapis.com/v1beta/models/gemini-embedding-test-id:embedContent' => fn () => throw new ConnectionException('timeout'),
    ]);

    expect(fn () => app(GeminiEmbeddingProvider::class)->embed('Dormitory policies'))
        ->toThrow(ChatProviderException::class, 'The Gemini request timed out or the service could not be reached.');
});

test('Gemini is not configured without a model, key, or positive embedding dimensions', function () {
    configureGeminiEmbeddingProvider();
    config([
        'dora.embedding.model' => null,
        'dora.embedding.api_key' => null,
        'dora.embedding.dimensions' => 0,
    ]);
    config(['dora.llm.provider' => 'gemini', 'dora.llm.api_key' => null]);

    expect(app(GeminiEmbeddingProvider::class)->isConfigured())->toBeFalse();
    expect(fn () => app(GeminiEmbeddingProvider::class)->embed('text'))
        ->toThrow(ProviderNotConfiguredException::class);
});
