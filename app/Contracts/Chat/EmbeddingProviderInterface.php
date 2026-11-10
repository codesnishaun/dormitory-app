<?php

namespace App\Contracts\Chat;

interface EmbeddingProviderInterface
{
    public function isConfigured(): bool;

    /**
     * @return list<float>
     */
    public function embed(string $text): array;

    /**
     * @return list<float>
     */
    public function embedDocument(string $text): array;
}
