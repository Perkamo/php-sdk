<?php

declare(strict_types=1);

namespace Perkamo;

use DateTimeImmutable;
use InvalidArgumentException;

final class BrowserToken
{
    public function __construct(
        public readonly string $token,
        public readonly string $tokenType,
        public readonly DateTimeImmutable $expiresAt,
        public readonly int $expiresIn,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $token = self::requiredString($payload, 'token');
        $tokenType = self::requiredString($payload, 'token_type');
        $expiresAt = self::requiredString($payload, 'expires_at');
        $expiresIn = $payload['expires_in'] ?? null;

        if (!is_int($expiresIn)) {
            throw new InvalidArgumentException('Perkamo browser token expires_in must be an integer');
        }

        return new self(
            $token,
            $tokenType,
            new DateTimeImmutable($expiresAt),
            $expiresIn,
        );
    }

    /**
     * @param array<string, mixed> $payload
     */
    private static function requiredString(array $payload, string $key): string
    {
        $value = $payload[$key] ?? null;
        if (!is_string($value) || trim($value) === '') {
            throw new InvalidArgumentException('Perkamo browser token payload field "' . $key . '" must be a non-empty string');
        }

        return $value;
    }
}
