<?php

declare(strict_types=1);

namespace Perkamo;

use InvalidArgumentException;
use JsonSerializable;

/**
 * @phpstan-type ContextValue string|int|float|bool|null|array<mixed>
 */
final class EventContext implements JsonSerializable
{
    /** @var array<string, true> */
    private const RESERVED_CONTEXT_KEYS = [
        'xp' => true,
        'wallet' => true,
        'wallets' => true,
        'rewards' => true,
        'level' => true,
        'perks' => true,
        'achievements' => true,
    ];

    /** @var array<string, mixed> */
    private readonly array $values;

    /**
     * @param array<string, mixed> $values
     */
    public function __construct(array $values = [])
    {
        $this->values = self::normalizeObject($values, 'context');
    }

    /**
     * @param array<string, mixed> $values
     */
    public static function fromArray(array $values): self
    {
        return new self($values);
    }

    public static function empty(): self
    {
        return new self();
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->values);
    }

    public function get(string $key): mixed
    {
        return $this->values[$key] ?? null;
    }

    public function with(string $key, mixed $value): self
    {
        $values = $this->values;
        $values[$key] = $value;

        return new self($values);
    }

    public function without(string $key): self
    {
        $values = $this->values;
        unset($values[$key]);

        return new self($values);
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->values;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    /**
     * @param array<mixed> $values
     * @return array<string, mixed>
     */
    private static function normalizeObject(array $values, string $path): array
    {
        $normalized = [];

        foreach ($values as $key => $value) {
            if (!is_string($key) || $key === '') {
                throw new InvalidArgumentException("Perkamo {$path} keys must be non-empty strings");
            }

            if ($path === 'context' && isset(self::RESERVED_CONTEXT_KEYS[strtolower($key)])) {
                throw new InvalidArgumentException(
                    "Refusing to send reserved Perkamo context field '{$key}' from the SDK"
                );
            }

            $normalized[$key] = self::normalizeValue($value, "{$path}.{$key}");
        }

        return $normalized;
    }

    private static function normalizeValue(mixed $value, string $path): mixed
    {
        if ($value === null || is_string($value) || is_int($value) || is_bool($value)) {
            return $value;
        }

        if (is_float($value)) {
            if (!is_finite($value)) {
                throw new InvalidArgumentException("Perkamo {$path} must be a finite number");
            }

            return $value;
        }

        if (is_array($value)) {
            if (array_is_list($value)) {
                return array_map(
                    static fn (mixed $item): mixed => self::normalizeValue($item, "{$path}[]"),
                    $value,
                );
            }

            return self::normalizeObject($value, $path);
        }

        throw new InvalidArgumentException(
            sprintf('Perkamo %s must be JSON-serializable scalar data, got %s', $path, get_debug_type($value))
        );
    }
}
