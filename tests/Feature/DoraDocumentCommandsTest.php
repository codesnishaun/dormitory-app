<?php

use App\Contracts\Chat\DocumentIngestionInterface;
use Illuminate\Support\Facades\Storage;

test('document ingestion requires an explicit approval confirmation', function () {
    $ingestion = Mockery::mock(DocumentIngestionInterface::class);
    $ingestion->shouldNotReceive('ingest');
    app()->instance(DocumentIngestionInterface::class, $ingestion);

    $this->artisan('dora:ingest-document', [
        'path' => 'policy.md',
        '--id' => 'policy',
        '--source' => 'Dormitory policy',
        '--document-version' => 'v1',
        '--audience' => 'guest',
    ])
        ->expectsOutput('Indexing requires the explicit --approved option.')
        ->assertFailed();
});

test('document ingestion refuses paths outside the local approved knowledge directory', function () {
    Storage::fake('local');
    Storage::disk('local')->put('dora/knowledge/policy.md', 'Approved policy.');
    Storage::disk('local')->put('dora/private.md', 'Private account information.');
    $ingestion = Mockery::mock(DocumentIngestionInterface::class);
    $ingestion->shouldNotReceive('ingest');
    app()->instance(DocumentIngestionInterface::class, $ingestion);

    $this->artisan('dora:ingest-document', [
        'path' => '../private.md',
        '--id' => 'policy',
        '--source' => 'Dormitory policy',
        '--document-version' => 'v1',
        '--audience' => 'guest',
        '--approved' => true,
    ])
        ->expectsOutput('The document must be a readable file inside the local dora/knowledge directory.')
        ->assertFailed();
});

test('approved local documents are passed to ingestion with stable metadata', function () {
    $this->travelTo('2026-11-08 12:00:00');
    Storage::fake('local');
    $content = 'Dormitory quiet hours begin at 10 PM.';
    Storage::disk('local')->put('dora/knowledge/quiet-hours.md', $content);
    $ingestion = Mockery::mock(DocumentIngestionInterface::class);
    $ingestion->shouldReceive('ingest')
        ->once()
        ->with($content, Mockery::on(fn (array $metadata): bool => $metadata['document_id'] === 'quiet-hours'
            && $metadata['source'] === 'Dormitory handbook'
            && $metadata['document_version'] === 'v2'
            && $metadata['audience'] === 'guest'
            && $metadata['approval_status'] === 'approved'
            && $metadata['updated_at'] === '2026-11-08'
            && $metadata['effective_from'] === null
            && $metadata['expires_at'] === null));
    app()->instance(DocumentIngestionInterface::class, $ingestion);

    $this->artisan('dora:ingest-document', [
        'path' => 'quiet-hours.md',
        '--id' => 'quiet-hours',
        '--source' => 'Dormitory handbook',
        '--document-version' => 'v2',
        '--audience' => 'guest',
        '--approved' => true,
    ])->assertSuccessful();
});

test('document deletion removes all chunks for the requested source', function () {
    $ingestion = Mockery::mock(DocumentIngestionInterface::class);
    $ingestion->shouldReceive('deleteSourceDocument')->once()->with('quiet-hours');
    app()->instance(DocumentIngestionInterface::class, $ingestion);

    $this->artisan('dora:delete-document', ['id' => 'quiet-hours'])
        ->assertSuccessful();
});
