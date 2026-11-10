<?php

namespace App\Services\Chat;

use App\Exceptions\Chat\ChatProviderException;
use App\Exceptions\Chat\ProviderNotConfiguredException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class GeminiApiClient
{
    public function isConfigured(
        ?string $provider,
        ?string $model,
        ?string $apiKey,
        ?string $baseUrl,
        int $timeout,
    ): bool {
        return $provider === 'gemini'
            && $this->modelId($model) !== null
            && filled($apiKey)
            && $this->isValidBaseUrl($baseUrl)
            && $timeout > 0
            && $timeout <= 120;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array<string, mixed>
     */
    public function post(
        string $model,
        string $action,
        array $payload,
        ?string $apiKey,
        ?string $baseUrl,
        int $timeout,
    ): array {
        if (! $this->isConfigured('gemini', $model, $apiKey, $baseUrl, $timeout)) {
            throw new ProviderNotConfiguredException('The Gemini provider is not configured.');
        }

        $modelId = $this->modelId($model);
        $url = rtrim((string) $baseUrl, '/').'/models/'.rawurlencode((string) $modelId).':'.$action;

        try {
            $this->allowHttpTimeoutToHandleFailure($timeout);

            $response = Http::acceptJson()
                ->withHeader('x-goog-api-key', (string) $apiKey)
                ->connectTimeout(min($timeout, 5))
                ->timeout($timeout)
                ->retry(
                    [250],
                    when: fn (Throwable $exception): bool => $exception instanceof RequestException
                        && in_array($exception->response->status(), [500, 502, 503, 504], true),
                    throw: false,
                )
                ->post($url, $payload);
        } catch (ConnectionException $exception) {
            throw new ChatProviderException('The Gemini request timed out or the service could not be reached.', previous: $exception);
        }

        if (! $response->successful()) {
            $this->throwForUnsuccessfulResponse($response, $action, $payload);
        }

        $data = $response->json();

        if (! is_array($data)) {
            throw new ChatProviderException('Gemini returned a malformed response.');
        }

        return $data;
    }

    /**
     * @param  array<string, mixed>  $payload
     * @param  callable(array<string, mixed>): void  $onChunk
     */
    public function postStream(
        string $model,
        array $payload,
        ?string $apiKey,
        ?string $baseUrl,
        int $timeout,
        callable $onChunk,
    ): void {
        if (! $this->isConfigured('gemini', $model, $apiKey, $baseUrl, $timeout)) {
            throw new ProviderNotConfiguredException('The Gemini provider is not configured.');
        }

        $modelId = $this->modelId($model);
        $url = rtrim((string) $baseUrl, '/').'/models/'.rawurlencode((string) $modelId).':streamGenerateContent?alt=sse';

        try {
            $this->allowHttpTimeoutToHandleFailure($timeout);

            $response = Http::acceptJson()
                ->withHeader('x-goog-api-key', (string) $apiKey)
                ->connectTimeout(min($timeout, 5))
                ->timeout($timeout)
                ->retry(
                    [250],
                    when: fn (Throwable $exception): bool => $exception instanceof RequestException
                        && in_array($exception->response->status(), [500, 502, 503, 504], true),
                    throw: false,
                )
                ->withOptions(['stream' => false])
                ->post($url, $payload);
        } catch (ConnectionException $exception) {
            throw new ChatProviderException('The Gemini request timed out or the service could not be reached.', previous: $exception);
        }

        if (! $response->successful()) {
            $this->throwForUnsuccessfulResponse($response, 'streamGenerateContent', $payload);
        }

        $stream = $response->resource();
        $dataLines = [];

        try {
            while (($line = fgets($stream)) !== false) {
                $line = rtrim($line, "\r\n");

                if ($line === '') {
                    $this->dispatchStreamEvent($dataLines, $onChunk);
                    $dataLines = [];

                    continue;
                }

                if (str_starts_with($line, 'data:')) {
                    $dataLines[] = ltrim(substr($line, strlen('data:')));
                }
            }

            $this->dispatchStreamEvent($dataLines, $onChunk);
        } finally {
            if (is_resource($stream)) {
                fclose($stream);
            }
        }
    }

    /**
     * @param  list<string>  $dataLines
     * @param  callable(array<string, mixed>): void  $onChunk
     */
    private function dispatchStreamEvent(array $dataLines, callable $onChunk): void
    {
        if ($dataLines === []) {
            return;
        }

        $data = implode("\n", $dataLines);

        if ($data === '[DONE]') {
            return;
        }

        try {
            $chunk = json_decode($data, true, flags: JSON_THROW_ON_ERROR);
        } catch (\JsonException $exception) {
            throw new ChatProviderException('Gemini returned a malformed streaming response.', previous: $exception);
        }

        if (! is_array($chunk)) {
            throw new ChatProviderException('Gemini returned a malformed streaming response.');
        }

        $onChunk($chunk);
    }

    private function modelId(?string $model): ?string
    {
        $modelId = trim((string) $model);

        if (str_starts_with($modelId, 'models/')) {
            $modelId = substr($modelId, strlen('models/'));
        }

        return preg_match('/^[A-Za-z0-9][A-Za-z0-9._-]{0,127}$/', $modelId) === 1
            ? $modelId
            : null;
    }

    private function throwForUnsuccessfulResponse(Response $response, string $action, array $payload): never
    {
        $error = $response->json('error');
        $providerMessage = is_array($error) && is_string($error['message'] ?? null)
            ? $this->sanitizeProviderMessage($error['message'], $payload)
            : null;

        Log::warning('Gemini API request failed.', [
            'action' => $action,
            'http_status' => $response->status(),
            'provider_code' => is_array($error) && is_int($error['code'] ?? null)
                ? $error['code']
                : null,
            'provider_status' => is_array($error) && is_string($error['status'] ?? null)
                ? mb_substr($error['status'], 0, 80)
                : null,
            'provider_message' => $providerMessage,
        ]);

        [$message, $userMessage, $errorCode] = match ($response->status()) {
            401, 403 => [
                'Gemini authentication failed.',
                'DORA is temporarily unable to generate a response. Please try again.',
                'provider_error',
            ],
            429 => [
                'Gemini rate limit was exceeded.',
                'DORA is temporarily unable to generate a response. Please try again shortly.',
                'provider_rate_limited',
            ],
            default => $response->serverError()
                ? [
                    'Gemini is temporarily unavailable (HTTP '.$response->status().').',
                    'DORA is temporarily unable to generate a response. Please try again.',
                    'provider_unavailable',
                ]
                : [
                    'Gemini rejected the request (HTTP '.$response->status().').',
                    'DORA is temporarily unable to generate a response. Please try again.',
                    'provider_error',
                ],
        };

        throw new ChatProviderException(
            $message,
            userMessage: $userMessage,
            errorCode: $errorCode,
            failureContext: [
                'action' => $action,
                'http_status' => $response->status(),
                'provider_status' => is_array($error) && is_string($error['status'] ?? null)
                    ? mb_substr($error['status'], 0, 80)
                    : null,
            ],
        );
    }

    private function sanitizeProviderMessage(string $message, array $payload): string
    {
        $inputValues = $this->stringValues($payload);
        usort($inputValues, static fn (string $left, string $right): int => mb_strlen($right) <=> mb_strlen($left));

        foreach ($inputValues as $inputValue) {
            if (mb_strlen($inputValue) >= 8) {
                $message = str_replace($inputValue, '[REDACTED_INPUT]', $message);
            }
        }

        $message = preg_replace('/[\x00-\x1F\x7F]/u', ' ', $message) ?? '';
        $message = preg_replace('/AIza[0-9A-Za-z_-]{20,}/', '[REDACTED]', $message) ?? '';
        $message = preg_replace('/Bearer\s+\S+/i', 'Bearer [REDACTED]', $message) ?? '';
        $message = preg_replace(
            '/\b[A-Z0-9._%+-]+@[A-Z0-9.-]+\.[A-Z]{2,}\b/i',
            '[REDACTED_EMAIL]',
            $message,
        ) ?? '';

        return mb_substr(trim($message), 0, 500);
    }

    /**
     * @param  array<array-key, mixed>  $data
     * @return list<string>
     */
    private function stringValues(array $data): array
    {
        $values = [];

        foreach ($data as $value) {
            if (is_string($value)) {
                $values[] = $value;
            } elseif (is_array($value)) {
                array_push($values, ...$this->stringValues($value));
            }
        }

        return $values;
    }

    private function allowHttpTimeoutToHandleFailure(int $timeout): void
    {
        if (PHP_SAPI === 'cli') {
            return;
        }

        $requiredExecutionTime = $timeout + 10;
        $currentExecutionTime = ini_get('max_execution_time');

        if (is_string($currentExecutionTime)
            && ((int) $currentExecutionTime === 0 || (int) $currentExecutionTime >= $requiredExecutionTime)) {
            return;
        }

        if (! function_exists('set_time_limit') || ! set_time_limit($requiredExecutionTime)) {
            throw new ChatProviderException('The PHP execution limit is too short for the configured Gemini timeout.');
        }
    }

    private function isValidBaseUrl(?string $baseUrl): bool
    {
        if (! is_string($baseUrl) || filter_var($baseUrl, FILTER_VALIDATE_URL) === false) {
            return false;
        }

        $parts = parse_url($baseUrl);

        return is_array($parts)
            && ($parts['scheme'] ?? null) === 'https'
            && filled($parts['host'] ?? null)
            && ! isset($parts['user'])
            && ! isset($parts['pass'])
            && ! isset($parts['query'])
            && ! isset($parts['fragment']);
    }
}
