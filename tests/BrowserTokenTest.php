<?php

declare(strict_types=1);

namespace Perkamo\Tests;

use InvalidArgumentException;
use Perkamo\BrowserToken;
use PHPUnit\Framework\TestCase;

final class BrowserTokenTest extends TestCase
{
    public function testCreatesTypedBrowserTokenFromApiPayload(): void
    {
        $token = BrowserToken::fromArray([
            'token' => 'jwt',
            'token_type' => 'Bearer',
            'expires_at' => '2026-06-04T12:10:00.000Z',
            'expires_in' => 600,
        ]);

        self::assertSame('jwt', $token->token);
        self::assertSame('Bearer', $token->tokenType);
        self::assertSame('2026-06-04T12:10:00+00:00', $token->expiresAt->format(DATE_ATOM));
        self::assertSame(600, $token->expiresIn);
    }

    public function testRejectsMalformedBrowserTokenPayloads(): void
    {
        $this->expectException(InvalidArgumentException::class);

        BrowserToken::fromArray([
            'token' => '',
            'token_type' => 'Bearer',
            'expires_at' => '2026-06-04T12:10:00.000Z',
            'expires_in' => 600,
        ]);
    }
}
