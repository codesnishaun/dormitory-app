<?php

namespace App\Services\Chat;

use App\Contracts\Chat\VectorSearchInterface;
use App\Data\Chat\KnowledgeChunk;
use App\Exceptions\Chat\ChatProviderException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;

class QdrantVectorSearch implements VectorSearchInterface
{
    public function isConfigured(): bool
    {
        $endpoint = config('dora.vector.endpoint');
        $collection = config('dora.vector.index');
        $parts = is_string($endpoint) ? parse_url($endpoint) : false;
        $namespace = config('dora.vector.namespace');

        return config('dora.vector.provider') === 'qdrant'
            && is_string($endpoint)
            && filter_var($endpoint, FILTER_VALIDATE_URL) !== false
            && is_array($parts)
            && in_array($parts['scheme'] ?? null, ['http', 'https'], true)
            && filled($parts['host'] ?? null)
            && ! isset($parts['user'])
            && ! isset($parts['pass'])
            && ! isset($parts['query'])
            && ! isset($parts['fragment'])
            && is_string($collection)
            && preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,127}$/', $collection) === 1
            && ($namespace === null || is_string($namespace))
            && (int) config('dora.embedding.dimensions', 0) > 0;
    }

    public function collectionExists(): bool
    {
        return $this->collectionInfo() !== null;
    }

    public function ensureCollection(): void
    {
        if ($this->collectionExists()) {
            return;
        }

        $this->request('PUT', '/collections/'.$this->collectionName(), [
            'vectors' => [
                'size' => (int) config('dora.embedding.dimensions'),
                'distance' => 'Cosine',
            ],
        ]);
    }

    /**
     * @param  list<float>  $embedding
     * @return list<KnowledgeChunk>
     */
    public function search(array $embedding, string $audience, int $limit = 5): array
    {
        $this->validateEmbedding($embedding);
        $audiences = match ($audience) {
            'guest' => ['guest'],
            'tenant' => ['guest', 'tenant'],
            'admin' => ['guest', 'tenant', 'admin'],
            default => throw new ChatProviderException('The DORA audience is invalid.'),
        };

        if ($limit < 1 || $limit > 100) {
            throw new ChatProviderException('The DORA retrieval limit is invalid.');
        }

        $this->assertCollectionAvailable();
        $today = now()->toDateString().'T00:00:00Z';
        $must = [
            ['key' => 'audience', 'match' => ['any' => $audiences]],
            ['key' => 'approval_status', 'match' => ['value' => 'approved']],
            [
                'should' => [
                    ['key' => 'effective_from', 'range' => ['lte' => $today]],
                    ['is_empty' => ['key' => 'effective_from']],
                ],
            ],
            [
                'should' => [
                    ['key' => 'expires_at', 'range' => ['gte' => $today]],
                    ['is_empty' => ['key' => 'expires_at']],
                ],
            ],
        ];

        $namespace = config('dora.vector.namespace');

        if (is_string($namespace) && $namespace !== '') {
            $must[] = ['key' => 'namespace', 'match' => ['value' => $namespace]];
        }

        $response = $this->request('POST', '/collections/'.$this->collectionName().'/points/search', [
            'vector' => $embedding,
            'filter' => ['must' => $must],
            'limit' => $limit,
            'with_payload' => true,
            'with_vector' => false,
        ]);
        $results = $response['result'] ?? null;

        if (! is_array($results) || ! array_is_list($results)) {
            throw new ChatProviderException('Qdrant returned malformed search results.');
        }

        $chunks = [];

        foreach ($results as $point) {
            if (! is_array($point)) {
                throw new ChatProviderException('Qdrant returned malformed search results.');
            }

            $chunks[] = $this->knowledgeChunk($point);
        }

        return $chunks;
    }

    /**
     * @param  list<array{id: string, vector: list<float>, payload: array<string, mixed>}>  $points
     */
    public function upsertPoints(array $points): void
    {
        if ($points === []) {
            return;
        }

        foreach ($points as $point) {
            if (! is_array($point)
                || ! is_string($point['id'] ?? null)
                || ! is_array($point['vector'] ?? null)) {
                throw new ChatProviderException('DORA attempted to store an invalid Qdrant point.');
            }

            $this->validateEmbedding($point['vector']);
        }

        $this->assertCollectionAvailable();
        $this->request('PUT', '/collections/'.$this->collectionName().'/points?wait=true', [
            'points' => $points,
        ]);
    }

    /**
     * @return list<string>
     */
    public function pointIdsForDocument(string $documentId): array
    {
        $this->assertCollectionAvailable();
        $filter = ['must' => [
            ['key' => 'source_document_id', 'match' => ['value' => $documentId]],
        ]];
        $namespace = config('dora.vector.namespace');

        if (is_string($namespace) && $namespace !== '') {
            $filter['must'][] = ['key' => 'namespace', 'match' => ['value' => $namespace]];
        }

        $pointIds = [];
        $offset = null;

        do {
            $body = [
                'filter' => $filter,
                'limit' => 1000,
                'with_payload' => false,
                'with_vector' => false,
            ];

            if ($offset !== null) {
                $body['offset'] = $offset;
            }

            $response = $this->request('POST', '/collections/'.$this->collectionName().'/points/scroll', $body);
            $result = $response['result'] ?? null;

            if (! is_array($result) || ! is_array($result['points'] ?? null)) {
                throw new ChatProviderException('Qdrant returned malformed document points.');
            }

            foreach ($result['points'] as $point) {
                if (! is_array($point) || (! is_string($point['id'] ?? null) && ! is_int($point['id'] ?? null))) {
                    throw new ChatProviderException('Qdrant returned malformed document points.');
                }

                $pointIds[] = (string) $point['id'];
            }

            $nextOffset = $result['next_page_offset'] ?? null;

            if ($nextOffset !== null && ! is_string($nextOffset) && ! is_int($nextOffset)) {
                throw new ChatProviderException('Qdrant returned malformed document pagination metadata.');
            }

            if ($nextOffset !== null && (string) $nextOffset === (string) $offset) {
                throw new ChatProviderException('Qdrant returned invalid document pagination metadata.');
            }

            $offset = $nextOffset;
        } while ($offset !== null);

        return $pointIds;
    }

    /**
     * @param  list<string>  $pointIds
     */
    public function deletePoints(array $pointIds): void
    {
        if ($pointIds === []) {
            return;
        }

        $this->assertCollectionAvailable();
        $this->request('POST', '/collections/'.$this->collectionName().'/points/delete?wait=true', [
            'points' => array_values($pointIds),
        ]);
    }

    public function deleteSourceDocument(string $documentId): void
    {
        if (! $this->collectionExists()) {
            return;
        }

        $this->deletePoints($this->pointIdsForDocument($documentId));
    }

    /**
     * @param  list<float>  $embedding
     */
    private function validateEmbedding(array $embedding): void
    {
        $expectedDimensions = (int) config('dora.embedding.dimensions', 0);

        if ($expectedDimensions < 1 || count($embedding) !== $expectedDimensions) {
            throw new ChatProviderException('The Qdrant vector dimension does not match the embedding configuration.');
        }

        foreach ($embedding as $value) {
            if ((! is_int($value) && ! is_float($value)) || ! is_finite((float) $value)) {
                throw new ChatProviderException('The Qdrant vector contains an invalid value.');
            }
        }
    }

    /**
     * @param  array<string, mixed>  $point
     */
    private function knowledgeChunk(array $point): KnowledgeChunk
    {
        $payload = $point['payload'] ?? null;

        if (! is_array($payload)) {
            throw new ChatProviderException('Qdrant returned a knowledge point without valid metadata.');
        }

        foreach (['chunk_id', 'source_document_id', 'source', 'document_version', 'audience', 'approval_status', 'content'] as $key) {
            if (! is_string($payload[$key] ?? null) || trim($payload[$key]) === '') {
                throw new ChatProviderException('Qdrant returned incomplete knowledge metadata.');
            }
        }

        if (! in_array($payload['audience'], ['guest', 'tenant', 'admin'], true)
            || ! in_array($payload['approval_status'], ['approved', 'pending', 'rejected'], true)) {
            throw new ChatProviderException('Qdrant returned invalid knowledge authorization metadata.');
        }

        $updatedAt = $this->payloadDate($payload, 'updated_at', required: true);
        $effectiveFrom = $this->payloadDate($payload, 'effective_from');
        $expiresAt = $this->payloadDate($payload, 'expires_at');

        return new KnowledgeChunk(
            $payload['chunk_id'],
            $payload['content'],
            $payload['source'],
            $updatedAt,
            $payload['audience'],
            $effectiveFrom,
            $expiresAt,
            $payload['approval_status'] === 'approved',
            $payload['source_document_id'],
            $payload['document_version'],
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     */
    private function payloadDate(array $payload, string $key, bool $required = false): ?string
    {
        $value = $payload[$key] ?? null;

        if ($value === null && ! $required) {
            return null;
        }

        if (! is_string($value)
            || preg_match('/^\d{4}-\d{2}-\d{2}(?:T00:00:00Z)?$/', $value) !== 1
            || ! checkdate((int) substr($value, 5, 2), (int) substr($value, 8, 2), (int) substr($value, 0, 4))) {
            throw new ChatProviderException('Qdrant returned invalid knowledge date metadata.');
        }

        return substr($value, 0, 10);
    }

    private function assertCollectionAvailable(): void
    {
        $collection = $this->collectionInfo();

        if ($collection === null) {
            throw new ChatProviderException('The configured Qdrant collection does not exist.');
        }

        $this->ensureDatePayloadIndexes($collection);
    }

    /**
     * @return array<string, mixed>|null
     */
    private function collectionInfo(): ?array
    {
        $data = $this->request(
            'GET',
            '/collections/'.$this->collectionName(),
            allowNotFound: true,
        );

        if ($data !== null) {
            $this->assertCollectionConfiguration($data);
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $collection
     */
    private function ensureDatePayloadIndexes(array $collection): void
    {
        $payloadSchema = $collection['result']['payload_schema'] ?? [];

        if (! is_array($payloadSchema)) {
            throw new ChatProviderException('Qdrant returned malformed payload index metadata.');
        }

        foreach (['effective_from', 'expires_at'] as $field) {
            if (array_key_exists($field, $payloadSchema)) {
                $indexType = $payloadSchema[$field]['data_type'] ?? null;

                if ($indexType !== 'datetime') {
                    throw new ChatProviderException('The Qdrant '.$field.' payload index must use the datetime type.');
                }

                continue;
            }

            $this->request('PUT', '/collections/'.$this->collectionName().'/index?wait=true', [
                'field_name' => $field,
                'field_schema' => 'datetime',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function assertCollectionConfiguration(array $data): void
    {
        $vectors = $data['result']['config']['params']['vectors'] ?? null;

        if (! is_array($vectors)
            || (int) ($vectors['size'] ?? 0) !== (int) config('dora.embedding.dimensions')
            || ($vectors['distance'] ?? null) !== 'Cosine') {
            throw new ChatProviderException('The Qdrant collection has incompatible vector dimensions or distance settings.');
        }
    }

    /**
     * @param  array<string, mixed>|null  $payload
     * @return array<string, mixed>|null
     */
    private function request(string $method, string $path, ?array $payload = null, bool $allowNotFound = false): ?array
    {
        if (! $this->isConfigured()) {
            throw new ChatProviderException('The Qdrant provider is not configured.');
        }

        $request = Http::acceptJson()
            ->connectTimeout(3)
            ->timeout(10);
        $apiKey = config('dora.vector.api_key');

        if (is_string($apiKey) && $apiKey !== '') {
            $request = $request->withHeader('api-key', $apiKey);
        }

        $url = rtrim((string) config('dora.vector.endpoint'), '/').$path;

        try {
            $response = match ($method) {
                'GET' => $request->get($url),
                'POST' => $request->post($url, $payload ?? []),
                'PUT' => $request->put($url, $payload ?? []),
                default => throw new ChatProviderException('DORA attempted an unsupported Qdrant operation.'),
            };
        } catch (ConnectionException $exception) {
            throw new ChatProviderException('Qdrant could not be reached or timed out.', previous: $exception);
        }

        if ($allowNotFound && $response->status() === 404) {
            return null;
        }

        if (! $response->successful()) {
            throw new ChatProviderException('Qdrant rejected the request or is unavailable.');
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new ChatProviderException('Qdrant returned a malformed response.');
        }

        return $data;
    }

    private function collectionName(): string
    {
        return rawurlencode((string) config('dora.vector.index'));
    }
}
