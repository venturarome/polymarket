<?php

declare(strict_types=1);

use Danielgnh\PolymarketPhp\Client;
use Danielgnh\PolymarketPhp\Http\FakeGuzzleHttpClient;

beforeEach(function (): void {
    $this->fakeHttp = new FakeGuzzleHttpClient();
    $this->client = new Client(clobHttpClient: $this->fakeHttp);
});

describe('Book::get()', function (): void {
    it('fetches order book for a token', function (): void {
        $bookData = [
            'bids' => [
                ['price' => '0.52', 'size' => '100.00'],
                ['price' => '0.51', 'size' => '200.00'],
            ],
            'asks' => [
                ['price' => '0.53', 'size' => '150.00'],
                ['price' => '0.54', 'size' => '250.00'],
            ],
        ];

        $this->fakeHttp->addJsonResponse('GET', '/book', $bookData);

        $result = $this->client->clob()->book()->get('token_123');

        expect($result)->toBeArray()
            ->and($result)->toHaveKey('bids')
            ->and($result)->toHaveKey('asks')
            ->and($result['bids'])->toHaveCount(2)
            ->and($result['asks'])->toHaveCount(2);
    });

    it('preserves decimal precision in prices and sizes', function (): void {
        $bookData = [
            'bids' => [['price' => '0.123456789', 'size' => '1000.123456']],
            'asks' => [['price' => '0.987654321', 'size' => '2000.987654']],
        ];

        $this->fakeHttp->addJsonResponse('GET', '/book', $bookData);

        $result = $this->client->clob()->book()->get('token_123');

        expect($result['bids'][0]['price'])->toBe('0.123456789')
            ->and($result['bids'][0]['size'])->toBe('1000.123456')
            ->and($result['asks'][0]['price'])->toBe('0.987654321')
            ->and($result['asks'][0]['size'])->toBe('2000.987654');
    });
});

describe('Book::getMultiple()', function (): void {
    it('fetches multiple order books', function (): void {
        $booksData = [
            ['token_id' => 'token_1', 'bids' => [], 'asks' => []],
            ['token_id' => 'token_2', 'bids' => [], 'asks' => []],
        ];

        $this->fakeHttp->addJsonResponse('POST', '/books', $booksData);

        $result = $this->client->clob()->book()->getMultiple([
            ['token_id' => 'token_1'],
            ['token_id' => 'token_2'],
        ]);

        expect($result)->toBeArray()
            ->and($result)->toHaveCount(2);
    });
});

describe('Book::getTickSize()', function (): void {
    it('fetches tick size for a token', function (): void {
        $tickData = ['tick_size' => '0.01'];

        $this->fakeHttp->addJsonResponse('GET', '/tick-size', $tickData);

        $result = $this->client->clob()->book()->getTickSize('token_123');

        expect($result)->toBeArray()
            ->and($result)->toHaveKey('tick_size')
            ->and($result['tick_size'])->toBe('0.01');
    });
});

describe('Book::getNegRisk()', function (): void {
    it('checks negative risk status for a token', function (): void {
        $negRiskData = ['neg_risk' => true];

        $this->fakeHttp->addJsonResponse('GET', '/neg-risk', $negRiskData);

        $result = $this->client->clob()->book()->getNegRisk('token_123');

        expect($result)->toBeArray()
            ->and($result)->toHaveKey('neg_risk')
            ->and($result['neg_risk'])->toBeTrue();
    });
});
