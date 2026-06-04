<?php

declare(strict_types=1);

namespace Perkamo;

use DateTimeImmutable;
use DateTimeInterface;
use DateTimeZone;
use InvalidArgumentException;
use JsonSerializable;

final class EventInput implements JsonSerializable
{
    public readonly EventContext $context;
    public readonly ?string $occurredAt;

    /**
     * @param EventContext|array<string, mixed> $context
     */
    public function __construct(
        public readonly string $userId,
        public readonly string $event,
        EventContext|array $context = [],
        public readonly ?string $transactionId = null,
        DateTimeInterface|string|null $occurredAt = null,
    ) {
        self::assertString('user_id', $this->userId, 256);
        self::assertString('event', $this->event, 128);
        if ($this->transactionId !== null) {
            self::assertString('transaction_id', $this->transactionId, 256);
        }

        $this->context = $context instanceof EventContext ? $context : EventContext::fromArray($context);
        $this->occurredAt = self::normalizeOccurredAt($occurredAt);
    }

    public static function create(string $userId, string $event): self
    {
        return new self($userId, $event);
    }

    /**
     * @param array{
     *     user_id?: mixed,
     *     event?: mixed,
     *     transaction_id?: mixed,
     *     context?: mixed,
     *     occurred_at?: mixed
     * } $payload
     */
    public static function fromArray(array $payload): self
    {
        $context = $payload['context'] ?? [];
        if (!is_array($context) && !$context instanceof EventContext) {
            throw new InvalidArgumentException("Perkamo event field 'context' must be an array or EventContext");
        }

        return new self(
            self::requireString($payload, 'user_id'),
            self::requireString($payload, 'event'),
            $context,
            self::optionalString($payload, 'transaction_id'),
            self::optionalDateTime($payload, 'occurred_at'),
        );
    }

    public function withTransactionId(string $transactionId): self
    {
        return new self($this->userId, $this->event, $this->context, $transactionId, $this->occurredAt);
    }

    /**
     * @param EventContext|array<string, mixed> $context
     */
    public function withContext(EventContext|array $context): self
    {
        return new self($this->userId, $this->event, $context, $this->transactionId, $this->occurredAt);
    }

    public function withContextValue(string $key, mixed $value): self
    {
        return $this->withContext($this->context->with($key, $value));
    }

    public function withOccurredAt(DateTimeInterface|string $occurredAt): self
    {
        return new self($this->userId, $this->event, $this->context, $this->transactionId, $occurredAt);
    }

    /**
     * @return array{
     *     user_id: string,
     *     event: string,
     *     transaction_id: string,
     *     context: array<string, mixed>,
     *     occurred_at: string
     * }
     */
    public function toPayload(?string $defaultTransactionId = null, DateTimeInterface|string|null $defaultOccurredAt = null): array
    {
        $transactionId = $this->transactionId ?? $defaultTransactionId;
        if ($transactionId === null) {
            throw new InvalidArgumentException("Perkamo event field 'transaction_id' is required and must be a string");
        }

        self::assertString('transaction_id', $transactionId, 256);

        $payload = [
            'user_id' => $this->userId,
            'event' => $this->event,
            'transaction_id' => $transactionId,
            'context' => $this->context->toArray(),
            'occurred_at' => $this->occurredAt ?? self::normalizeOccurredAt($defaultOccurredAt),
        ];

        if ($payload['occurred_at'] === null) {
            throw new InvalidArgumentException("Perkamo event field 'occurred_at' is required and must be a string");
        }

        EventValidator::assertSafeEvent($payload);

        return $payload;
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return array_filter(
            [
                'user_id' => $this->userId,
                'event' => $this->event,
                'transaction_id' => $this->transactionId,
                'context' => $this->context->toArray(),
                'occurred_at' => $this->occurredAt,
            ],
            static fn (mixed $value): bool => $value !== null,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    private static function normalizeOccurredAt(DateTimeInterface|string|null $occurredAt): ?string
    {
        if ($occurredAt === null) {
            return null;
        }

        if ($occurredAt instanceof DateTimeInterface) {
            return DateTimeImmutable::createFromInterface($occurredAt)
                ->setTimezone(new DateTimeZone('UTC'))
                ->format(DATE_ATOM);
        }

        if ($occurredAt === '') {
            throw new InvalidArgumentException("Perkamo event field 'occurred_at' must be a non-empty string");
        }

        return $occurredAt;
    }

    private static function assertString(string $field, string $value, int $maxLength): void
    {
        if ($value === '') {
            throw new InvalidArgumentException("Perkamo event field '{$field}' is required and must be a string");
        }
        if (strlen($value) > $maxLength) {
            throw new InvalidArgumentException(
                "Perkamo event field '{$field}' exceeds max length of {$maxLength} characters"
            );
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function requireString(array $payload, string $field): string
    {
        $value = $payload[$field] ?? null;
        if (!is_string($value)) {
            throw new InvalidArgumentException("Perkamo event field '{$field}' is required and must be a string");
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function optionalString(array $payload, string $field): ?string
    {
        $value = $payload[$field] ?? null;
        if ($value === null) {
            return null;
        }
        if (!is_string($value)) {
            throw new InvalidArgumentException("Perkamo event field '{$field}' must be a string when provided");
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function optionalDateTime(array $payload, string $field): DateTimeInterface|string|null
    {
        $value = $payload[$field] ?? null;
        if ($value === null || is_string($value) || $value instanceof DateTimeInterface) {
            return $value;
        }

        throw new InvalidArgumentException("Perkamo event field '{$field}' must be a string when provided");
    }
}
