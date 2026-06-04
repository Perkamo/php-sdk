<?php

declare(strict_types=1);

namespace Perkamo;

use InvalidArgumentException;
use JsonSerializable;

final class WalletDelta implements JsonSerializable
{
    public function __construct(
        public readonly string $wallet,
        public readonly int|float $amount,
    ) {
        if ($this->wallet === '') {
            throw new InvalidArgumentException("Perkamo wallet delta field 'wallet' is required and must be a string");
        }
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $wallet = $payload['wallet'] ?? null;
        $amount = $payload['amount'] ?? null;

        if (!is_string($wallet) || $wallet === '') {
            throw new InvalidArgumentException("Perkamo wallet delta field 'wallet' is required and must be a string");
        }
        if (!is_int($amount) && !is_float($amount)) {
            throw new InvalidArgumentException("Perkamo wallet delta field 'amount' is required and must be a number");
        }

        return new self($wallet, $amount);
    }

    /**
     * @return array{wallet: string, amount: int|float}
     */
    public function toArray(): array
    {
        return [
            'wallet' => $this->wallet,
            'amount' => $this->amount,
        ];
    }

    /**
     * @return array{wallet: string, amount: int|float}
     */
    public function jsonSerialize(): array
    {
        return $this->toArray();
    }
}
