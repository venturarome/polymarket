<?php

declare(strict_types=1);

use Danielgnh\PolymarketPhp\Client;
use Danielgnh\PolymarketPhp\Http\FakeGuzzleHttpClient;

beforeEach(function (): void {
    $this->fakeHttp = new FakeGuzzleHttpClient();
    $this->client = new Client(clobHttpClient: $this->fakeHttp);
});

describe('Rewards::getCurrentRewards()', function (): void {
    it('fetches active reward markets', function (): void {
        $rewardsData = [
            ['market_id' => 'market_1', 'reward_rate' => '0.05'],
            ['market_id' => 'market_2', 'reward_rate' => '0.03'],
        ];

        $this->fakeHttp->addJsonResponse('GET', '/rewards-markets-current', $rewardsData);

        $result = $this->client->clob()->rewards()->getCurrentRewards();

        expect($result)->toBeArray()
            ->and($result)->toHaveCount(2)
            ->and($result[0])->toHaveKey('reward_rate');
    });
});

describe('Rewards::getForMarket()', function (): void {
    it('fetches rewards for specific market', function (): void {
        $marketRewardsData = [
            ['condition_id' => 'market_123', 'reward_rate' => '0.04', 'total_rewards' => '1000.00'],
        ];

        $this->fakeHttp->addJsonResponse('GET', '/rewards-markets/market_123', $marketRewardsData);

        $result = $this->client->clob()->rewards()->getForMarket('market_123');

        expect($result)->toBeArray()
            ->and($result[0]['condition_id'])->toBe('market_123');
    });
});

describe('Rewards::getEarningsForDay()', function (): void {
    it('fetches daily earnings breakdown', function (): void {
        $earningsData = [
            ['market_id' => 'market_1', 'earnings' => '10.50'],
            ['market_id' => 'market_2', 'earnings' => '5.25'],
        ];

        $this->fakeHttp->addJsonResponse('GET', '/earnings-for-user-for-day', $earningsData);

        $result = $this->client->clob()->rewards()->getEarningsForDay(['date' => '2025-01-15']);

        expect($result)->toBeArray()
            ->and($result)->toHaveCount(2);
    });
});

describe('Rewards::getTotalEarningsForDay()', function (): void {
    it('fetches total daily earnings', function (): void {
        $totalEarningsData = [
            ['date' => '2025-01-15', 'total_earnings' => '15.75'],
        ];

        $this->fakeHttp->addJsonResponse('GET', '/total-earnings-for-user-for-day', $totalEarningsData);

        $result = $this->client->clob()->rewards()->getTotalEarningsForDay(['date' => '2025-01-15']);

        expect($result)->toBeArray()
            ->and($result[0]['total_earnings'])->toBe('15.75');
    });
});

describe('Rewards::getUserEarningsAndMarketsConfig()', function (): void {
    it('fetches earnings with market configuration', function (): void {
        $configData = [
            [
                'market_id' => 'market_1',
                'earnings' => '10.00',
                'percentage' => '0.65',
            ],
        ];

        $this->fakeHttp->addJsonResponse('GET', '/rewards-earnings-percentages', $configData);

        $result = $this->client->clob()->rewards()->getUserEarningsAndMarketsConfig(['date' => '2025-01-15']);

        expect($result)->toBeArray()
            ->and($result[0])->toHaveKey('percentage');
    });
});

describe('Rewards::getRewardPercentages()', function (): void {
    it('fetches reward percentage rates', function (): void {
        $percentagesData = [
            'base_rate' => '0.02',
            'bonus_rate' => '0.01',
        ];

        $this->fakeHttp->addJsonResponse('GET', '/liquidity-reward-percentages', $percentagesData);

        $result = $this->client->clob()->rewards()->getRewardPercentages();

        expect($result)->toBeArray()
            ->and($result)->toHaveKey('base_rate')
            ->and($result['base_rate'])->toBe('0.02');
    });
});
