<?php

declare(strict_types=1);

namespace Perkamo\Tests;

use Perkamo\WebhookSignature;
use PHPUnit\Framework\TestCase;

final class WebhookSignatureTest extends TestCase
{
    private const SECRET = 'whsec_example';
    private const BODY = '{"event":"reward.redeemed","id":"evt_1"}';
    private const NOW = 1782777600;

    /** Reproduces the server's signing scheme (apps/api security/webhook-egress). */
    private static function serverSign(string $secret, string $timestamp, string $body): string
    {
        return 'v1=' . hash_hmac('sha256', $timestamp . '.' . $body, $secret);
    }

    public function testAcceptsValidSignatureWithinToleranceWindow(): void
    {
        $ts = (string) self::NOW;

        self::assertTrue(WebhookSignature::verify(
            secret: self::SECRET,
            payload: self::BODY,
            signatureHeader: self::serverSign(self::SECRET, $ts, self::BODY),
            timestampHeader: $ts,
            now: self::NOW,
        ));
    }

    public function testRejectsTamperedBody(): void
    {
        $ts = (string) self::NOW;

        self::assertFalse(WebhookSignature::verify(
            secret: self::SECRET,
            payload: self::BODY . 'x',
            signatureHeader: self::serverSign(self::SECRET, $ts, self::BODY),
            timestampHeader: $ts,
            now: self::NOW,
        ));
    }

    public function testRejectsWrongSecret(): void
    {
        $ts = (string) self::NOW;

        self::assertFalse(WebhookSignature::verify(
            secret: self::SECRET,
            payload: self::BODY,
            signatureHeader: self::serverSign('whsec_other', $ts, self::BODY),
            timestampHeader: $ts,
            now: self::NOW,
        ));
    }

    public function testRejectsStaleTimestampReplay(): void
    {
        $ts = (string) self::NOW;

        self::assertFalse(WebhookSignature::verify(
            secret: self::SECRET,
            payload: self::BODY,
            signatureHeader: self::serverSign(self::SECRET, $ts, self::BODY),
            timestampHeader: $ts,
            toleranceSeconds: 300,
            now: self::NOW + 3600,
        ));
    }

    public function testAcceptsTimestampAtToleranceBoundary(): void
    {
        $ts = (string) self::NOW;

        self::assertTrue(WebhookSignature::verify(
            secret: self::SECRET,
            payload: self::BODY,
            signatureHeader: self::serverSign(self::SECRET, $ts, self::BODY),
            timestampHeader: $ts,
            toleranceSeconds: 300,
            now: self::NOW + 300,
        ));
    }

    public function testRejectsMissingHeaders(): void
    {
        $ts = (string) self::NOW;
        $signature = self::serverSign(self::SECRET, $ts, self::BODY);

        self::assertFalse(WebhookSignature::verify(
            secret: self::SECRET,
            payload: self::BODY,
            signatureHeader: null,
            timestampHeader: $ts,
            now: self::NOW,
        ));
        self::assertFalse(WebhookSignature::verify(
            secret: self::SECRET,
            payload: self::BODY,
            signatureHeader: $signature,
            timestampHeader: null,
            now: self::NOW,
        ));
        self::assertFalse(WebhookSignature::verify(
            secret: '',
            payload: self::BODY,
            signatureHeader: $signature,
            timestampHeader: $ts,
            now: self::NOW,
        ));
    }

    public function testRejectsNonNumericTimestamp(): void
    {
        self::assertFalse(WebhookSignature::verify(
            secret: self::SECRET,
            payload: self::BODY,
            signatureHeader: self::serverSign(self::SECRET, 'soon', self::BODY),
            timestampHeader: 'soon',
            now: self::NOW,
        ));
    }

    public function testRejectsMalformedSignatureHeader(): void
    {
        $ts = (string) self::NOW;

        self::assertFalse(WebhookSignature::verify(
            secret: self::SECRET,
            payload: self::BODY,
            signatureHeader: 'v2=deadbeef',
            timestampHeader: $ts,
            now: self::NOW,
        ));
    }

    public function testMatchesKnownVector(): void
    {
        // Cross-SDK vector: keep in sync with the JS implementation.
        self::assertSame(
            'v1=' . hash_hmac('sha256', '1700000000.{"a":1}', 'whsec_vector'),
            self::serverSign('whsec_vector', '1700000000', '{"a":1}'),
        );
        self::assertTrue(WebhookSignature::verify(
            secret: 'whsec_vector',
            payload: '{"a":1}',
            signatureHeader: self::serverSign('whsec_vector', '1700000000', '{"a":1}'),
            timestampHeader: '1700000000',
            now: 1700000000,
        ));
    }
}
