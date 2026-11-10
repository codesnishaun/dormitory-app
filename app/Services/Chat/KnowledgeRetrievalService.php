<?php

namespace App\Services\Chat;

use App\Contracts\Chat\EmbeddingProviderInterface;
use App\Contracts\Chat\VectorSearchInterface;
use App\Data\Chat\KnowledgeChunk;
use App\Exceptions\Chat\ChatProviderException;
use App\Exceptions\Chat\ProviderNotConfiguredException;

class KnowledgeRetrievalService
{
    public function __construct(
        private EmbeddingProviderInterface $embeddingProvider,
        private VectorSearchInterface $vectorSearch,
    ) {}

    public function isConfigured(): bool
    {
        return $this->embeddingProvider->isConfigured() && $this->vectorSearch->isConfigured();
    }

    /**
     * @return list<KnowledgeChunk>
     */
    public function retrieve(string $query, string $audience): array
    {
        if (! $this->isConfigured()) {
            throw new ProviderNotConfiguredException('DORA knowledge retrieval is not configured.');
        }

        if (! in_array($audience, ['guest', 'tenant', 'admin'], true)) {
            throw new ChatProviderException('The DORA audience is invalid.');
        }

        $embedding = $this->embeddingProvider->embed($query);

        if ($embedding === [] || collect($embedding)->contains(fn ($value) => ! is_numeric($value) || ! is_finite((float) $value))) {
            throw new ChatProviderException('The embedding provider returned an invalid vector.');
        }

        $dimensions = (int) config('dora.embedding.dimensions', 0);

        if ($dimensions > 0 && count($embedding) !== $dimensions) {
            throw new ChatProviderException('The embedding provider returned an unexpected vector dimension.');
        }

        $limit = (int) config('dora.retrieval_limit', 5);

        if ($limit < 1 || $limit > 100) {
            throw new ChatProviderException('The DORA retrieval limit is invalid.');
        }

        $chunks = $this->vectorSearch->search($embedding, $audience, $limit);
        $today = today()->toDateString();
        $chunkIds = [];

        foreach ($chunks as $chunk) {
            if (! $chunk instanceof KnowledgeChunk || ! in_array($chunk->audience, ['guest', 'tenant', 'admin'], true)) {
                throw new ChatProviderException('The vector search provider returned invalid knowledge metadata.');
            }

            if ($chunk->id === '' || $chunk->content === '' || $chunk->source === '' || in_array($chunk->id, $chunkIds, true)) {
                throw new ChatProviderException('The vector search provider returned incomplete knowledge metadata.');
            }

            $chunkIds[] = $chunk->id;
            $this->validateDate($chunk->updatedAt);
            $this->validateDate($chunk->effectiveFrom);
            $this->validateDate($chunk->expiresAt);

            if (($chunk->sourceDocumentId !== null && $chunk->sourceDocumentId === '')
                || ($chunk->documentVersion !== null && $chunk->documentVersion === '')) {
                throw new ChatProviderException('The vector search provider returned incomplete document provenance.');
            }
        }

        return array_values(array_filter(
            $chunks,
            fn (KnowledgeChunk $chunk): bool => $chunk->isApproved
                && $this->canAccess($chunk->audience, $audience)
                && ($chunk->effectiveFrom === null || $chunk->effectiveFrom <= $today)
                && ($chunk->expiresAt === null || $chunk->expiresAt >= $today),
        ));
    }

    private function canAccess(string $documentAudience, string $userAudience): bool
    {
        return match ($userAudience) {
            'guest' => $documentAudience === 'guest',
            'tenant' => in_array($documentAudience, ['guest', 'tenant'], true),
            'admin' => in_array($documentAudience, ['guest', 'tenant', 'admin'], true),
            default => false,
        };
    }

    private function validateDate(?string $date): void
    {
        if ($date === null) {
            return;
        }

        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)
            || ! checkdate((int) substr($date, 5, 2), (int) substr($date, 8, 2), (int) substr($date, 0, 4))) {
            throw new ChatProviderException('The vector search provider returned invalid document date metadata.');
        }
    }
}
