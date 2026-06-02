<?php

declare(strict_types=1);

namespace Perkamo\Tests;

use Perkamo\RequestSigner;
use PHPUnit\Framework\TestCase;

final class RequestSignerTest extends TestCase
{
    public function testSignsMutatingRequests(): void
    {
        $signature = RequestSigner::sign(
            apiKey: 'sk_test',
            timestamp: '1700000000',
            method: 'POST',
            path: '/v1/events',
            body: '{"tenant":"demo"}',
        );

        self::assertMatchesRegularExpression('/^v1=[a-f0-9]{64}$/', $signature);
        self::assertSame(
            'v1=fe762eb8c15ef0eb3731e3a2d53a918a4dd918abdbd42bf04a5522646d79dcae',
            $signature,
        );
    }
}
