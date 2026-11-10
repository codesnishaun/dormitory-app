<?php

namespace App\Data\Chat;

class KnowledgeChunk
{
    public function __construct(
        public string $id,
        public string $content,
        public string $source,
        public ?string $updatedAt,
        public string $audience,
        public ?string $effectiveFrom = null,
        public ?string $expiresAt = null,
        public bool $isApproved = false,
        public ?string $sourceDocumentId = null,
        public ?string $documentVersion = null,
    ) {}
}
