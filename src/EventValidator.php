<?php

declare(strict_types=1);

namespace Perkamo;

use InvalidArgumentException;

final class EventValidator
{
    /**
     * @param array<string, mixed> $event
     */
    public static function assertSafeEvent(array $event): void
    {
        self::assertStringField($event, 'user_id', 256);
        self::assertStringField($event, 'event', 128);
        self::assertStringField($event, 'transaction_id', 256);

        $context = $event['context'] ?? [];
        if ($context instanceof EventContext) {
            return;
        }
        if (!is_array($context)) {
            throw new InvalidArgumentException("Perkamo event field 'context' must be an array or EventContext");
        }

        EventContext::fromArray($context);
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
