<?php

declare(strict_types=1);

use Danielgnh\PolymarketPhp\Client;
use Danielgnh\PolymarketPhp\Http\FakeGuzzleHttpClient;

beforeEach(function (): void {
    $this->fakeHttp = new FakeGuzzleHttpClient();
    $this->client = new Client(clobHttpClient: $this->fakeHttp);
});

describe('Account::getBalanceAllowance()', function (): void {
    it('fetches balance and allowance information', function (): void {
        $balanceData = [
            'balance' => '1000.00',
            'allowance' => '500.00',
            'token' => 'USDC',
        ];

        $this->fakeHttp->addJsonResponse('GET', '/balance-allowance', $balanceData);

        $result = $this->client->clob()->account()->getBalanceAllowance();

        expect($result)->toBeArray()
            ->and($result)->toHaveKey('balance')
            ->and($result['balance'])->toBe('1000.00');
    });
});

describe('Account::updateBalanceAllowance()', function (): void {
    it('updates allowance settings', function (): void {
        $updateResponse = [
            'success' => true,
            'allowance' => '750.00',
        ];

        $this->fakeHttp->addJsonResponse('GET', '/update-balance-allowance', $updateResponse);

        $result = $this->client->clob()->account()->updateBalanceAllowance(['allowance' => '750.00']);

        expect($result)->toBeArray()
            ->and($result['success'])->toBeTrue();
    });
});

describe('Account::getNotifications()', function (): void {
    it('retrieves user notifications', function (): void {
        $notificationsData = [
            ['id' => 'notif_1', 'type' => 'order_filled', 'message' => 'Your order was filled'],
            ['id' => 'notif_2', 'type' => 'order_cancelled', 'message' => 'Your order was cancelled'],
        ];

        $this->fakeHttp->addJsonResponse('GET', '/notifications', $notificationsData);

        $result = $this->client->clob()->account()->getNotifications();

        expect($result)->toBeArray()
            ->and($result)->toHaveCount(2)
            ->and($result[0]['type'])->toBe('order_filled');
    });
});

describe('Account::dropNotifications()', function (): void {
    it('deletes notifications', function (): void {
        $deleteResponse = ['success' => true, 'deleted_count' => 5];

        $this->fakeHttp->addJsonResponse('DELETE', '/notifications', $deleteResponse);

        $result = $this->client->clob()->account()->dropNotifications();

        expect($result)->toBeArray()
            ->and($result['success'])->toBeTrue();
    });
});

describe('Account::getClosedOnlyMode()', function (): void {
    it('checks account restriction status', function (): void {
        $statusData = [
            'closed_only' => false,
            'reason' => null,
        ];

        $this->fakeHttp->addJsonResponse('GET', '/closed-only', $statusData);

        $result = $this->client->clob()->account()->getClosedOnlyMode();

        expect($result)->toBeArray()
            ->and($result)->toHaveKey('closed_only')
            ->and($result['closed_only'])->toBeFalse();
    });
});
