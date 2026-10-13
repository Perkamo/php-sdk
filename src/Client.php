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
    private readonly string $sdkIdentification;

    public function __construct(
        string $baseUrl = self::DEFAULT_BASE_URL,
        ?string $apiKey = null,
        private readonly int $timeoutSeconds = 10,
        ?string $sdkIdentification = null,
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
        $this->sdkIdentification = $sdkIdentification === null
            ? SdkIdentification::forPackage('perkamo/sdk', '0.9.0')
            : SdkIdentification::validate($sdkIdentification);
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
     * @param array<string, mixed> $traits
     * @return array<string, mixed>
     */
    public function identify(string $userId, array $traits = []): array
    {
        $response = $this->request(
            'POST',
            '/v1/identify',
            [
                'user_id' => $this->nonEmptyString($userId, 'userId'),
                'traits' => $traits,
            ],
            true,
        );

        if (isset($response['customer']) && is_array($response['customer'])) {
            $response['customer'] = $this->canonicalCustomer($response['customer']);
        }

        return $response;
    }

    /**
     * @return array<string, mixed>
     */
    public function customer(string $userId): array
    {
        return $this->canonicalCustomer(
            $this->request('GET', '/v1/customer/' . rawurlencode($userId), null, false),
        );
    }

    /**
     * Permanently erases a customer and suppresses the identifier from future
     * ingestion. Requires the customer:delete server-key scope.
     *
     * @return array<string, mixed>
     */
    public function deleteCustomer(string $userId): array
    {
        return $this->request(
            'DELETE',
            '/v1/customer/' . rawurlencode($this->nonEmptyString($userId, 'userId')),
            null,
            true,
        );
    }

    /**
     * Redeems a configured reward, subtracting its wallet cost and returning the
     * declarative effect (coupon, free_shipping, gift_card, entitlement or
     * custom) to apply on your side. Reuse a stable transaction id to make
     * retries idempotent.
     */
    public function redeem(
        string $userId,
        string $rewardKey,
        ?string $transactionId = null,
    ): RedeemRewardResult {
        return RedeemRewardResult::fromArray($this->request(
            'POST',
            '/v1/rewards/redeem',
            [
                'user_id' => $this->nonEmptyString($userId, 'userId'),
                'reward_key' => $this->nonEmptyString($rewardKey, 'rewardKey'),
                'transaction_id' => $transactionId ?? $this->createTransactionId(),
            ],
            true,
        ));
    }

    /**
     * @return array<string, mixed>
     */
    public function program(): array
    {
        return $this->canonicalProgram($this->request('GET', '/v1/program', null, false));
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function eventCatalog(): array
    {
        $earningRules = $this->program()['blueprint']['earningRules'] ?? [];

        return is_array($earningRules) ? array_values($earningRules) : [];
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function eventDefinitions(): array
    {
        $program = $this->program();
        $definitions = $program['blueprint']['eventDefinitions'] ?? null;
        if (is_array($definitions)) {
            return array_values($definitions);
        }

        $earningRules = $program['blueprint']['earningRules'] ?? [];
        if (!is_array($earningRules)) {
            return [];
        }

        return array_map(
            static fn (array $rule): array => [
                'key' => $rule['event'] ?? '',
                'name' => $rule['title'] ?? ($rule['event'] ?? ''),
                'description' => $rule['description'] ?? null,
                'type' => 'custom',
                'status' => 'active',
                'references' => ['earningRule' => true, 'goals' => []],
                'observationCount' => 0,
            ],
            array_values(array_filter($earningRules, 'is_array')),
        );
    }

    /**
     * Remove v1 transport aliases from the official SDK customer model.
     *
     * @param array<string, mixed> $customer
     * @return array<string, mixed>
     */
    private function canonicalCustomer(array $customer): array
    {
        unset($customer['achievements']);

        if (isset($customer['next_perks']) && is_array($customer['next_perks'])) {
            $customer['next_perks'] = array_map(
                static function (mixed $perk): mixed {
                    if (!is_array($perk)) {
                        return $perk;
                    }
                    unset($perk['trigger']);

                    return $perk;
                },
                $customer['next_perks'],
            );
        }

        if (isset($customer['perks']) && is_array($customer['perks'])) {
            $customer['perks'] = array_map(
                static function (mixed $perk): mixed {
                    if (!is_array($perk) || !isset($perk['metadata']) || !is_array($perk['metadata'])) {
                        return $perk;
                    }
                    unset($perk['metadata']['trigger']);

                    return $perk;
                },
                $customer['perks'],
            );
        }

        return $customer;
    }

    /**
     * Remove v1 transport aliases from the official SDK program model.
     *
     * @param array<string, mixed> $program
     * @return array<string, mixed>
     */
    private function canonicalProgram(array $program): array
    {
        if (!isset($program['blueprint']) || !is_array($program['blueprint'])) {
            return $program;
        }

        unset($program['blueprint']['passivePerks'], $program['blueprint']['achievements']);
        if (isset($program['blueprint']['earningRules']) && is_array($program['blueprint']['earningRules'])) {
            $program['blueprint']['earningRules'] = array_map(
                static function (mixed $rule): mixed {
                    if (!is_array($rule)) {
                        return $rule;
                    }
                    unset($rule['rewards']);

                    return $rule;
                },
                $program['blueprint']['earningRules'],
            );
        }

        return $program;
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
            'x-perkamo-sdk: ' . $this->sdkIdentification,
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

        $responseHeaders = $http_response_header ?? [];
        $statusCode = $this->statusCode($responseHeaders);
        $decoded = $responseBody === ''
            ? []
            : json_decode($responseBody, true, 512, JSON_THROW_ON_ERROR);

        if ($statusCode < 200 || $statusCode >= 300) {
            throw new PerkamoApiException(
                $statusCode,
                $decoded,
                $this->headerValue($responseHeaders, 'x-perkamo-request-id', 'x-request-id'),
                $this->headerValue($responseHeaders, 'retry-after'),
                [
                    'limit' => $this->headerNumber($responseHeaders, 'ratelimit-limit', 'x-ratelimit-limit'),
                    'remaining' => $this->headerNumber($responseHeaders, 'ratelimit-remaining', 'x-ratelimit-remaining'),
                    'reset' => $this->headerValue($responseHeaders, 'ratelimit-reset', 'x-ratelimit-reset'),
                ],
            );
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

    /**
     * @param list<string> $headers
     */
    private function headerValue(array $headers, string ...$names): ?string
    {
        $lookup = array_fill_keys(array_map('strtolower', $names), true);

        foreach ($headers as $header) {
            $separator = strpos($header, ':');
            if ($separator === false) {
                continue;
            }

            $name = strtolower(trim(substr($header, 0, $separator)));
            if (!isset($lookup[$name])) {
                continue;
            }

            $value = trim(substr($header, $separator + 1));

            return $value === '' ? null : $value;
        }

        return null;
    }

    /**
     * @param list<string> $headers
     */
    private function headerNumber(array $headers, string ...$names): ?int
    {
        $value = $this->headerValue($headers, ...$names);
        if ($value === null || !ctype_digit($value)) {
            return null;
        }

        return (int) $value;
    }
}
