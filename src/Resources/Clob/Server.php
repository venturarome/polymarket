<?php

declare(strict_types=1);

namespace PolymarketPhp\Polymarket\Resources\Clob;

use PolymarketPhp\Polymarket\Exceptions\PolymarketException;
use PolymarketPhp\Polymarket\Resources\Resource;

class Server extends Resource
{
    /**
     * @throws PolymarketException
     */
    public function healthCheck(): string
    {
        return $this->httpClient->get('/')->body();
    }

    /**
     * @throws PolymarketException
     */
    public function getTime(): int
    {
        return (int) $this->httpClient->get('/time')->body();
    }

    /**
     * @return array<string, mixed>
     *
     * @throws PolymarketException
     */
    public function getFeeRate(string $tokenId): array
    {
        return $this->httpClient->get('/fee-rate', ['token_id' => $tokenId])->json();
    }
}
