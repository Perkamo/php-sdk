<?php

declare(strict_types=1);

namespace Perkamo;

use InvalidArgumentException;
use JsonSerializable;

final class BatchIngestResult implements JsonSerializable
{
    /**
     * @param list<EventIngestResult> $results
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public readonly array $results,
        private readonly array $raw,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $results = $payload['results'] ?? null;
        if (!is_array($results) || !array_is_list($results)) {
            throw new InvalidArgumentException("Perkamo batch ingest field 'results' is required and must be a list");
        }

        return new self(
            array_map(
                static function (mixed $result): EventIngestResult {
                    if (!is_array($result)) {
                        throw new InvalidArgumentException("Perkamo batch ingest result items must be objects");
                    }

                    return EventIngestResult::fromArray($result);
                },
                $results,
            ),
            $payload,
        );
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(): array
    {
        return $this->raw;
    }

    /**
     * @return array<string, mixed>
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
