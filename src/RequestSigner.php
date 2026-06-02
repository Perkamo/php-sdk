<?php

declare(strict_types=1);

namespace Perkamo;

final class RequestSigner
{
    private const VERSION = 'v1';

    public static function sign(
        string $apiKey,
        string $timestamp,
        string $method,
        string $path,
        string $body,
    ): string {
        $baseString = implode('.', [
            self::VERSION,
            $timestamp,
            strtoupper($method),
            $path,
            hash('sha256', $body),
        ]);

        return self::VERSION . '=' . hash_hmac('sha256', $baseString, $apiKey);
    }
}
