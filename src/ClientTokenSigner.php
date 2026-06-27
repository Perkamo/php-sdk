<?php

declare(strict_types=1);

namespace Perkamo;

use JsonException;

/**
 * Self-signs Perkamo client tokens (HS256) on the backend. The frontend sends
 * the token as `Authorization: Bearer <token>` to /v1/client/* endpoints. Never
 * expose the signing secret to the browser.
 */
final class ClientTokenSigner
{
    private const TYP = 'perkamo.client+jwt';
    private const ISSUER = 'perkamo';
    private const AUDIENCE = 'perkamo.browser';

    /**
     * @param list<string>      $scope
     * @param list<string>|null $events
     *
     * @throws JsonException
     */
    public static function sign(
        string $kid,
        string $secret,
        string $subject,
        array $scope,
        ?array $events = null,
        int $ttlSeconds = 600,
        ?int $now = null,
    ): string {
        $issuedAt = $now ?? time();
        $header = ['alg' => 'HS256', 'typ' => self::TYP, 'kid' => $kid];
        $payload = [
            'iss' => self::ISSUER,
            'aud' => self::AUDIENCE,
            'sub' => $subject,
            'scope' => array_values($scope),
            'iat' => $issuedAt,
            'nbf' => $issuedAt,
            'exp' => $issuedAt + $ttlSeconds,
            'jti' => 'pct_' . bin2hex(random_bytes(16)),
        ];
        if ($events !== null) {
            $payload['events'] = array_values($events);
        }

        $signingInput = self::base64Url(json_encode($header, JSON_THROW_ON_ERROR))
            . '.' . self::base64Url(json_encode($payload, JSON_THROW_ON_ERROR));
        $signature = hash_hmac('sha256', $signingInput, $secret, true);

        return $signingInput . '.' . self::base64Url($signature);
    }

    private static function base64Url(string $value): string
    {
        return rtrim(strtr(base64_encode($value), '+/', '-_'), '=');
    }
}
