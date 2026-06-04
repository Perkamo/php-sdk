<?php

declare(strict_types=1);

namespace Perkamo\Tests;

use DateTimeImmutable;
use InvalidArgumentException;
use Perkamo\EventContext;
use PHPUnit\Framework\TestCase;

final class EventContextTest extends TestCase
{
    public function testBuildsImmutableJsonSafeContext(): void
    {
        $context = EventContext::fromArray(['order_id' => 'order_1'])
            ->with('amount', 12900)
            ->with('items', [
                ['sku' => 'sku_1', 'quantity' => 2],
            ]);

        self::assertSame(
            [
                'order_id' => 'order_1',
                'amount' => 12900,
                'items' => [
                    ['sku' => 'sku_1', 'quantity' => 2],
                ],
            ],
            $context->toArray(),
        );
        self::assertFalse(EventContext::fromArray(['order_id' => 'order_1'])->has('amount'));
    }

    public function testRejectsReservedContextFields(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('reserved Perkamo context field');

        EventContext::fromArray(['wallet' => 100]);
    }

    public function testRejectsNonJsonSafeValues(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('JSON-serializable scalar data');

        EventContext::fromArray(['ordered_at' => new DateTimeImmutable()]);
    }
}
