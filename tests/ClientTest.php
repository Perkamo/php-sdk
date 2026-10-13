<?php

declare(strict_types=1);

namespace Perkamo\Tests;

use Perkamo\Client;
use Perkamo\SdkIdentification;
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

    public function testIdentifiesTheInstalledPhpSdkWithoutExposingSecrets(): void
    {
        $client = new Client('sk_test_secret');

        self::assertSame('perkamo/sdk@0.9.0', $this->property($client, 'sdkIdentification'));
        self::assertSame(
            '@perkamo/browser@1.2.3-beta.1',
            SdkIdentification::validate('@perkamo/browser@1.2.3-beta.1'),
        );
    }

    public function testRejectsUnsafeSdkIdentificationOverrides(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        new Client('sk_test_secret', sdkIdentification: "perkamo/sdk@1.0.0\r\nx-evil: true");
    }

    public function testExposesProgramCatalogHelpers(): void
    {
        self::assertTrue(method_exists(Client::class, 'program'));
        self::assertTrue(method_exists(Client::class, 'eventCatalog'));
        self::assertTrue(method_exists(Client::class, 'eventDefinitions'));
    }

    public function testExposesCustomerIdentifyHelper(): void
    {
        self::assertTrue(method_exists(Client::class, 'identify'));
        self::assertTrue(method_exists(Client::class, 'deleteCustomer'));
    }

    public function testCanonicalCustomerResponseOmitsDeprecatedAliases(): void
    {
        $client = new Client('sk_test_secret');
        $customer = $this->invokeArrayNormalizer($client, 'canonicalCustomer', [
            'user_id' => 'customer_123',
            'goals' => ['progress' => [], 'completions' => []],
            'achievements' => ['completed' => [], 'in_progress' => [], 'unlocked' => []],
            'next_perks' => [[
                'key' => 'vip',
                'unlock' => ['type' => 'points_threshold', 'amount' => 100],
                'trigger' => ['type' => 'points_threshold', 'amount' => 100],
            ]],
            'perks' => [[
                'key' => 'welcome',
                'metadata' => [
                    'unlock' => ['type' => 'manual'],
                    'trigger' => ['type' => 'manual'],
                ],
            ]],
        ]);

        self::assertArrayNotHasKey('achievements', $customer);
        self::assertArrayNotHasKey('trigger', $customer['next_perks'][0]);
        self::assertArrayNotHasKey('trigger', $customer['perks'][0]['metadata']);
        self::assertSame(['progress' => [], 'completions' => []], $customer['goals']);
    }

    public function testCanonicalProgramResponseOmitsDeprecatedAliases(): void
    {
        $client = new Client('sk_test_secret');
        $program = $this->invokeArrayNormalizer($client, 'canonicalProgram', [
            'blueprint' => [
                'earningRules' => [[
                    'event' => 'purchase.completed',
                    'awards' => [['wallet' => 'points', 'amount' => 10]],
                    'rewards' => [['wallet' => 'points', 'amount' => 10]],
                ]],
                'perks' => [],
                'goals' => [],
                'passivePerks' => [],
                'achievements' => [],
            ],
        ]);

        self::assertArrayNotHasKey('passivePerks', $program['blueprint']);
        self::assertArrayNotHasKey('achievements', $program['blueprint']);
        self::assertArrayNotHasKey('rewards', $program['blueprint']['earningRules'][0]);
        self::assertSame(
            [['wallet' => 'points', 'amount' => 10]],
            $program['blueprint']['earningRules'][0]['awards'],
        );
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

    /**
     * @param array<string, mixed> $payload
     * @return array<string, mixed>
     */
    private function invokeArrayNormalizer(Client $client, string $method, array $payload): array
    {
        $reflection = new \ReflectionMethod($client, $method);
        $result = $reflection->invoke($client, $payload);

        self::assertIsArray($result);

        return $result;
    }
}
