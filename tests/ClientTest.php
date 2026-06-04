<?php

declare(strict_types=1);

namespace Perkamo\Tests;

use Perkamo\Client;
use PHPUnit\Framework\TestCase;
use RuntimeException;

final class ClientTest extends TestCase
{
    public function testDefaultsBaseUrlWhenOnlyApiKeyIsProvided(): void
    {
        $client = new Client('sk_test_secret');

        self::assertSame(Client::DEFAULT_BASE_URL, $this->property($client, 'baseUrl'));
        self::assertSame('sk_test_secret', $this->property($client, 'apiKey'));
    }

    public function testSupportsNamedApiKeyWithoutBaseUrl(): void
    {
        $client = new Client(apiKey: 'sk_test_secret');

        self::assertSame(Client::DEFAULT_BASE_URL, $this->property($client, 'baseUrl'));
        self::assertSame('sk_test_secret', $this->property($client, 'apiKey'));
    }

    public function testKeepsExplicitBaseUrlOverride(): void
    {
        $client = new Client('https://api.example.test', 'sk_test_secret');

        self::assertSame('https://api.example.test', $this->property($client, 'baseUrl'));
        self::assertSame('sk_test_secret', $this->property($client, 'apiKey'));
    }

    public function testRequiresApiKey(): void
    {
        $this->expectException(RuntimeException::class);
        $this->expectExceptionMessage('apiKey');

        new Client();
    }

    private function property(Client $client, string $name): string
    {
        $property = new \ReflectionProperty($client, $name);

        return $property->getValue($client);
    }
}
