<?php

declare(strict_types=1);

namespace PolymarketPhp\Polymarket\Signing\TypedData;

/**
 * EIP-712 payload for Polymarket CTF Exchange order signing.
 *
 * The verifying contract is auto-derived from the chain ID unless overridden
 * explicitly, so callers only need to pass the chain ID.
 */
class OrderPayload implements TypedDataInterface
{
    public const MAINNET_CHAIN_ID = 137;

    public const TESTNET_CHAIN_ID = 80002;

    public const CTF_EXCHANGE_MAINNET = '0x4bFb41d5B3570DeFd03C39a9A4D8dE6Bd8B8982E';

    public const CTF_EXCHANGE_TESTNET = '0xdFE02Eb6733538f8Ea35D585af8DE5958AD99E40';

    private readonly string $verifyingContract;

    /**
     * @param array<string, mixed> $orderData
     * @param int                  $chainId           Chain ID – drives contract auto-selection
     * @param string|null          $verifyingContract Explicit override; derived from chainId when null
     */
    public function __construct(
        private readonly array $orderData,
        private readonly int $chainId = self::MAINNET_CHAIN_ID,
        ?string $verifyingContract = null,
    ) {
        $this->verifyingContract = $verifyingContract ?? match ($this->chainId) {
            self::TESTNET_CHAIN_ID => self::CTF_EXCHANGE_TESTNET,
            default => self::CTF_EXCHANGE_MAINNET,
        };
    }

    public function getDomainTypes(): array
    {
        return [
            ['name' => 'name',              'type' => 'string'],
            ['name' => 'version',           'type' => 'string'],
            ['name' => 'chainId',           'type' => 'uint256'],
            ['name' => 'verifyingContract', 'type' => 'address'],
        ];
    }

    public function getDomain(): array
    {
        return [
            'name'              => 'Polymarket CTF Exchange',
            'version'           => '1',
            'chainId'           => $this->chainId,
            'verifyingContract' => $this->verifyingContract,
        ];
    }

    public function getPrimaryType(): string
    {
        return 'Order';
    }

    public function getTypes(): array
    {
        return [
            'Order' => [
                ['name' => 'salt',          'type' => 'uint256'],
                ['name' => 'maker',         'type' => 'address'],
                ['name' => 'signer',        'type' => 'address'],
                ['name' => 'taker',         'type' => 'address'],
                ['name' => 'tokenId',       'type' => 'uint256'],
                ['name' => 'makerAmount',   'type' => 'uint256'],
                ['name' => 'takerAmount',   'type' => 'uint256'],
                ['name' => 'expiration',    'type' => 'uint256'],
                ['name' => 'nonce',         'type' => 'uint256'],
                ['name' => 'feeRateBps',    'type' => 'uint256'],
                ['name' => 'side',          'type' => 'uint8'],
                ['name' => 'signatureType', 'type' => 'uint8'],
            ],
        ];
    }

    public function getMessage(): array
    {
        return $this->orderData;
    }
}
