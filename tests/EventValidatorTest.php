<?php

declare(strict_types=1);

namespace Perkamo\Tests;

use InvalidArgumentException;
use Perkamo\EventValidator;
use PHPUnit\Framework\TestCase;

final class EventValidatorTest extends TestCase
{
    public function testRejectsReservedContextFields(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('reserved Perkamo context field');

        EventValidator::assertSafeEvent([
            'space' => 'demo',
            'user_id' => 'customer_123',
            'event' => 'purchase.completed',
            'transaction_id' => 'order_1',
            'context' => ['wallet' => 100],
        ]);
    }

    public function testAcceptsBusinessFacts(): void
    {
        EventValidator::assertSafeEvent([
            'space' => 'demo',
            'user_id' => 'customer_123',
            'event' => 'purchase.completed',
            'transaction_id' => 'order_1',
            'context' => ['order_id' => 'order_1', 'amount' => 12900],
        ]);

        self::assertTrue(true);
    }
}
