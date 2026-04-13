<?php

declare(strict_types=1);

use PolymarketPhp\Polymarket\Enums\OrderSide;
use PolymarketPhp\Polymarket\Enums\OrderType;
use PolymarketPhp\Polymarket\Enums\SignatureType;
use PolymarketPhp\Polymarket\Exceptions\PolymarketException;
use PolymarketPhp\Polymarket\Http\FakeGuzzleHttpClient;
use PolymarketPhp\Polymarket\Resources\Clob\Orders;
use PolymarketPhp\Polymarket\Signing\Eip712Signer;

// Hardhat account #0 — a public test vector, safe to commit.
const ORDERS_POST_TEST_KEY = '0xac0974bec39a17e36ba4a6b4d238ff944bacb478cbed5efcae784d7bf4f2ff80';
const ORDERS_POST_TEST_ADDRESS = '0xf39Fd6e51aad88F6F4ce6aB8827279cffFb92266';

/**
 * Build a complete, valid post() input array.
 *
 * @return array<string, mixed>
 */
function buildValidPostInput(OrderSide $side = OrderSide::BUY): array
{
    return [
        'order' => [
            'maker'         => ORDERS_POST_TEST_ADDRESS,
            'signer'        => ORDERS_POST_TEST_ADDRESS,
            'taker'         => '0x0000000000000000000000000000000000000000',
            'tokenId'       => '71321045679252212594626385532706912750332728571942532289631379312455583992563',
            'makerAmount'   => '100000',
            'takerAmount'   => '100000',
            'expiration'    => 0,
            'nonce'         => 0,
            'feeRateBps'    => 0,
            'side'          => $side,
            'signatureType' => SignatureType::EOA->value,
            'salt'          => 12_345,
        ],
        'owner'     => ORDERS_POST_TEST_ADDRESS,
        'orderType' => OrderType::GTC->value,
        'deferExec' => false,
    ];
}

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

function makeOrders(?Eip712Signer $signer = null): array
{
    $fakeHttp = new FakeGuzzleHttpClient();
    $orders = new Orders($signer, $fakeHttp);

    return [$orders, $fakeHttp];
}

// ---------------------------------------------------------------------------

describe('Orders::post() – authentication guard', function (): void {
    it('throws PolymarketException when no signer is configured', function (): void {
        [$orders] = makeOrders(null);

        expect(fn () => $orders->post(buildValidPostInput()))
            ->toThrow(PolymarketException::class, 'authentication');
    });
});

describe('Orders::post() – HTTP behaviour', function (): void {
    it('sends a POST request to the /order endpoint', function (): void {
        [$orders, $fakeHttp] = makeOrders(new Eip712Signer(ORDERS_POST_TEST_KEY, 137));
        $fakeHttp->addJsonResponse('POST', '/order', ['success' => true, 'orderID' => 'abc123']);

        $result = $orders->post(buildValidPostInput());

        expect($result['success'])->toBeTrue()
            ->and($fakeHttp->hasRequest('POST', '/order'))->toBeTrue();
    });

    it('returns the decoded JSON response from the CLOB API', function (): void {
        [$orders, $fakeHttp] = makeOrders(new Eip712Signer(ORDERS_POST_TEST_KEY, 137));
        $fakeHttp->addJsonResponse('POST', '/order', ['orderID' => 'xyz-999', 'status' => 'pending']);

        $result = $orders->post(buildValidPostInput());

        expect($result)->toBe(['orderID' => 'xyz-999', 'status' => 'pending']);
    });
});

describe('Orders::post() – signature', function (): void {
    it('adds a 0x-prefixed 65-byte signature to the order sub-array', function (): void {
        [$orders, $fakeHttp] = makeOrders(new Eip712Signer(ORDERS_POST_TEST_KEY, 137));
        $fakeHttp->addJsonResponse('POST', '/order', ['success' => true]);

        $orders->post(buildValidPostInput());

        $request = $fakeHttp->getRequest('POST', '/order');

        expect($request)->not->toBeNull()
            ->and($request['data']['order'])->toHaveKey('signature')
            ->and($request['data']['order']['signature'])->toStartWith('0x')
            ->and(strlen((string) $request['data']['order']['signature']))->toBe(132);
    });

    it('produces the same signature for identical inputs (secp256k1 RFC 6979)', function (): void {
        $signer = new Eip712Signer(ORDERS_POST_TEST_KEY, 137);

        [$orders1, $fakeHttp1] = makeOrders($signer);
        $fakeHttp1->addJsonResponse('POST', '/order', ['success' => true]);
        $orders1->post(buildValidPostInput());
        $sig1 = $fakeHttp1->getRequest('POST', '/order')['data']['order']['signature'];

        [$orders2, $fakeHttp2] = makeOrders($signer);
        $fakeHttp2->addJsonResponse('POST', '/order', ['success' => true]);
        $orders2->post(buildValidPostInput());
        $sig2 = $fakeHttp2->getRequest('POST', '/order')['data']['order']['signature'];

        expect($sig1)->toBe($sig2);
    });
});

describe('Orders::post() – payload shape sent to API', function (): void {
    it('converts the OrderSide enum to its string value for the API', function (): void {
        [$orders, $fakeHttp] = makeOrders(new Eip712Signer(ORDERS_POST_TEST_KEY, 137));
        $fakeHttp->addJsonResponse('POST', '/order', ['success' => true]);

        $orders->post(buildValidPostInput(OrderSide::BUY));
        $order = $fakeHttp->getRequest('POST', '/order')['data']['order'];

        expect($order['side'])->toBe('BUY');
    });

    it('converts the SELL side enum to its string value', function (): void {
        [$orders, $fakeHttp] = makeOrders(new Eip712Signer(ORDERS_POST_TEST_KEY, 137));
        $fakeHttp->addJsonResponse('POST', '/order', ['success' => true]);

        $orders->post(buildValidPostInput(OrderSide::SELL));
        $order = $fakeHttp->getRequest('POST', '/order')['data']['order'];

        expect($order['side'])->toBe('SELL');
    });

    it('normalises all numeric order fields to strings for the CLOB API', function (): void {
        [$orders, $fakeHttp] = makeOrders(new Eip712Signer(ORDERS_POST_TEST_KEY, 137));
        $fakeHttp->addJsonResponse('POST', '/order', ['success' => true]);

        $orders->post(buildValidPostInput());
        $order = $fakeHttp->getRequest('POST', '/order')['data']['order'];

        expect($order['salt'])->toBeString()
            ->and($order['tokenId'])->toBeString()
            ->and($order['makerAmount'])->toBeString()
            ->and($order['takerAmount'])->toBeString()
            ->and($order['expiration'])->toBeString()
            ->and($order['nonce'])->toBeString()
            ->and($order['feeRateBps'])->toBeString();
    });

    it('forwards owner, orderType, and deferExec to the top-level payload', function (): void {
        [$orders, $fakeHttp] = makeOrders(new Eip712Signer(ORDERS_POST_TEST_KEY, 137));
        $fakeHttp->addJsonResponse('POST', '/order', ['success' => true]);

        $orders->post(buildValidPostInput());
        $payload = $fakeHttp->getRequest('POST', '/order')['data'];

        expect($payload['owner'])->toBe(ORDERS_POST_TEST_ADDRESS)
            ->and($payload['orderType'])->toBe(OrderType::GTC->value)
            ->and($payload['deferExec'])->toBeFalse();
    });

    it('defaults deferExec to false when omitted', function (): void {
        [$orders, $fakeHttp] = makeOrders(new Eip712Signer(ORDERS_POST_TEST_KEY, 137));
        $fakeHttp->addJsonResponse('POST', '/order', ['success' => true]);

        $input = buildValidPostInput();
        unset($input['deferExec']);
        $orders->post($input);

        $payload = $fakeHttp->getRequest('POST', '/order')['data'];

        expect($payload['deferExec'])->toBeFalse();
    });
});
