<?php

declare(strict_types=1);

use Danielgnh\PolymarketPhp\Client;
use Danielgnh\PolymarketPhp\Http\FakeGuzzleHttpClient;

beforeEach(function (): void {
    $this->fakeHttp = new FakeGuzzleHttpClient();
    $this->client = new Client(clobHttpClient: $this->fakeHttp);
});

describe('Markets::list()', function (): void {
    it('fetches market listing', function (): void {
        $marketsData = [
            'data' => [
                ['condition_id' => 'market_1', 'question' => 'Will this happen?'],
                ['condition_id' => 'market_2', 'question' => 'Will that happen?'],
            ],
            'next_cursor' => null,
        ];

        $this->fakeHttp->addJsonResponse('GET', '/markets', $marketsData);

        $result = $this->client->clob()->markets()->list();

        expect($result)->toBeArray()
            ->and($result)->toHaveKey('data');
    });
});

describe('Markets::getSimplified()', function (): void {
    it('fetches simplified market data', function (): void {
        $simplifiedData = [
            'data' => [
                ['condition_id' => 'market_1', 'question' => 'Simple question?'],
            ],
        ];

        $this->fakeHttp->addJsonResponse('GET', '/simplified-markets', $simplifiedData);

        $result = $this->client->clob()->markets()->getSimplified();

        expect($result)->toBeArray()
            ->and($result)->toHaveKey('data');
    });
});

describe('Markets::getSampling()', function (): void {
    it('fetches sampling markets', function (): void {
        $samplingData = [
            'data' => [
                ['condition_id' => 'sample_1'],
            ],
        ];

        $this->fakeHttp->addJsonResponse('GET', '/sampling-markets', $samplingData);

        $result = $this->client->clob()->markets()->getSampling();

        expect($result)->toBeArray();
    });
});

describe('Markets::getSamplingSimplified()', function (): void {
    it('fetches simplified sampling markets', function (): void {
        $simplifiedSamplingData = [
            'data' => [
                ['condition_id' => 'sample_1'],
            ],
        ];

        $this->fakeHttp->addJsonResponse('GET', '/sampling-simplified-markets', $simplifiedSamplingData);

        $result = $this->client->clob()->markets()->getSamplingSimplified();

        expect($result)->toBeArray();
    });
});

describe('Markets::get()', function (): void {
    it('fetches single market by condition ID', function (): void {
        $marketData = [
            'condition_id' => 'market_123',
            'question' => 'Will this happen?',
            'tokens' => [
                ['token_id' => 'token_1', 'outcome' => 'Yes'],
                ['token_id' => 'token_2', 'outcome' => 'No'],
            ],
        ];

        $this->fakeHttp->addJsonResponse('GET', '/market/market_123', $marketData);

        $result = $this->client->clob()->markets()->get('market_123');

        expect($result)->toBeArray()
            ->and($result['condition_id'])->toBe('market_123')
            ->and($result)->toHaveKey('tokens');
    });
});

describe('Markets::getTradeEvents()', function (): void {
    it('fetches trade events for a market', function (): void {
        $tradeEventsData = [
            ['event_id' => 'event_1', 'type' => 'trade', 'price' => '0.52'],
            ['event_id' => 'event_2', 'type' => 'trade', 'price' => '0.53'],
        ];

        $this->fakeHttp->addJsonResponse('GET', '/market-trades-events/market_123', $tradeEventsData);

        $result = $this->client->clob()->markets()->getTradeEvents('market_123');

        expect($result)->toBeArray()
            ->and($result)->toHaveCount(2)
            ->and($result[0]['type'])->toBe('trade');
    });
});
