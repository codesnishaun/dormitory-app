<?php

namespace App\Services\Chat;

use App\Contracts\Chat\DocumentIngestionInterface;
use App\Contracts\Chat\EmbeddingProviderInterface;
use App\Exceptions\Chat\ChatProviderException;
use App\Exceptions\Chat\ProviderNotConfiguredException;

class QdrantDocumentIngestion implements DocumentIngestionInterface
{
    private const MAX_CONTENT_LENGTH = 100000;

    private const CHUNK_LENGTH = 3000;

    private const CHUNK_OVERLAP = 300;

    private const ALLOWED_METADATA = [
        'document_id',
        'source',
        'document_version',
        'audience',
        'approval_status',
        'updated_at',
        'effective_from',
        'expires_at',
    ];

    public function __construct(
        private EmbeddingProviderInterface $embeddingProvider,
        private QdrantVectorSearch $vectorSearch,
    ) {}

    public function isConfigured(): bool
    {
        return $this->embeddingProvider->isConfigured() && $this->vectorSearch->isConfigured();
    }

    public function ingest(string $content, array $metadata): void
    {
        $metadata = $this->validateMetadata($metadata);

        if (! $this->isConfigured()) {
            throw new ProviderNotConfiguredException('DORA document ingestion is not configured.');
        }

        $chunks = $this->chunk($content);
        $points = [];
        $newPointIds = [];

        foreach ($chunks as $index => $chunk) {
            $pointId = $this->stableChunkId($metadata['document_id'], $index);
            $embedding = $this->embeddingProvider->embedDocument($chunk);
            $newPointIds[] = $pointId;
            $points[] = [
                'id' => $pointId,
                'vector' => $embedding,
                'payload' => [
                    'chunk_id' => $pointId,
                    'source_document_id' => $metadata['document_id'],
                    'source' => $metadata['source'],
                    'document_version' => $metadata['document_version'],
                    'audience' => $metadata['audience'],
                    'approval_status' => 'approved',
                    'updated_at' => $this->dateTimestamp($metadata['updated_at']),
                    'effective_from' => $metadata['effective_from'] === null
                        ? null
                        : $this->dateTimestamp($metadata['effective_from']),
                    'expires_at' => $metadata['expires_at'] === null
                        ? null
                        : $this->dateTimestamp($metadata['expires_at']),
                    'content' => $chunk,
                    'namespace' => config('dora.vector.namespace'),
                ],
            ];
        }

        $this->vectorSearch->ensureCollection();
        $existingPointIds = $this->vectorSearch->pointIdsForDocument($metadata['document_id']);
        $this->vectorSearch->upsertPoints($points);
        $obsoletePointIds = array_values(array_diff($existingPointIds, $newPointIds));
        $this->vectorSearch->deletePoints($obsoletePointIds);
    }

    public function deleteSourceDocument(string $documentId): void
    {
        if (trim($documentId) === '' || mb_strlen($documentId) > 128) {
            throw new ChatProviderException('The DORA source document ID is invalid.');
        }

        if (! $this->isConfigured()) {
            throw new ProviderNotConfiguredException('DORA document ingestion is not configured.');
        }

        $this->vectorSearch->deleteSourceDocument($documentId);
    }

    /**
     * @param  array<string, scalar|null>  $metadata
     * @return array{
     *     document_id: string,
     *     source: string,
     *     document_version: string,
     *     audience: string,
     *     approval_status: string,
     *     updated_at: string,
     *     effective_from: ?string,
     *     expires_at: ?string
     * }
     */
    private function validateMetadata(array $metadata): array
    {
        if (array_diff(array_keys($metadata), self::ALLOWED_METADATA) !== []) {
            throw new ChatProviderException('DORA document metadata contains unsupported fields.');
        }

        foreach (['document_id', 'source', 'document_version', 'audience', 'approval_status'] as $key) {
            if (! is_string($metadata[$key] ?? null) || trim($metadata[$key]) === '') {
                throw new ChatProviderException('DORA document metadata is incomplete.');
            }
        }

        if (mb_strlen($metadata['document_id']) > 128
            || mb_strlen($metadata['source']) > 255
            || mb_strlen($metadata['document_version']) > 100) {
            throw new ChatProviderException('DORA document metadata exceeds its allowed length.');
        }

        if ($metadata['approval_status'] !== 'approved') {
            throw new ChatProviderException('Only approved documents may be indexed.');
        }

        if (! in_array($metadata['audience'], ['guest', 'tenant', 'admin'], true)) {
            throw new ChatProviderException('DORA document audience is invalid.');
        }

        $updatedAt = $this->validatedDate($metadata['updated_at'] ?? today()->toDateString(), 'updated_at');
        $effectiveFrom = $this->validatedDate($metadata['effective_from'] ?? null, 'effective_from');
        $expiresAt = $this->validatedDate($metadata['expires_at'] ?? null, 'expires_at');

        if ($effectiveFrom !== null && $expiresAt !== null && $effectiveFrom > $expiresAt) {
            throw new ChatProviderException('The DORA document effective date is after its expiration date.');
        }

        return [
            'document_id' => $metadata['document_id'],
            'source' => $metadata['source'],
            'document_version' => $metadata['document_version'],
            'audience' => $metadata['audience'],
            'approval_status' => $metadata['approval_status'],
            'updated_at' => $updatedAt,
            'effective_from' => $effectiveFrom,
            'expires_at' => $expiresAt,
        ];
    }

    private function validatedDate(mixed $value, string $field): ?string
    {
        if ($value === null && $field !== 'updated_at') {
            return null;
        }

        if (! is_string($value)
            || preg_match('/^\d{4}-\d{2}-\d{2}$/', $value) !== 1
            || ! checkdate((int) substr($value, 5, 2), (int) substr($value, 8, 2), (int) substr($value, 0, 4))) {
            throw new ChatProviderException('DORA document metadata contains an invalid '.$field.' date.');
        }

        return $value;
    }

    /**
     * @return list<string>
     */
    private function chunk(string $content): array
    {
        $content = trim(str_replace(["\r\n", "\r"], "\n", $content));

        if ($content === ''
            || ! mb_check_encoding($content, 'UTF-8')
            || mb_strlen($content) > self::MAX_CONTENT_LENGTH) {
            throw new ChatProviderException('DORA document content is empty, invalid UTF-8, or exceeds the ingestion limit.');
        }

        if (preg_match('/\b(?:password|invitation[\s_-]*token|access[\s_-]*token|api[\s_-]*key)\b\s*[:=]\s*["\']?([^\s"\'<>]{8,})/i', $content) === 1) {
            throw new ChatProviderException('DORA document content appears to contain a credential or token.');
        }

        $chunks = [];

        while (mb_strlen($content) > self::CHUNK_LENGTH) {
            $candidate = mb_substr($content, 0, self::CHUNK_LENGTH);
            $cutAt = max(
                (int) mb_strrpos($candidate, "\n"),
                (int) mb_strrpos($candidate, ' '),
            );

            if ($cutAt < self::CHUNK_LENGTH / 2) {
                $cutAt = self::CHUNK_LENGTH;
            }

            $chunk = trim(mb_substr($content, 0, $cutAt));

            if ($chunk !== '') {
                $chunks[] = $chunk;
            }

            $content = trim(mb_substr($content, max(1, $cutAt - self::CHUNK_OVERLAP)));
        }

        if ($content !== '') {
            $chunks[] = $content;
        }

        return $chunks;
    }

    private function stableChunkId(string $documentId, int $index): string
    {
        $namespace = hex2bin('6ba7b8109dad11d180b400c04fd430c8');
        $hash = sha1($namespace.'dora:'.$documentId.':'.$index, true);
        $hash[6] = chr((ord($hash[6]) & 0x0F) | 0x50);
        $hash[8] = chr((ord($hash[8]) & 0x3F) | 0x80);
        $hex = bin2hex(substr($hash, 0, 16));

        return substr($hex, 0, 8).'-'
            .substr($hex, 8, 4).'-'
            .substr($hex, 12, 4).'-'
            .substr($hex, 16, 4).'-'
            .substr($hex, 20, 12);
    }

    private function dateTimestamp(string $date): string
    {
        return $date.'T00:00:00Z';
    }
}
