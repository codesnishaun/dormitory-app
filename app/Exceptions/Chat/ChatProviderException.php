<?php

namespace App\Exceptions\Chat;

use Illuminate\Support\Facades\Log;
use RuntimeException;
use Throwable;

class ChatProviderException extends RuntimeException
{
    /**
     * @param  array<string, mixed>  $failureContext
     */
    public function __construct(
        string $message,
        public readonly string $userMessage = 'DORA is temporarily unable to generate a response. Please try again.',
        public readonly string $errorCode = 'provider_error',
        public readonly array $failureContext = [],
        int $code = 0,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function report(): void
    {
        Log::warning('DORA provider operation failed.', array_merge([
            'message' => $this->getMessage(),
            'error_code' => $this->errorCode,
        ], $this->safeContext()));
    }

    /**
     * @return array<string, mixed>
     */
    public function context(): array
    {
        return $this->safeContext();
    }

    /**
     * @return array<string, mixed>
     */
    private function safeContext(): array
    {
        $safe = [];

        foreach ($this->failureContext as $key => $value) {
            if (! is_string($key) || in_array($key, [
                'api_key',
                'password',
                'token',
                'invitation_token',
                'session_id',
                'content',
                'message',
                'body',
                'arguments',
                'payload',
            ], true)) {
                continue;
            }

            if (is_scalar($value) || $value === null) {
                $safe[$key] = is_string($value) ? mb_substr($value, 0, 120) : $value;
            }
        }

        return $safe;
    }
}
