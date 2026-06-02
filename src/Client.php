<?php

declare(strict_types=1);

namespace Perkamo;

use Perkamo\Exception\PerkamoApiException;
use RuntimeException;

final class Client
{
    public function __construct(
        private readonly string $baseUrl,
        private readonly string $tenant,
        private readonly string $apiKey,
        private readonly int $timeoutSeconds = 10,
    ) {
        if ($this->baseUrl === '') {
            throw new RuntimeException('Perkamo baseUrl is required');
        }
        if ($this->tenant === '') {
            throw new RuntimeException('Perkamo tenant is required');
        }
        if ($this->apiKey === '') {
            throw new RuntimeException('Perkamo apiKey is required');
        }
    }

    /**
     * @param array<string, mixed> $context
     * @return array<string, mixed>
     */
    public function emit(
        string $userId,
        string $event,
        array $context = [],
        ?string $transactionId = null,
        ?string $occurredAt = null,
    ): array {
        $payload = [
            'tenant' => $this->tenant,
            'user_id' => $userId,
            'event' => $event,
            'transaction_id' => $transactionId ?? $this->createTransactionId(),
            'context' => $context,
            'occurred_at' => $occurredAt ?? gmdate('c'),
        ];
        EventValidator::assertSafeEvent($payload);

        return $this->request('POST', '/v1/events', $payload, true);
    }

    /**
     * @param list<array<string, mixed>> $events
     * @return array<string, mixed>
     */
    public function batch(array $events): array
    {
        $payloadEvents = array_map(function (array $event): array {
            $payload = [
                'tenant' => $event['tenant'] ?? $this->tenant,
                'user_id' => $event['user_id'] ?? null,
                'event' => $event['event'] ?? null,
                'transaction_id' => $event['transaction_id'] ?? null,
                'context' => $event['context'] ?? null,
                'occurred_at' => $event['occurred_at'] ?? null,
            ];
            EventValidator::assertSafeEvent($payload);
            return $payload;
        }, $events);

        return $this->request('POST', '/v1/events:batch', ['events' => $payloadEvents], true);
    }

    /**
     * @return array<string, mixed>
     */
    public function profile(string $userId): array
    {
        return $this->request('GET', '/v1/profile/' . rawurlencode($userId), null, false);
    }

    private function createTransactionId(): string
    {
        return 'tx_' . bin2hex(random_bytes(16));
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
