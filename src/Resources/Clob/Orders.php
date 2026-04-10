<?php

declare(strict_types=1);

namespace PolymarketPhp\Polymarket\Resources\Clob;

use GuzzleHttp\Promise\PromiseInterface;
use PolymarketPhp\Polymarket\Config;
use PolymarketPhp\Polymarket\Enums\OrderSide;
use PolymarketPhp\Polymarket\Exceptions\PolymarketException;
use PolymarketPhp\Polymarket\Http\AsyncClientInterface;
use PolymarketPhp\Polymarket\Http\BatchResult;
use PolymarketPhp\Polymarket\Http\HttpClientInterface;
use PolymarketPhp\Polymarket\Http\Response;
use PolymarketPhp\Polymarket\Resources\Resource;
use PolymarketPhp\Polymarket\Resources\Traits\HasAsyncClient;
use PolymarketPhp\Polymarket\Signing\Eip712Signer;
use PolymarketPhp\Polymarket\Signing\TypedData\OrderPayload;

class Orders extends Resource
{
    use HasAsyncClient;

    public function __construct(
        private Config $config,
        HttpClientInterface $httpClient,
        ?AsyncClientInterface $asyncClient = null,
    ) {
        parent::__construct($httpClient, $asyncClient);
    }

    /**
     * @param array<string, mixed> $filters
     *
     * @return array<string, mixed>
     *
     * @throws PolymarketException
     */
    public function list(array $filters = [], int $limit = 100, int $offset = 0): array
    {
        $params = array_merge($filters, [
            'limit' => $limit,
            'offset' => $offset,
        ]);

        return $this->httpClient->get('/data/orders', $params)->json();
    }

    /**
     * @return array<string, mixed>
     *
     * @throws PolymarketException
     */
    public function get(string $orderId): array
    {
        return $this->httpClient->get("/data/order/{$orderId}")->json();
    }

    /**
     * @param array<string, mixed> $params
     *
     * @return array<string, mixed>
     *
     * @throws PolymarketException
     */
    public function getOpen(array $params = []): array
    {
        return $this->httpClient->get('/open-orders', $params)->json();
    }

    /**
     * @param array<string, mixed> $orderData
     *
     * @return array<string, mixed>
     *
     * @throws PolymarketException
     */
    public function create(array $orderData): array
    {
        return $this->httpClient->post('/orders', $orderData)->json();
    }

    /**
     * @param array<string, mixed> $orderData
     *
     * @return array<string, mixed>
     *
     * @throws PolymarketException
     */
    public function post(array $inputOrderData): array
    {
        $orderData = $inputOrderData['order'];

        /** @var OrderSide $side */
        $side = $orderData['side'];

        // Adapt some fields to adhere to Ethereum data types
        $orderData['side'] = $side->forSignature();

        $key = $this->config->privateKey ?? throw new PolymarketException('Private key not set');
        $signer = new Eip712Signer($key, $this->config->chainId);
        $signature = $signer->sign(new OrderPayload($orderData));

        $orderData['signature'] = $signature;

        // Adapt some fields to adhere to Polymarket data types
        $orderData['side'] = $side->forApi();
        $orderData['feeRateBps'] = (string) $orderData['feeRateBps'];
        $orderData['expiration'] = (string) $orderData['expiration'];
        $orderData['nonce'] = (string) $orderData['nonce'];

        $finalPayload = [
            'order' => $orderData,
            'owner' => $inputOrderData['owner'],
            'orderType' =>$inputOrderData['orderType'],
            'deferExec' => $inputOrderData['deferExec'] ?? false,
        ];

        return $this->httpClient->post('/order', $finalPayload)->json();

    }

    /**
     * @param array<int, array<string, mixed>> $orders
     *
     * @return array<string, mixed>
     *
     * @throws PolymarketException
     */
    public function postMultiple(array $orders): array
    {
        return $this->httpClient->post('/orders', $orders)->json();
    }

    /**
     * @param string|array<string, mixed> $orderIdOrPayload
     *
     * @return array<string, mixed>
     *
     * @throws PolymarketException
     */
    public function cancel(string|array $orderIdOrPayload): array
    {
        if (is_string($orderIdOrPayload)) {
            return $this->httpClient->delete("/orders/{$orderIdOrPayload}")->json();
        }

        return $this->httpClient->delete('/order', $orderIdOrPayload)->json();
    }

    /**
     * @param array<int, string> $orderIds
     *
     * @return array<string, mixed>
     *
     * @throws PolymarketException
     */
    public function cancelMultiple(array $orderIds): array
    {
        return $this->httpClient->delete('/orders', ['ids' => $orderIds])->json();
    }

    /**
     * @return array<string, mixed>
     *
     * @throws PolymarketException
     */
    public function cancelAll(): array
    {
        return $this->httpClient->delete('/cancel-all')->json();
    }

    /**
     * @param array<string, mixed> $payload
     *
     * @return array<string, mixed>
     *
     * @throws PolymarketException
     */
    public function cancelMarketOrders(array $payload): array
    {
        return $this->httpClient->delete('/cancel-market-orders', $payload)->json();
    }

    /**
     * @param array<string, mixed> $filters
     */
    public function listAsync(array $filters = [], int $limit = 100, int $offset = 0): PromiseInterface
    {
        $params = array_merge($filters, [
            'limit' => $limit,
            'offset' => $offset,
        ]);

        return $this->getAsyncClient()->getAsync('/data/orders', $params)
            ->then(fn (Response $response): array => $response->json());
    }

    public function getAsync(string $orderId): PromiseInterface
    {
        return $this->getAsyncClient()->getAsync("/data/order/{$orderId}")
            ->then(fn (Response $response): array => $response->json());
    }

    /**
     * @param array<string, mixed> $params
     */
    public function getOpenAsync(array $params = []): PromiseInterface
    {
        return $this->getAsyncClient()->getAsync('/open-orders', $params)
            ->then(fn (Response $response): array => $response->json());
    }

    /**
     * @param array<string> $orderIds
     */
    public function getMany(array $orderIds, int $concurrency = 10): BatchResult
    {
        $promises = [];
        foreach ($orderIds as $id) {
            $promises[$id] = $this->getAsync($id);
        }

        return $this->getAsyncClient()->pool($promises, $concurrency);
    }

    /**
     * @param array<string> $orderIds
     */
    public function cancelMany(array $orderIds, int $concurrency = 5): BatchResult
    {
        $promises = [];
        foreach ($orderIds as $id) {
            $promises[$id] = $this->cancelAsync($id);
        }

        return $this->getAsyncClient()->pool($promises, $concurrency);
    }

    private function cancelAsync(string $orderId): PromiseInterface
    {
        return $this->getAsyncClient()->deleteAsync("/orders/{$orderId}")
            ->then(fn (Response $response): array => $response->json());
    }
}
