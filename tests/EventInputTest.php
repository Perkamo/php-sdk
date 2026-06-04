<?php

declare(strict_types=1);

namespace Perkamo\Tests;

use DateTimeImmutable;
use InvalidArgumentException;
use Perkamo\EventInput;
use PHPUnit\Framework\TestCase;

final class EventInputTest extends TestCase
{
    public function testBuildsEventPayloadWithDefaults(): void
    {
        $event = EventInput::create('customer_123', 'purchase.completed')
            ->withTransactionId('order_1092')
            ->withContextValue('order_id', 'order_1092')
            ->withContextValue('amount', 12900);

        self::assertSame(
            [
                'user_id' => 'customer_123',
                'event' => 'purchase.completed',
                'transaction_id' => 'order_1092',
                'context' => ['order_id' => 'order_1092', 'amount' => 12900],
                'occurred_at' => '2026-05-31T12:00:00+00:00',
            ],
            $event->toPayload(defaultOccurredAt: '2026-05-31T12:00:00+00:00'),
        );
    }

    public function testFormatsDateTimeOccurrenceInUtc(): void
    {
        $event = EventInput::create('customer_123', 'lesson.finished')
            ->withTransactionId('lesson:intro:user:customer_123')
            ->withOccurredAt(new DateTimeImmutable('2026-05-31T14:00:00+02:00'));

        self::assertSame(
            '2026-05-31T12:00:00+00:00',
            $event->toPayload()['occurred_at'],
        );
    }

    public function testRequiresTransactionIdForPayload(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('transaction_id');

        EventInput::create('customer_123', 'purchase.completed')->toPayload();
    }

    public function testCreatesEventFromApiShapedArray(): void
    {
        $event = EventInput::fromArray([
            'user_id' => 'customer_123',
            'event' => 'purchase.completed',
            'transaction_id' => 'order_1092',
            'context' => ['order_id' => 'order_1092'],
            'occurred_at' => '2026-05-31T12:00:00+00:00',
        ]);

        self::assertSame('order_1092', $event->transactionId);
        self::assertSame(['order_id' => 'order_1092'], $event->context->toArray());
    }
}
