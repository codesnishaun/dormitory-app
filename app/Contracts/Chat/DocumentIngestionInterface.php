<?php

namespace App\Contracts\Chat;

interface DocumentIngestionInterface
{
    public function isConfigured(): bool;

    /**
     * @param  array<string, scalar|null>  $metadata
     */
    public function ingest(string $content, array $metadata): void;

    public function deleteSourceDocument(string $documentId): void;
}
