<?php

declare(strict_types=1);

use Danielgnh\PolymarketPhp\Config;
use Danielgnh\PolymarketPhp\Http\GuzzleHttpClient;
use GuzzleHttp\Client as GuzzleClient;

describe('GuzzleHttpClient', function (): void {
    it('exposes internal Guzzle client', function (): void {
        $config = new Config();
        $client = new GuzzleHttpClient('https://example.com', $config);

        $guzzle = $client->getGuzzleClient();

        expect($guzzle)->toBeInstanceOf(GuzzleClient::class);
    });
});
