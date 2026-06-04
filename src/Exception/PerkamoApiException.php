<?php

declare(strict_types=1);

namespace Perkamo\Exception;

use RuntimeException;

final class PerkamoApiException extends RuntimeException
{
    public function __construct(
        private readonly int $statusCode,
        private readonly mixed $body,
        private readonly ?string $requestId = null,
        private readonly ?string $retryAfter = null,
        private readonly array $rateLimit = [],
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

    public function requestId(): ?string
    {
        return $this->requestId;
    }

    public function retryAfter(): ?string
    {
        return $this->retryAfter;
    }

    /**
     * @return array{limit?: int|null, remaining?: int|null, reset?: string|null}
     */
    public function rateLimit(): array
    {
        return $this->rateLimit;
    }
}
