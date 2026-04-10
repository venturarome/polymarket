<?php

declare(strict_types=1);

namespace PolymarketPhp\Polymarket\Signing;

use Exception;
use InvalidArgumentException;
use kornrunner\Ethereum\Address;
use kornrunner\Keccak;
use kornrunner\Secp256k1;
use kornrunner\Signature\Signature;
use PolymarketPhp\Polymarket\Exceptions\ClobAuthenticationException;
use PolymarketPhp\Polymarket\Exceptions\SigningException;
use PolymarketPhp\Polymarket\Signing\TypedData\TypedDataInterface;

/**
 * EIP-712 signer for CLOB authentication.
 */
class Eip712Signer
{
    private readonly string $privateKey;

    private Address $ethAddress;

    public function getAddress(): string
    {
        return '0x' . $this->ethAddress->get();
    }

    /**
     * @throws SigningException
     * @throws ClobAuthenticationException
     */
    public function __construct(string $privateKey, private readonly int $chainId = 137)
    {
        $this->privateKey = $this->normalizePrivateKey($privateKey);

        try {
            $this->ethAddress = new Address(substr($this->privateKey, 2));
        } catch (InvalidArgumentException $exception) {
            throw SigningException::eip712Failed("Failed to derive address: {$exception->getMessage()}");
        }
    }

    public function sign(TypedDataInterface $payload): string
    {
        $hash = $this->hashTypedData($payload);

        return $this->signHash($hash);
    }

    /**
     * Validate and normalize private key format (add 0x prefix if missing).
     *
     * @throws ClobAuthenticationException
     */
    private function normalizePrivateKey(string $key): string
    {
        $key = str_starts_with($key, '0x') ? substr($key, 2) : $key;

        if (!ctype_xdigit($key)) {
            throw ClobAuthenticationException::invalidPrivateKey(
                'Must be a hexadecimal string'
            );
        }

        if (strlen($key) !== 64) {
            throw ClobAuthenticationException::invalidPrivateKey(
                'Must be 32 bytes (64 hex characters)'
            );
        }

        return '0x' . strtolower($key);
    }

    /**
     * Hash typed data according to EIP-712.
     *
     * @param TypedDataInterface $payload
     * @return string
     * @throws Exception
     */
    private function hashTypedData(TypedDataInterface $payload): string
    {
        // EIP-712 prefix
        $prefix = "\x19\x01";

        $domainHash = $this->hashStruct(
            'EIP712Domain',
            $payload->getDomainTypes(),
            $payload->getDomain()
        );

        $types = $payload->getTypes();
        $primaryType = $payload->getPrimaryType();

        $messageHash = $this->hashStruct(
            $primaryType,
            $types[$primaryType],
            $payload->getMessage()
        );

        $encoded = $prefix . hex2bin(substr($domainHash, 2)) . hex2bin(substr($messageHash, 2));

        return '0x' . Keccak::hash($encoded, 256);
    }

    /**
     * Hash a struct according to EIP-712.
     *
     * @param array<array{name: string, type: string}> $types
     * @param array<string, mixed>                     $data
     *
     *
     * @throws Exception
     */
    private function hashStruct(string $typeName, array $types, array $data): string
    {
        $typeHash = $this->hashType($typeName, $types);

        $encodedValues = '';
        foreach ($types as $type) {
            $fieldName = $type['name'];
            if (!array_key_exists($fieldName, $data)) {
                throw new InvalidArgumentException("Missing required field '$fieldName' in $typeName data structure.");
            }
            $value = $data[$fieldName];
            $encodedValues .= $this->encodeValue($type['type'], $value);
        }

        // Concatenate typeHash with encoded values and hash
        $encoded = hex2bin(substr($typeHash, 2)) . hex2bin($encodedValues);

        return '0x' . Keccak::hash($encoded, 256);
    }

    /**
     * Hash a type string according to EIP-712.
     *
     * @param  array<array{name: string, type: string}>  $types
     *
     * @throws Exception
     */
    private function hashType(string $typeName, array $types): string
    {
        $typeString = $typeName . '(';
        $parts = [];
        foreach ($types as $type) {
            $parts[] = $type['type'] . ' ' . $type['name'];
        }
        $typeString .= implode(',', $parts) . ')';

        return '0x' . Keccak::hash($typeString, 256);
    }

    /**
     * Encode a value according to EIP-712 encoding rules.
     */
    private function encodeValue(string $type, mixed $value): string
    {
        if ($type === 'string' || $type === 'bytes') {
            if (!is_string($value)) {
                throw new InvalidArgumentException("Expected string, got " . get_debug_type($value) . ". Value: " . $value);
            }

            return Keccak::hash($value, 256);
        }

        if ($type === 'address') {
            if (!is_string($value)) {
                throw new InvalidArgumentException("Expected string, got " . get_debug_type($value));
            }
            $address = strtolower(str_replace('0x', '', $value));

            return str_pad($address, 64, '0', STR_PAD_LEFT);
        }

        if ($type === 'uint256' || $type === 'uint8') {
            if (!is_int($value) && !ctype_digit($value)) {
                throw new InvalidArgumentException("Expected uint256/uint8, got " . get_debug_type($value) . ". Value: " . $value);
            }

            $hex = gmp_strval(gmp_init($value), 16);

            return str_pad($hex, 64, '0', STR_PAD_LEFT);
        }

        if ($type === 'bool') {
            return str_pad($value ? '1' : '0', 64, '0', STR_PAD_LEFT);
        }

        if (preg_match('/^bytes(\d+)$/', $type, $matches)) {
            $size = (int) $matches[1];
            if ($size < 1 || $size > 32) {
                throw new InvalidArgumentException("Invalid bytes size: $type");
            }

            $hex = str_replace('0x', '', (string) $value);
            // Important: Fixed bytes are padded on the RIGHT
            return str_pad($hex, 64, '0', STR_PAD_RIGHT);
        }

        throw new InvalidArgumentException("Unsupported type: $type");
    }

    /**
     * @param  string  $hash  Hash to sign (with 0x prefix)
     */
    private function signHash(string $hash): string
    {
        $secp256k1 = new Secp256k1();
        $privateKeyHex = substr($this->privateKey, 2);
        $hashHex = substr($hash, 2);

        /** @var Signature $signature */
        $signature = $secp256k1->sign($hashHex, $privateKeyHex);

        // Ethereum signature (r + s + v)
        $r = str_pad(gmp_strval($signature->getR(), 16), 64, '0', STR_PAD_LEFT);
        $s = str_pad(gmp_strval($signature->getS(), 16), 64, '0', STR_PAD_LEFT);
        $v = 27 + $signature->getRecoveryParam();

        return '0x' . $r . $s . dechex($v);
    }
}
