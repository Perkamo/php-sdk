<?php

declare(strict_types=1);

namespace Perkamo\Tests;

use Perkamo\BatchIngestResult;
use Perkamo\EventIngestResult;
use PHPUnit\Framework\TestCase;

final class EventIngestResultTest extends TestCase
{
    public function testBuildsTypedEventResultFromApiPayload(): void
    {
        $result = EventIngestResult::fromArray(self::eventPayload());

        self::assertTrue($result->applied);
        self::assertFalse($result->duplicate);
        self::assertSame('loyalty_points', $result->delta[0]->wallet);
        self::assertSame(10, $result->delta[0]->amount);
        self::assertSame('premium_discount', $result->unlocked[0]->key);
        self::assertSame(120, $result->walletState['loyalty_points']);
        self::assertSame(2, $result->level?->level);
        self::assertSame(self::eventPayload(), $result->toArray());
    }

    public function testBuildsTypedBatchResult(): void
    {
        $batch = BatchIngestResult::fromArray(['results' => [self::eventPayload()]]);

        self::assertCount(1, $batch->results);
        self::assertSame('loyalty_points', $batch->results[0]->delta[0]->wallet);
    }

    /**
     * @return array<string, mixed>
     */
    private static function eventPayload(): array
    {
        return [
            'applied' => true,
            'duplicate' => false,
            'delta' => [['wallet' => 'loyalty_points', 'amount' => 10]],
            'leveled_up' => true,
            'unlocked' => [
                [
                    'type' => 'perk',
                    'key' => 'premium_discount',
                    'name' => 'Premium discount',
                ],
            ],
            'wallet_state' => [
                'loyalty_points' => 120,
            ],
            'level' => [
                'wallet' => 'xp',
                'level' => 2,
                'current' => 300,
                'nextLevelAt' => 500,
                'currentLevelAt' => 250,
                'progress' => 0.2,
            ],
        ];
    }
}
