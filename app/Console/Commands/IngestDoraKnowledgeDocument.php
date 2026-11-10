<?php

namespace App\Console\Commands;

use App\Contracts\Chat\DocumentIngestionInterface;
use App\Exceptions\Chat\ChatProviderException;
use App\Exceptions\Chat\ProviderNotConfiguredException;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;

#[Signature('dora:ingest-document
    {path : Relative path under the local dora/knowledge storage directory}
    {--id= : Stable source document ID}
    {--source= : Source name or reference}
    {--document-version= : Source document version}
    {--audience= : guest, tenant, or admin}
    {--approved : Confirms the source document has been approved}
    {--effective-from= : Optional effective date in YYYY-MM-DD format}
    {--expires-at= : Optional expiration date in YYYY-MM-DD format}')]
#[Description('Ingest an explicitly approved local DORA knowledge document')]
class IngestDoraKnowledgeDocument extends Command
{
    public function handle(DocumentIngestionInterface $ingestion): int
    {
        if (! $this->option('approved')) {
            $this->error('Indexing requires the explicit --approved option.');

            return self::FAILURE;
        }

        $options = [
            'document_id' => $this->option('id'),
            'source' => $this->option('source'),
            'document_version' => $this->option('document-version'),
            'audience' => $this->option('audience'),
        ];

        foreach ($options as $name => $value) {
            if (! is_string($value) || trim($value) === '') {
                $this->error('The --'.str_replace('_', '-', $name).' option is required.');

                return self::FAILURE;
            }
        }

        $relativePath = $this->argument('path');

        if (! is_string($relativePath)
            || trim($relativePath) === ''
            || preg_match('/^(?:[A-Za-z]:[\\\\\/]|[\\\\\/]{1,2})/', $relativePath) === 1) {
            $this->error('The document path must be relative to the local dora/knowledge storage directory.');

            return self::FAILURE;
        }

        $knowledgeDirectory = realpath(Storage::disk('local')->path('dora/knowledge'));

        if ($knowledgeDirectory === false) {
            $this->error('The local dora/knowledge storage directory does not exist.');

            return self::FAILURE;
        }

        $relativePath = str_replace(['/', '\\'], DIRECTORY_SEPARATOR, $relativePath);
        $documentPath = realpath($knowledgeDirectory.DIRECTORY_SEPARATOR.$relativePath);

        if ($documentPath === false
            || ! str_starts_with($documentPath, $knowledgeDirectory.DIRECTORY_SEPARATOR)
            || ! is_file($documentPath)) {
            $this->error('The document must be a readable file inside the local dora/knowledge directory.');

            return self::FAILURE;
        }

        try {
            $fileSize = filesize($documentPath);

            if ($fileSize === false || $fileSize > 1000000) {
                $this->error('The document is unreadable or exceeds the local ingestion limit.');

                return self::FAILURE;
            }

            $content = file_get_contents($documentPath);

            if (! is_string($content)) {
                $this->error('The document could not be read.');

                return self::FAILURE;
            }

            $ingestion->ingest($content, [
                ...$options,
                'approval_status' => 'approved',
                'updated_at' => today()->toDateString(),
                'effective_from' => $this->option('effective-from') ?: null,
                'expires_at' => $this->option('expires-at') ?: null,
            ]);
        } catch (ChatProviderException|ProviderNotConfiguredException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Approved DORA knowledge document indexed successfully.');

        return self::SUCCESS;
    }
}
