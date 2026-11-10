<?php

namespace App\Contracts\Chat;

use App\Data\Chat\KnowledgeChunk;

interface VectorSearchInterface
{
    public function isConfigured(): bool;

    /**
     * @return list<KnowledgeChunk>
     */
    public function search(array $embedding, string $audience, int $limit = 5): array;
}
