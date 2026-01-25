<?php

declare(strict_types=1);

use Danielgnh\PolymarketPhp\Client;
use Danielgnh\PolymarketPhp\Http\FakeGuzzleHttpClient;

beforeEach(function (): void {
    $this->fakeHttp = new FakeGuzzleHttpClient();
    $this->client = new Client(gammaHttpClient: $this->fakeHttp, clobHttpClient: $this->fakeHttp);
});

describe('Health::check()', function (): void {
    it('performs health check', function (): void {
        $healthData = [
            'status' => 'ok',
            'timestamp' => '2025-01-15T12:00:00Z',
        ];

        $this->fakeHttp->addJsonResponse('GET', '/', $healthData);

        $result = $this->client->gamma()->health()->check();

        expect($result)->toBeArray()
            ->and($result['status'])->toBe('ok')
            ->and($result)->toHaveKey('timestamp');
    });

    it('handles unhealthy status', function (): void {
        $healthData = [
            'status' => 'degraded',
            'timestamp' => '2025-01-15T12:00:00Z',
            'message' => 'Database connection slow',
        ];

        $this->fakeHttp->addJsonResponse('GET', '/', $healthData);

        $result = $this->client->gamma()->health()->check();

        expect($result)->toBeArray()
            ->and($result['status'])->toBe('degraded')
            ->and($result)->toHaveKey('message');
    });
});
