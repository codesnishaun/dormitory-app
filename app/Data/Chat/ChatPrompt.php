<?php

namespace App\Data\Chat;

class ChatPrompt
{
    /**
     * @param  list<array<string, mixed>>  $messages
     * @param  list<KnowledgeChunk>  $knowledge
     * @param  list<array<string, mixed>>  $tools
     */
    public function __construct(
        public string $systemInstructions,
        public array $messages,
        public array $knowledge,
        public array $tools,
        public string $audience,
        public ?string $conversationSummary = null,
    ) {}
}
