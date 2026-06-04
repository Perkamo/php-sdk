<?php

declare(strict_types=1);

namespace Perkamo\Tests;

use Perkamo\Exception\PerkamoApiException;
use PHPUnit\Framework\TestCase;

final class PerkamoApiExceptionTest extends TestCase
{
    public function testExposesErrorMetadata(): void
    {
        $exception = new PerkamoApiException(
            429,
            ['message' => 'Rate limit exceeded'],
            'req_test_123',
            '30',
            [
                'limit' => 100,
                'remaining' => 0,
                'reset' => '1767225600',
            ],
        );

        self::assertSame(429, $exception->statusCode());
        self::assertSame(['message' => 'Rate limit exceeded'], $exception->body());
        self::assertSame('req_test_123', $exception->requestId());
        self::assertSame('30', $exception->retryAfter());
        self::assertSame([
            'limit' => 100,
            'remaining' => 0,
            'reset' => '1767225600',
        ], $exception->rateLimit());
    }
}
