<?php

declare(strict_types=1);

namespace Perkamo;

use InvalidArgumentException;
use JsonSerializable;

final class EventIngestResult implements JsonSerializable
{
    /**
     * @param list<WalletDelta> $delta
     * @param list<UnlockedItem> $unlocked
     * @param array<string, int|float> $walletState
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public readonly bool $applied,
        public readonly bool $duplicate,
        public readonly array $delta,
        public readonly bool $leveledUp,
        public readonly array $unlocked,
        public readonly array $walletState,
        public readonly ?LevelState $level,
        private readonly array $raw,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $delta = self::listField($payload, 'delta', WalletDelta::fromArray(...));
        $unlocked = self::listField($payload, 'unlocked', UnlockedItem::fromArray(...));

        $level = $payload['level'] ?? null;
        if ($level !== null && !is_array($level)) {
            throw new InvalidArgumentException("Perkamo event ingest field 'level' must be an object or null");
        }

        return new self(
            self::boolField($payload, 'applied'),
            self::boolField($payload, 'duplicate'),
            $delta,
            self::boolField($payload, 'leveled_up'),
            $unlocked,
            self::walletStateField($payload),
            $level === null ? null : LevelState::fromArray($level),
            $payload,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->raw;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function boolField(array $payload, string $field): bool
    {
        $value = $payload[$field] ?? null;
        if (!is_bool($value)) {
            throw new InvalidArgumentException("Perkamo event ingest field '{$field}' is required and must be a boolean");
        }

        return $value;
    }

    /**
     * @template T
     * @param array<string, mixed> $payload
     * @param callable(array<string, mixed>): T $factory
     * @return list<T>
     */
    private static function listField(array $payload, string $field, callable $factory): array
    {
        $value = $payload[$field] ?? null;
        if (!is_array($value) || !array_is_list($value)) {
            throw new InvalidArgumentException("Perkamo event ingest field '{$field}' is required and must be a list");
        }

        return array_map(
            static function (mixed $item) use ($field, $factory): mixed {
                if (!is_array($item)) {
                    throw new InvalidArgumentException("Perkamo event ingest field '{$field}' items must be objects");
                }

                return $factory($item);
            },
            $value,
        );
    }

    /**
     * @param array<string, mixed> $payload
     * @return array<string, int|float>
     */
    private static function walletStateField(array $payload): array
    {
        $value = $payload['wallet_state'] ?? null;
        if (!is_array($value)) {
            throw new InvalidArgumentException("Perkamo event ingest field 'wallet_state' is required and must be an object");
        }

        $walletState = [];
        foreach ($value as $wallet => $amount) {
            if (!is_string($wallet) || $wallet === '') {
                throw new InvalidArgumentException("Perkamo event ingest wallet_state keys must be non-empty strings");
            }
            if (!is_int($amount) && !is_float($amount)) {
                throw new InvalidArgumentException("Perkamo event ingest wallet_state values must be numbers");
            }
            $walletState[$wallet] = $amount;
        }

        return $walletState;
    }
}
