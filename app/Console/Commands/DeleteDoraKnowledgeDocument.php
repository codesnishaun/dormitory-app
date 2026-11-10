<?php

namespace App\Console\Commands;

use App\Contracts\Chat\DocumentIngestionInterface;
use App\Exceptions\Chat\ChatProviderException;
use App\Exceptions\Chat\ProviderNotConfiguredException;
use Illuminate\Console\Attributes\Description;
use Illuminate\Console\Attributes\Signature;
use Illuminate\Console\Command;

#[Signature('dora:delete-document {id : Stable source document ID to remove}')]
#[Description('Delete all indexed chunks for a DORA source document')]
class DeleteDoraKnowledgeDocument extends Command
{
    public function handle(DocumentIngestionInterface $ingestion): int
    {
        try {
            $ingestion->deleteSourceDocument((string) $this->argument('id'));
        } catch (ChatProviderException|ProviderNotConfiguredException $exception) {
            $this->error($exception->getMessage());

            return self::FAILURE;
        }

        $this->info('DORA source document chunks deleted successfully.');

        return self::SUCCESS;
    }
}
