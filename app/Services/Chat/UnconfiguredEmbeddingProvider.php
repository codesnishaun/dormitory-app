<?php

namespace App\Services\Chat;

use App\Contracts\Chat\EmbeddingProviderInterface;
use App\Exceptions\Chat\ProviderNotConfiguredException;

class UnconfiguredEmbeddingProvider implements EmbeddingProviderInterface
{
    public function isConfigured(): bool
    {
        return false;
    }

    public function embed(string $text): array
    {
        throw new ProviderNotConfiguredException('The DORA embedding provider is not configured.');
    }

    public function embedDocument(string $text): array
    {
        throw new ProviderNotConfiguredException('The DORA embedding provider is not configured.');
    }
}
