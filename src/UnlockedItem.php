<?php

declare(strict_types=1);

namespace Perkamo;

use InvalidArgumentException;
use JsonSerializable;

final class UnlockedItem implements JsonSerializable
{
    public function __construct(
        public readonly string $type,
        public readonly string $key,
        public readonly string $name,
    ) {
        self::assertString('type', $this->type);
        self::assertString('key', $this->key);
        self::assertString('name', $this->name);
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        return new self(
            self::stringField($payload, 'type'),
            self::stringField($payload, 'key'),
            self::stringField($payload, 'name'),
        );
    }

    /**
     * @return array{type: string, key: string, name: string}
     */
    public function toArray(): array
    {
        return [
            'type' => $this->type,
            'key' => $this->key,
            'name' => $this->name,
        ];
    }

    /**
     * @return array{type: string, key: string, name: string}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }

    private static function assertString(string $field, string $value): void
    {
        if ($value === '') {
            throw new InvalidArgumentException("Perkamo unlocked item field '{$field}' is required and must be a string");
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function stringField(array $payload, string $field): string
    {
        $value = $payload[$field] ?? null;
        if (!is_string($value) || $value === '') {
            throw new InvalidArgumentException("Perkamo unlocked item field '{$field}' is required and must be a string");
        }

        return $value;
    }
}
