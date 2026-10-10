<?php

use App\Services\SmmProviderService;

beforeEach(function () {
    $this->service = new SmmProviderService;
});

it('completes processing only from an explicit numeric zero remaining count', function () {
    foreach ([0, '0', 0.0] as $zero) {
        $parsed = $this->service->parseStatusPayload([
            'status' => 'In Progress',
            'remains' => $zero,
        ], 'provider-42');

        expect($parsed['success'])->toBeTrue()
            ->and($parsed['remains_explicit'])->toBeTrue()
            ->and($parsed['remains'])->toBe(0)
            ->and($this->service->resolveSyncedOrderStatus('processing', $parsed['status'], $parsed['remains'], true, 500))
            ->toMatchArray([
                'status' => 'completed',
                'auto_completed' => true,
                'reason' => 'provider_processing_with_explicit_zero_remaining',
            ]);
    }
});

it('never treats missing null malformed or negative remains as zero', function ($value, $includeKey) {
    $payload = ['status' => 'Processing'];
    if ($includeKey) {
        $payload['remains'] = $value;
    }

    $parsed = $this->service->parseStatusPayload($payload);

    expect($parsed['success'])->toBeTrue()
        ->and($parsed['remains'])->toBeNull()
        ->and($parsed['remains_explicit'])->toBeFalse()
        ->and($this->service->resolveSyncedOrderStatus('processing', $parsed['status'], null, false, 500)['status'])
        ->toBe('processing');
})->with([
    'missing' => [null, false],
    'null' => [null, true],
    'empty' => ['', true],
    'decimal string' => ['0.0', true],
    'negative' => [-1, true],
    'boolean' => [false, true],
]);

it('preserves positive remaining counts as processing', function () {
    $parsed = $this->service->parseStatusPayload(['status' => 'Processing', 'remains' => '25']);

    expect($this->service->resolveSyncedOrderStatus('processing', $parsed['status'], $parsed['remains'], true, 500)['status'])
        ->toBe('processing');
});

it('does not override explicit provider terminal statuses with zero remaining', function ($providerStatus, $expected) {
    expect($this->service->resolveSyncedOrderStatus('processing', $providerStatus, 0, true, 500)['status'])
        ->toBe($expected);
})->with([
    ['Failed', 'failed'],
    ['Canceled', 'canceled'],
    ['Cancelled', 'canceled'],
    ['Partial', 'partial'],
    ['Completed', 'completed'],
]);

it('never reopens a local terminal order', function ($currentStatus) {
    expect($this->service->resolveSyncedOrderStatus($currentStatus, 'In Progress', 100, true, 500)['status'])
        ->toBe($currentStatus);
})->with(['completed', 'failed', 'canceled', 'partial']);

it('rejects error malformed and mismatched-order payloads', function ($payload) {
    expect($this->service->parseStatusPayload($payload, 'expected-10')['success'])->toBeFalse();
})->with([
    [['error' => 'timeout']],
    [['remains' => 0]],
    [['status' => 'Processing', 'order' => 'different-11', 'remains' => 0]],
]);
