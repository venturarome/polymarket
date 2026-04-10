<?php

namespace PolymarketPhp\Polymarket\Signing\TypedData;

interface TypedDataInterface
{
    /**
     * @return array{name: string, version: string, chainId: int, verifyingContract?: string, salt?: string}
     */
    public function getDomain(): array;

    /**
     * @return array{array{name: string, type: string}}
     */
    public function getDomainTypes(): array;

    /**
     * @return array<string, array<array{name: string, type: string}>>
     */
    public function getTypes(): array;

    /**
     * @return array<string, mixed>
     */
    public function getMessage(): array;

    public function getPrimaryType(): string;
}