<?php

declare(strict_types=1);

namespace Perkamo;

use InvalidArgumentException;
use JsonSerializable;

final class RedeemRewardResult implements JsonSerializable
{
    /**
     * @param array<string, mixed> $reward
     * @param list<WalletDelta> $delta
     * @param array<string, mixed> $effect
     * @param array<string, int|float> $walletState
     * @param array<string, mixed> $raw
     */
    public function __construct(
        public readonly bool $redeemed,
        public readonly bool $duplicate,
        public readonly array $reward,
        public readonly array $delta,
        public readonly array $effect,
        public readonly array $walletState,
        public readonly ?LevelState $level,
        private readonly array $raw,
    ) {
    }

    /**
     * @param array<string, mixed> $payload
     */
    public static function fromArray(array $payload): self
    {
        $reward = $payload['reward'] ?? [];
        if (!is_array($reward)) {
            throw new InvalidArgumentException("Perkamo redeem field 'reward' must be an object");
        }

        $effect = $payload['effect'] ?? [];
        if (!is_array($effect)) {
            throw new InvalidArgumentException("Perkamo redeem field 'effect' must be an object");
        }

        $delta = $payload['delta'] ?? [];
        if (!is_array($delta) || !array_is_list($delta)) {
            throw new InvalidArgumentException("Perkamo redeem field 'delta' must be a list");
        }

        $walletState = $payload['wallet_state'] ?? [];
        if (!is_array($walletState)) {
            throw new InvalidArgumentException("Perkamo redeem field 'wallet_state' must be an object");
        }

        $level = $payload['level'] ?? null;
        if ($level !== null && !is_array($level)) {
            throw new InvalidArgumentException("Perkamo redeem field 'level' must be an object or null");
        }

        return new self(
            (bool) ($payload['redeemed'] ?? false),
            (bool) ($payload['duplicate'] ?? false),
            $reward,
            array_map(
                static fn (mixed $item): WalletDelta => WalletDelta::fromArray(
                    is_array($item) ? $item : throw new InvalidArgumentException('Perkamo redeem delta items must be objects'),
                ),
                $delta,
            ),
            $effect,
            $walletState,
            $level === null ? null : LevelState::fromArray($level),
            $payload,
        );
    }

    /**
     * The declarative effect type: coupon, free_shipping, gift_card,
     * entitlement or custom.
     */
    public function effectType(): string
    {
        $type = $this->effect['type'] ?? '';

        return is_string($type) ? $type : '';
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
