<?php

declare(strict_types=1);

namespace Perkamo;

use DateTimeInterface;
use InvalidArgumentException;
use Perkamo\Exception\PerkamoApiException;
use RuntimeException;

final class Client
{
    public const DEFAULT_BASE_URL = 'https://api.perkamo.com';

    private readonly string $baseUrl;
    private readonly string $apiKey;

    public function __construct(
        string $baseUrl = self::DEFAULT_BASE_URL,
        ?string $apiKey = null,
        private readonly int $timeoutSeconds = 10,
    ) {
        if ($apiKey === null) {
            if (func_num_args() === 0) {
                $apiKey = '';
            } else {
                $apiKey = $baseUrl;
                $baseUrl = self::DEFAULT_BASE_URL;
            }
        }

        $baseUrl = trim($baseUrl) === '' ? self::DEFAULT_BASE_URL : trim($baseUrl);
        $apiKey = trim($apiKey);

        if ($apiKey === '') {
            throw new RuntimeException('Perkamo apiKey is required');
        }

        $this->baseUrl = $baseUrl;
        $this->apiKey = $apiKey;
    }

    /**
     * @param EventContext|array<string, mixed> $context
     */
    public function emit(
        string $userId,
        string $event,
        EventContext|array $context = [],
        ?string $transactionId = null,
        DateTimeInterface|string|null $occurredAt = null,
    ): EventIngestResult {
        return $this->emitEvent(new EventInput($userId, $event, $context, $transactionId, $occurredAt));
    }

    public function emitEvent(EventInput $event): EventIngestResult
    {
        $payload = $event->toPayload($this->createTransactionId(), gmdate('c'));

        return EventIngestResult::fromArray($this->request('POST', '/v1/events', $payload, true));
    }

    /**
     * @param list<EventInput> $events
     */
    public function batch(array $events): BatchIngestResult
    {
        $payloadEvents = array_map(function (mixed $event): array {
            if (!$event instanceof EventInput) {
                throw new InvalidArgumentException('Perkamo batch events must be EventInput instances');
            }

            return $event->toPayload(null, gmdate('c'));
        }, $events);

        return BatchIngestResult::fromArray(
            $this->request('POST', '/v1/events:batch', ['events' => $payloadEvents], true),
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function profile(string $userId): array
    {
        return $this->request('GET', '/v1/profile/' . rawurlencode($userId), null, false);
    }

    public function createBrowserToken(
        string $browserKey,
        string $userId,
        ?int $ttlSeconds = null,
    ): BrowserToken {
        $payload = [
            'browser_key' => $this->nonEmptyString($browserKey, 'browserKey'),
            'user_id' => $this->nonEmptyString($userId, 'userId'),
        ];

        if ($ttlSeconds !== null) {
            if ($ttlSeconds < 1 || $ttlSeconds > 1800) {
                throw new InvalidArgumentException('Perkamo browser token TTL must be between 1 and 1800 seconds');
            }
            $payload['ttl_seconds'] = $ttlSeconds;
        }

        return BrowserToken::fromArray(
            $this->request('POST', '/v1/browser-tokens', $payload, true),
        );
    }

    public function createBrowserStreamToken(
        string $browserKey,
        string $userId,
        ?int $ttlSeconds = 120,
    ): BrowserToken {
        $payload = [
            'browser_key' => $this->nonEmptyString($browserKey, 'browserKey'),
            'user_id' => $this->nonEmptyString($userId, 'userId'),
            'purpose' => 'stream',
        ];

        if ($ttlSeconds !== null) {
            if ($ttlSeconds < 1 || $ttlSeconds > 1800) {
                throw new InvalidArgumentException('Perkamo browser token TTL must be between 1 and 1800 seconds');
            }
            $payload['ttl_seconds'] = $ttlSeconds;
        }

        return BrowserToken::fromArray(
            $this->request('POST', '/v1/browser-tokens', $payload, true),
        );
    }

    private function createTransactionId(): string
    {
        return 'tx_' . bin2hex(random_bytes(16));
    }

    private function nonEmptyString(string $value, string $label): string
    {
        $value = trim($value);
        if ($value === '') {
            throw new InvalidArgumentException('Perkamo ' . $label . ' must not be empty');
        }

        return $value;
    }

    /**
     * @param array<string, mixed>|null $payload
     * @return array<string, mixed>
     */
    private function request(
        string $method,
        string $path,
        ?array $payload,
        bool $signed,
    ): array {
        $body = $payload === null ? '' : json_encode($payload, JSON_THROW_ON_ERROR);
        $headers = [
            'accept: application/json',
            'x-perkamo-api-key: ' . $this->apiKey,
        ];

        if ($payload !== null) {
            $headers[] = 'content-type: application/json';
        }

        if ($signed) {
            $timestamp = (string) time();
            $headers[] = 'x-perkamo-timestamp: ' . $timestamp;
            $headers[] = 'x-perkamo-signature: ' . RequestSigner::sign(
                $this->apiKey,
                $timestamp,
                $method,
                $path,
                $body,
            );
        }

        $context = stream_context_create([
            'http' => [
                'method' => $method,
                'header' => implode("\r\n", $headers),
                'content' => $body,
                'ignore_errors' => true,
                'timeout' => $this->timeoutSeconds,
            ],
        ]);

        $responseBody = file_get_contents(rtrim($this->baseUrl, '/') . $path, false, $context);
        if ($responseBody === false) {
            throw new RuntimeException('Perkamo API request failed before receiving a response');
        }

        $statusCode = $this->statusCode($http_response_header ?? []);
        $decoded = $responseBody === ''
            ? []
            : json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new PerkamoApiException($statusCode, $decoded);
        }

        return is_array($decoded) ? $decoded : [];
    }

    /**
     * @param list<string> $headers
     */
    private function statusCode(array $headers): int
    {
        $status = $headers[0] ?? '';
        if (preg_match('/^HTTP\/\S+\s+(\d{3})/', $status, $matches) !== 1) {
            return 0;
        }

        return (int) $matches[1];
    }
}
