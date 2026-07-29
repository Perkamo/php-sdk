<?php

declare(strict_types=1);

namespace Perkamo;

/**
 * Verifies inbound Perkamo webhooks.
 *
 * Recomputes HMAC-SHA256 over "{timestamp}.{body}" with the endpoint signing
 * secret, compares it in constant time to the `x-perkamo-signature` header,
 * and rejects timestamps outside the tolerance window (replay protection).
 * Returns false rather than throwing on any malformed or missing input.
 *
 * Mirrors `verifyWebhookSignature` from the @perkamo/sdk JS package.
 */
final class WebhookSignature
{
    private const VERSION = 'v1';
    private const DEFAULT_TOLERANCE_SECONDS = 300;

    /**
     * @param string      $secret           Endpoint signing secret revealed once at endpoint creation.
     * @param string      $payload          Raw request body exactly as received (do not re-serialize).
     * @param string|null $signatureHeader  Value of the `x-perkamo-signature` header.
     * @param string|null $timestampHeader  Value of the `x-perkamo-timestamp` header.
     * @param int         $toleranceSeconds Max allowed clock difference in seconds (default 300).
     * @param int|null    $now              Override the current unix time (testing).
     */
    public static function verify(
        string $secret,
        string $payload,
        ?string $signatureHeader,
        ?string $timestampHeader,
        int $toleranceSeconds = self::DEFAULT_TOLERANCE_SECONDS,
        ?int $now = null,
    ): bool {
        if ($secret === '' || $signatureHeader === null || $signatureHeader === ''
            || $timestampHeader === null || $timestampHeader === '') {
            return false;
        }

        if (preg_match('/^-?\d+$/', $timestampHeader) !== 1) {
            return false;
        }
        $timestamp = (int) $timestampHeader;

        $nowSeconds = $now ?? time();
        if (abs($nowSeconds - $timestamp) > $toleranceSeconds) {
            return false;
        }

        $expected = self::VERSION . '=' . hash_hmac(
            'sha256',
            $timestampHeader . '.' . $payload,
            $secret,
        );

        return hash_equals($expected, $signatureHeader);
    }
}
