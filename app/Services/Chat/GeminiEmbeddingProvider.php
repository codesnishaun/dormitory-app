<?php

namespace App\Services\Chat;

use App\Contracts\Chat\EmbeddingProviderInterface;
use App\Exceptions\Chat\ChatProviderException;
use App\Exceptions\Chat\ProviderNotConfiguredException;

class GeminiEmbeddingProvider implements EmbeddingProviderInterface
{
    public function __construct(private GeminiApiClient $client) {}

    public function isConfigured(): bool
    {
        $apiKey = config('dora.embedding.api_key');

        if (! filled($apiKey)
            && config('dora.embedding.provider') === 'gemini'
            && config('dora.llm.provider') === 'gemini') {
            $apiKey = config('dora.llm.api_key');
        }

        return $this->client->isConfigured(
            config('dora.embedding.provider'),
            config('dora.embedding.model'),
            is_string($apiKey) ? $apiKey : null,
            config('dora.embedding.base_url'),
            (int) config('dora.embedding.timeout', 30),
        ) && (int) config('dora.embedding.dimensions', 0) > 0;
    }

    public function embed(string $text): array
    {
        return $this->embedWithTaskType($text, 'RETRIEVAL_QUERY');
    }

    public function embedDocument(string $text): array
    {
        return $this->embedWithTaskType($text, 'RETRIEVAL_DOCUMENT');
    }

    /**
     * @return list<float>
     */
    private function embedWithTaskType(string $text, string $taskType): array
    {
        if (! $this->isConfigured()) {
            throw new ProviderNotConfiguredException('The Gemini embedding provider is not configured.');
        }

        $model = (string) config('dora.embedding.model');
        $apiKey = config('dora.embedding.api_key');

        if (! filled($apiKey)
            && config('dora.embedding.provider') === 'gemini'
            && config('dora.llm.provider') === 'gemini') {
            $apiKey = config('dora.llm.api_key');
        }

        $dimensions = (int) config('dora.embedding.dimensions');
        $modelId = str_starts_with($model, 'models/') ? substr($model, strlen('models/')) : $model;
        $response = $this->client->post(
            $model,
            'embedContent',
            [
                'model' => 'models/'.$modelId,
                'content' => [
                    'parts' => [
                        ['text' => $text],
                    ],
                ],
                'taskType' => $taskType,
                'outputDimensionality' => $dimensions,
            ],
            is_string($apiKey) ? $apiKey : null,
            config('dora.embedding.base_url'),
            (int) config('dora.embedding.timeout', 30),
        );

        $values = $response['embedding']['values'] ?? null;

        if (! is_array($values) || ! array_is_list($values) || $values === []) {
            throw new ChatProviderException('Gemini returned an invalid embedding vector.');
        }

        foreach ($values as $value) {
            if ((! is_int($value) && ! is_float($value)) || ! is_finite((float) $value)) {
                throw new ChatProviderException('Gemini returned an invalid embedding vector.');
            }
        }

        if (count($values) !== $dimensions) {
            throw new ChatProviderException('Gemini returned an unexpected embedding dimension.');
        }

        return array_map(static fn (int|float $value): float => (float) $value, $values);
    }
}
