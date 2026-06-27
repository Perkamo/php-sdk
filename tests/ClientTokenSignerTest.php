<?php

declare(strict_types=1);

namespace Perkamo\Tests;

use Perkamo\ClientTokenSigner;
use PHPUnit\Framework\TestCase;

final class ClientTokenSignerTest extends TestCase
{
    public function testSignsClientTokenWithExpectedHeaderAndClaims(): void
    {
        $token = ClientTokenSigner::sign(
            kid: 'csk_test_123',
            secret: 'cssk_test_secret',
            subject: 'customer_123',
            scope: ['customer:read', 'events:write'],
            events: ['page.view'],
            ttlSeconds: 600,
            now: 1780000000,
        );

        [$encodedHeader, $encodedPayload, $encodedSignature] = explode('.', $token);
        $header = json_decode($this->base64UrlDecode($encodedHeader), true, 512, JSON_THROW_ON_ERROR);
        $payload = json_decode($this->base64UrlDecode($encodedPayload), true, 512, JSON_THROW_ON_ERROR);

        self::assertSame('HS256', $header['alg']);
        self::assertSame('perkamo.client+jwt', $header['typ']);
        self::assertSame('csk_test_123', $header['kid']);
        self::assertSame('perkamo', $payload['iss']);
        self::assertSame('perkamo.browser', $payload['aud']);
        self::assertSame('customer_123', $payload['sub']);
        self::assertSame(['customer:read', 'events:write'], $payload['scope']);
        self::assertSame(['page.view'], $payload['events']);
        self::assertSame(1780000000, $payload['iat']);
        self::assertSame(1780000600, $payload['exp']);
        self::assertNotSame('', $encodedSignature);
    }

    private function base64UrlDecode(string $value): string
    {
        $padded = str_pad($value, (int) (ceil(strlen($value) / 4) * 4), '=', STR_PAD_RIGHT);

        return base64_decode(strtr($padded, '-_', '+/'), true) ?: '';
    }
}
