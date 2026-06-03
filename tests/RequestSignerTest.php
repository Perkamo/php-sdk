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
            body: '{"space":"demo"}',
        );

        self::assertMatchesRegularExpression('/^v1=[a-f0-9]{64}$/', $signature);
        self::assertSame(
            'v1=6bbc1e5ba44b90642cb2f878838723f95d0272760c9bae93162479595e765621',
            $signature,
        );
    }
}
