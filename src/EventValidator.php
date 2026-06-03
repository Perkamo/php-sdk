<?php

declare(strict_types=1);

namespace Perkamo;

use InvalidArgumentException;

final class EventValidator
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

    /**
     * @param array<string, mixed> $event
     */
    public static function assertSafeEvent(array $event): void
    {
        self::assertStringField($event, 'user_id', 256);
        self::assertStringField($event, 'event', 128);
        self::assertStringField($event, 'transaction_id', 256);

        $context = $event['context'] ?? [];
        if ($context !== null && !is_array($context)) {
            throw new InvalidArgumentException("Perkamo event field 'context' must be an array");
        }

        foreach (array_keys($context ?? []) as $key) {
            if (isset(self::RESERVED_CONTEXT_KEYS[strtolower((string) $key)])) {
                throw new InvalidArgumentException(
                    "Refusing to send reserved Perkamo context field '{$key}' from the SDK"
                );
            }
        }
    }

    /**
     * @param array<string, mixed> $event
     */
    private static function assertStringField(array $event, string $field, int $maxLength): void
    {
        $value = $event[$field] ?? null;
        if (!is_string($value) || $value === '') {
            throw new InvalidArgumentException("Perkamo event field '{$field}' is required and must be a string");
        }
        if (strlen($value) > $maxLength) {
            throw new InvalidArgumentException(
                "Perkamo event field '{$field}' exceeds max length of {$maxLength} characters"
            );
        }
    }
}
