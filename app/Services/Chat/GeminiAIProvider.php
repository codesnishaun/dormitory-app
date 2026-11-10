<?php

namespace App\Services\Chat;

use App\Contracts\Chat\AIProviderInterface;
use App\Contracts\Chat\AIStreamingProviderInterface;
use App\Data\Chat\ChatCompletion;
use App\Data\Chat\ChatPrompt;
use App\Exceptions\Chat\ChatProviderException;
use App\Exceptions\Chat\ProviderNotConfiguredException;
use Illuminate\Support\Str;
use JsonException;

class GeminiAIProvider implements AIProviderInterface, AIStreamingProviderInterface
{
    public function __construct(private GeminiApiClient $client) {}

    public function isConfigured(): bool
    {
        return $this->client->isConfigured(
            config('dora.llm.provider'),
            config('dora.llm.model'),
            config('dora.llm.api_key'),
            config('dora.llm.base_url'),
            (int) config('dora.llm.timeout', 30),
        );
    }

    public function generate(ChatPrompt $prompt): ChatCompletion
    {
        if (! $this->isConfigured()) {
            throw new ProviderNotConfiguredException('The Gemini language model provider is not configured.');
        }

        if (trim($prompt->systemInstructions) === '') {
            throw new ChatProviderException('The DORA system instructions are missing.');
        }

        $response = $this->client->post(
            (string) config('dora.llm.model'),
            'generateContent',
            $this->payload($prompt),
            config('dora.llm.api_key'),
            config('dora.llm.base_url'),
            (int) config('dora.llm.timeout', 30),
        );

        $candidate = $response['candidates'][0] ?? null;
        $parts = is_array($candidate) ? ($candidate['content']['parts'] ?? null) : null;

        if (! is_array($parts) || ! array_is_list($parts)) {
            throw new ChatProviderException('Gemini returned a malformed chat response.');
        }

        return $this->completionFromParts($parts, $prompt);
    }

    public function generateStream(ChatPrompt $prompt, callable $onText): ChatCompletion
    {
        if (! $this->isConfigured()) {
            throw new ProviderNotConfiguredException('The Gemini language model provider is not configured.');
        }

        if (trim($prompt->systemInstructions) === '') {
            throw new ChatProviderException('The DORA system instructions are missing.');
        }

        $text = '';
        $functionCalls = [];

        $this->client->postStream(
            (string) config('dora.llm.model'),
            $this->payload($prompt),
            config('dora.llm.api_key'),
            config('dora.llm.base_url'),
            (int) config('dora.llm.timeout', 30),
            function (array $chunk) use (&$text, &$functionCalls, $onText): void {
                $candidate = $chunk['candidates'][0] ?? null;
                $parts = is_array($candidate) ? ($candidate['content']['parts'] ?? null) : null;

                if (! is_array($parts) || ! array_is_list($parts)) {
                    if (($candidate['finishReason'] ?? null) === 'STOP') {
                        return;
                    }

                    throw new ChatProviderException('Gemini returned a malformed streaming response.');
                }

                foreach ($parts as $partIndex => $part) {
                    if (! is_array($part)) {
                        throw new ChatProviderException('Gemini returned a malformed streaming response.');
                    }

                    if (isset($part['text']) && ($part['thought'] ?? false) !== true) {
                        if (! is_string($part['text'])) {
                            throw new ChatProviderException('Gemini returned a malformed streaming response.');
                        }

                        $text .= $part['text'];
                        $onText($part['text']);
                    }

                    if (isset($part['functionCall'])) {
                        if (! is_array($part['functionCall'])) {
                            throw new ChatProviderException('Gemini returned an invalid tool call.');
                        }

                        $functionCalls[$partIndex]['functionCall'] = $part['functionCall'];

                        if (isset($part['thoughtSignature'])) {
                            if (! is_string($part['thoughtSignature'])) {
                                throw new ChatProviderException('Gemini returned an invalid tool call signature.');
                            }

                            $functionCalls[$partIndex]['thoughtSignature'] = $part['thoughtSignature'];
                        }
                    }
                }
            },
        );

        $parts = [];

        if ($text !== '') {
            $parts[] = ['text' => $text];
        }

        array_push($parts, ...array_values($functionCalls));

        return $this->completionFromParts($parts, $prompt);
    }

    /**
     * @return array<string, mixed>
     */
    private function payload(ChatPrompt $prompt): array
    {
        $payload = [
            'systemInstruction' => [
                'parts' => [
                    ['text' => $prompt->systemInstructions],
                ],
            ],
            'contents' => $this->contents($prompt),
        ];

        if ($prompt->tools !== []) {
            $payload['tools'] = [
                ['functionDeclarations' => $this->functionDeclarations($prompt->tools)],
            ];
        }

        return $payload;
    }

    /**
     * @param  list<array<string, mixed>>  $parts
     */
    private function completionFromParts(array $parts, ChatPrompt $prompt): ChatCompletion
    {
        $toolNames = array_column($prompt->tools, 'name');
        $text = [];
        $toolCalls = [];

        foreach ($parts as $part) {
            if (! is_array($part)) {
                throw new ChatProviderException('Gemini returned a malformed chat response.');
            }

            if (isset($part['text']) && ($part['thought'] ?? false) !== true) {
                if (! is_string($part['text'])) {
                    throw new ChatProviderException('Gemini returned a malformed chat response.');
                }

                $text[] = $part['text'];
            }

            if (! isset($part['functionCall'])) {
                continue;
            }

            $functionCall = $part['functionCall'];

            if (! is_array($functionCall)) {
                throw new ChatProviderException('Gemini returned an invalid tool call.');
            }

            $name = $functionCall['name'] ?? null;
            $arguments = $functionCall['args'] ?? [];

            if (! is_string($name)
                || ! in_array($name, $toolNames, true)
                || ! is_array($arguments)) {
                throw new ChatProviderException('Gemini returned an invalid tool call.');
            }

            $toolCalls[] = [
                'id' => (string) Str::uuid(),
                'name' => $name,
                'arguments' => $arguments,
            ];

            if (isset($part['thoughtSignature'])) {
                if (! is_string($part['thoughtSignature'])) {
                    throw new ChatProviderException('Gemini returned an invalid tool call signature.');
                }

                $toolCalls[array_key_last($toolCalls)]['thoughtSignature'] = $part['thoughtSignature'];
            }
        }

        $content = trim(implode("\n", $text));
        $sourceIds = [];

        if ($content === '' && $toolCalls === []) {
            throw new ChatProviderException('Gemini returned an empty chat response.');
        }

        if ($toolCalls === []) {
            preg_match_all('/\[\[source:([A-Za-z0-9._:-]+)\]\]/i', $content, $matches);
            $sourceIds = array_values(array_unique($matches[1] ?? []));
            $availableSourceIds = array_map(
                static fn ($chunk): string => $chunk->id,
                $prompt->knowledge,
            );

            if (array_diff($sourceIds, $availableSourceIds) !== []) {
                throw new ChatProviderException('Gemini returned an unknown knowledge source reference.');
            }

            $content = trim((string) preg_replace('/\[\[source:[A-Za-z0-9._:-]+\]\]/i', '', $content));

            if ($content === '') {
                throw new ChatProviderException('Gemini returned an empty chat response.');
            }
        }

        return new ChatCompletion($content, $toolCalls, $sourceIds);
    }

    /**
     * @return list<array{role: string, parts: list<array<string, mixed>>}>
     */
    private function contents(ChatPrompt $prompt): array
    {
        $contents = [];
        $supplementalParts = [];
        $pendingToolResponseParts = [];

        if ($prompt->conversationSummary !== null && trim($prompt->conversationSummary) !== '') {
            $supplementalParts[] = [
                'text' => $this->encodeUntrustedData([
                    'type' => 'conversation_summary',
                    'content' => $prompt->conversationSummary,
                ]),
            ];
        }

        $flushToolResponses = function () use (&$contents, &$pendingToolResponseParts): void {
            if ($pendingToolResponseParts === []) {
                return;
            }

            $contents[] = [
                'role' => 'user',
                'parts' => $pendingToolResponseParts,
            ];
            $pendingToolResponseParts = [];
        };

        foreach ($prompt->messages as $message) {
            if (! is_array($message) || ! is_string($message['role'] ?? null)) {
                throw new ChatProviderException('The DORA conversation history is malformed.');
            }

            if ($message['role'] === 'tool') {
                $name = $message['name'] ?? null;
                $content = $message['content'] ?? null;

                if (! is_string($name) || ! is_string($content)) {
                    throw new ChatProviderException('The DORA tool history is malformed.');
                }

                try {
                    $result = json_decode($content, true, flags: JSON_THROW_ON_ERROR);
                } catch (JsonException $exception) {
                    throw new ChatProviderException('The DORA tool history is malformed.', previous: $exception);
                }

                $pendingToolResponseParts[] = [
                    'functionResponse' => [
                        'name' => $name,
                        'response' => $this->structPayload($result),
                    ],
                ];

                continue;
            }

            $flushToolResponses();

            $role = match ($message['role']) {
                'assistant', 'model' => 'model',
                'user' => 'user',
                default => throw new ChatProviderException('The DORA conversation history is malformed.'),
            };

            if (isset($message['tool_calls'])) {
                if (! is_array($message['tool_calls'])) {
                    throw new ChatProviderException('The DORA tool history is malformed.');
                }

                $parts = [];

                foreach ($message['tool_calls'] as $toolCall) {
                    if (! is_array($toolCall)
                        || ! is_string($toolCall['name'] ?? null)
                        || ! is_array($toolCall['arguments'] ?? null)) {
                        throw new ChatProviderException('The DORA tool history is malformed.');
                    }

                    $parts[] = [
                        'functionCall' => [
                            'name' => $toolCall['name'],
                            'args' => $this->structPayload($toolCall['arguments']),
                        ],
                    ];

                    if (isset($toolCall['thoughtSignature'])) {
                        if (! is_string($toolCall['thoughtSignature'])) {
                            throw new ChatProviderException('The DORA tool history is malformed.');
                        }

                        $parts[array_key_last($parts)]['thoughtSignature'] = $toolCall['thoughtSignature'];
                    }
                }

                $contents[] = ['role' => $role, 'parts' => $parts];

                continue;
            }

            if (! is_string($message['content'] ?? null)) {
                throw new ChatProviderException('The DORA conversation history is malformed.');
            }

            $contents[] = [
                'role' => $role,
                'parts' => [['text' => $message['content']]],
            ];
        }

        $flushToolResponses();

        if ($prompt->knowledge !== []) {
            $supplementalParts[] = [
                'text' => $this->encodeUntrustedData([
                    'type' => 'retrieved_knowledge',
                    'items' => array_map(static fn ($chunk): array => [
                        'id' => $chunk->id,
                        'source' => $chunk->source,
                        'content' => $chunk->content,
                    ], $prompt->knowledge),
                ]),
            ];
        }

        if ($supplementalParts !== []) {
            $lastIndex = array_key_last($contents);

            if ($lastIndex !== null && $contents[$lastIndex]['role'] === 'user') {
                array_push($contents[$lastIndex]['parts'], ...$supplementalParts);
            } else {
                $contents[] = ['role' => 'user', 'parts' => $supplementalParts];
            }
        }

        if ($contents === []) {
            throw new ChatProviderException('The DORA conversation has no messages.');
        }

        return $contents;
    }

    /**
     * Gemini functionCall.args and functionResponse.response are protobuf Struct fields.
     * JSON lists (including empty PHP arrays) are rejected; encode objects instead.
     *
     * @return array<string, mixed>|object
     */
    private function structPayload(mixed $value): array|object
    {
        if (! is_array($value)) {
            return ['result' => $value];
        }

        if ($value === []) {
            return (object) [];
        }

        if (array_is_list($value)) {
            return ['items' => $value];
        }

        return $value;
    }

    /**
     * @param  list<array<string, mixed>>  $tools
     * @return list<array<string, mixed>>
     */
    private function functionDeclarations(array $tools): array
    {
        $declarations = [];

        foreach ($tools as $tool) {
            if (! is_string($tool['name'] ?? null)
                || ! preg_match('/^[A-Za-z_][A-Za-z0-9_]{0,63}$/', $tool['name'])
                || ! is_string($tool['description'] ?? null)
                || ! is_array($tool['parameters'] ?? null)) {
                throw new ChatProviderException('The DORA tool registry returned invalid tool definitions.');
            }

            $properties = [];
            $required = [];

            foreach ($tool['parameters'] as $name => $parameter) {
                if (! is_string($name)
                    || ! preg_match('/^[A-Za-z_][A-Za-z0-9_]{0,63}$/', $name)
                    || ! is_array($parameter)
                    || ! in_array($parameter['type'] ?? null, ['string', 'number', 'integer', 'boolean'], true)) {
                    throw new ChatProviderException('The DORA tool registry returned invalid tool definitions.');
                }

                $properties[$name] = ['type' => strtoupper($parameter['type'])];

                if (($parameter['required'] ?? false) === true) {
                    $required[] = $name;
                }
            }

            $declaration = [
                'name' => $tool['name'],
                'description' => $tool['description'],
            ];

            if ($properties !== []) {
                $schema = [
                    'type' => 'OBJECT',
                    'properties' => (object) $properties,
                ];

                if ($required !== []) {
                    $schema['required'] = $required;
                }

                $declaration['parameters'] = $schema;
            }

            $declarations[] = $declaration;
        }

        return $declarations;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function encodeUntrustedData(array $data): string
    {
        try {
            return json_encode($data, JSON_THROW_ON_ERROR | JSON_UNESCAPED_UNICODE);
        } catch (JsonException $exception) {
            throw new ChatProviderException('DORA could not encode its untrusted context.', previous: $exception);
        }
    }
}
