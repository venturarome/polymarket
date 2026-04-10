<?php

declare(strict_types=1);

namespace PolymarketPhp\Polymarket\Enums;

/**
 * Order side - whether buying or selling shares.
 */
enum OrderSide: string
{
    case BUY = 'BUY';
    case SELL = 'SELL';

    public function forSignature(): int
    {
        return match ($this) {
            self::BUY => 0,
            self::SELL => 1,
        };
    }

    public function forApi(): string
    {
        return $this->value;
    }
}
