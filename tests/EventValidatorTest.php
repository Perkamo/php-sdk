<?php

declare(strict_types=1);

namespace Perkamo\Tests;

use InvalidArgumentException;
use Perkamo\EventInput;
use Perkamo\EventValidator;
use PHPUnit\Framework\TestCase;

final class EventValidatorTest extends TestCase
{
    public function testRejectsReservedContextFields(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('reserved Perkamo context field');

        EventValidator::assertSafeEvent([
            'user_id' => 'customer_123',
            'event' => 'purchase.completed',
            'transaction_id' => 'order_1',
            'context' => ['wallet' => 100],
        ]);
    }

    public function testAcceptsBusinessFacts(): void
    {
        EventValidator::assertSafeEvent([
            'user_id' => 'customer_123',
            'event' => 'purchase.completed',
            'transaction_id' => 'order_1',
            'context' => ['order_id' => 'order_1', 'amount' => 12900],
        ]);

        self::assertTrue(true);
    }

    public function testAcceptsTypedEventContext(): void
    {
        EventValidator::assertSafeEvent(
            EventInput::create('customer_123', 'purchase.completed')
                ->withTransactionId('order_1')
                ->withContextValue('order_id', 'order_1')
                ->toPayload(defaultOccurredAt: '2026-05-31T12:00:00+00:00'),
        );

        self::assertTrue(true);
    }
}
