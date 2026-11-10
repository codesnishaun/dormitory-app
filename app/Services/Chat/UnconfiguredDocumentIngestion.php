<?php

namespace App\Services\Chat;

use App\Contracts\Chat\DocumentIngestionInterface;
use App\Exceptions\Chat\ProviderNotConfiguredException;

class UnconfiguredDocumentIngestion implements DocumentIngestionInterface
{
    public function isConfigured(): bool
    {
        return false;
    }

    public function ingest(string $content, array $metadata): void
    {
        throw new ProviderNotConfiguredException('The DORA document ingestion provider is not configured.');
    }

    public function deleteSourceDocument(string $documentId): void
    {
        throw new ProviderNotConfiguredException('The DORA document ingestion provider is not configured.');
    }
}
