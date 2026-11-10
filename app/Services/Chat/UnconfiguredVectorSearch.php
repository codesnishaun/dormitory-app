<?php

namespace App\Services\Chat;

use App\Contracts\Chat\VectorSearchInterface;
use App\Exceptions\Chat\ProviderNotConfiguredException;

class UnconfiguredVectorSearch implements VectorSearchInterface
{
    public function isConfigured(): bool
    {
        return false;
    }

    public function search(array $embedding, string $audience, int $limit = 5): array
    {
        throw new ProviderNotConfiguredException('The DORA vector search provider is not configured.');
    }
}
