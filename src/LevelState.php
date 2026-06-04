<?php

declare(strict_types=1);

namespace Perkamo;

use InvalidArgumentException;
use JsonSerializable;

final class LevelState implements JsonSerializable
{
    public function __construct(
        public readonly string $wallet,
        public readonly int $level,
        public readonly int|float $current,
        public readonly int|float $currentLevelAt,
        public readonly int|float|null $nextLevelAt,
        public readonly int|float $progress,
    ) {
        if ($this->wallet === '') {
            throw new InvalidArgumentException("Perkamo level field 'wallet' is required and must be a string");
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            self::stringField($payload, 'wallet'),
            self::intField($payload, 'level'),
            self::numberField($payload, 'current'),
            self::numberField($payload, 'currentLevelAt'),
            self::nullableNumberField($payload, 'nextLevelAt'),
            self::numberField($payload, 'progress'),
        );
    }

    /**
     * @return array{
     *     wallet: string,
     *     level: int,
     *     current: int|float,
     *     currentLevelAt: int|float,
     *     nextLevelAt: int|float|null,
     *     progress: int|float
     * }
     */
    public function toArray(): array
    {
        return [
            'wallet' => $this->wallet,
            'level' => $this->level,
            'current' => $this->current,
            'currentLevelAt' => $this->currentLevelAt,
            'nextLevelAt' => $this->nextLevelAt,
            'progress' => $this->progress,
        ];
    }

    /**
     * @return array{
     *     wallet: string,
     *     level: int,
     *     current: int|float,
     *     currentLevelAt: int|float,
     *     nextLevelAt: int|float|null,
     *     progress: int|float
     * }
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function stringField(array $payload, string $field): string
    {
        $value = $payload[$field] ?? null;
        if (!is_string($value) || $value === '') {
            throw new InvalidArgumentException("Perkamo level field '{$field}' is required and must be a string");
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function intField(array $payload, string $field): int
    {
        $value = $payload[$field] ?? null;
        if (!is_int($value)) {
            throw new InvalidArgumentException("Perkamo level field '{$field}' is required and must be an integer");
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function numberField(array $payload, string $field): int|float
    {
        $value = $payload[$field] ?? null;
        if (!is_int($value) && !is_float($value)) {
            throw new InvalidArgumentException("Perkamo level field '{$field}' is required and must be a number");
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function nullableNumberField(array $payload, string $field): int|float|null
    {
        $value = $payload[$field] ?? null;
        if ($value === null || is_int($value) || is_float($value)) {
            return $value;
        }

        throw new InvalidArgumentException("Perkamo level field '{$field}' must be a number or null");
    }
}
