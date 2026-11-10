<?php

namespace App\Data\Chat;

class ChatCompletion
{
    /**
     * @param  list<array{id: string, name: string, arguments: array<string, mixed>, thoughtSignature?: string}>  $toolCalls
     * @param  list<string>  $sourceIds  IDs of retrieved knowledge chunks cited in the response.
     */
    public function __construct(
        public string $content,
        public array $toolCalls = [],
        public array $sourceIds = [],
    ) {}
}
