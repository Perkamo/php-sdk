<?php

declare(strict_types=1);

namespace Perkamo\Exception;

use RuntimeException;

final class PerkamoApiException extends RuntimeException
{
    public function __construct(
        private readonly int $statusCode,
        private readonly mixed $body,
    ) {
        $message = is_array($body) && isset($body['message'])
            ? (string) $body['message']
            : 'HTTP ' . $statusCode;

        parent::__construct('Perkamo API error: ' . $message, $statusCode);
    }

    public function statusCode(): int
    {
        return $this->statusCode;
    }

    public function body(): mixed
    {
        return $this->body;
    }
}
