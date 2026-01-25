<?php

declare(strict_types=1);

use Danielgnh\PolymarketPhp\Exceptions\NotFoundException;
use Danielgnh\PolymarketPhp\Http\FakeGuzzleHttpClient;
use Danielgnh\PolymarketPhp\Http\Response;
use GuzzleHttp\Promise\PromiseInterface;

describe('FakeGuzzleHttpClient async methods', function (): void {
    it('getAsync returns fulfilled promise', function (): void {
        $fake = new FakeGuzzleHttpClient();
        $fake->addJsonResponse('GET', '/test', ['id' => '123']);

        $promise = $fake->getAsync('/test');

        expect($promise)->toBeInstanceOf(PromiseInterface::class);

        $response = $promise->wait();
        expect($response)->toBeInstanceOf(Response::class);
        expect($response->json())->toBe(['id' => '123']);
    });

    it('postAsync returns fulfilled promise', function (): void {
        $fake = new FakeGuzzleHttpClient();
        $fake->addJsonResponse('POST', '/test', ['created' => true]);

        $response = $fake->postAsync('/test', ['data' => 'value'])->wait();

        expect($response->json())->toBe(['created' => true]);
    });

    it('deleteAsync returns fulfilled promise', function (): void {
        $fake = new FakeGuzzleHttpClient();
        $fake->addJsonResponse('DELETE', '/test', ['deleted' => true]);

        $response = $fake->deleteAsync('/test')->wait();

        expect($response->json())->toBe(['deleted' => true]);
    });

    it('async methods return fulfilled promise for missing mocks with 404 response', function (): void {
        $fake = new FakeGuzzleHttpClient();

        $promise = $fake->getAsync('/unknown');
        $response = $promise->wait();

        expect($response->statusCode())->toBe(404);
    });

    it('addExceptionResponse causes rejection', function (): void {
        $fake = new FakeGuzzleHttpClient();
        $fake->addExceptionResponse('GET', '/error', new NotFoundException('Not found'));

        $promise = $fake->getAsync('/error');

        expect(fn () => $promise->wait())->toThrow(NotFoundException::class);
    });
});
