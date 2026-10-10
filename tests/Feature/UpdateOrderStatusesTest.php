<?php

use App\Models\Category;
use App\Models\Order;
use App\Models\Provider;
use App\Models\Service;
use App\Models\Transaction;
use App\Models\User;
use App\Models\Wallet;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;

beforeEach(function () {
    $this->provider = Provider::create([
        'name' => 'Standard SMM Test',
        'slug' => 'standard-smm-test',
        'type' => 'smm',
        'base_url' => 'https://smm.test/api/v2',
        'credentials' => ['api_key' => 'test-only-key'],
        'is_active' => true,
    ]);
    $category = Category::create([
        'name' => 'Test Category',
        'slug' => 'test-category',
        'type' => 'smm',
        'is_active' => true,
    ]);
    $this->service = Service::create([
        'category_id' => $category->id,
        'provider_id' => $this->provider->id,
        'name' => 'Test Service',
        'slug' => 'test-service',
        'type' => 'smm',
        'provider_service_code' => '10',
        'selling_price' => 1,
        'is_active' => true,
    ]);
    $this->user = User::factory()->create();
    $this->wallet = Wallet::create([
        'user_id' => $this->user->id,
        'currency' => 'USD',
        'balance' => 50,
        'ledger_balance' => 50,
    ]);
});

function statusTestOrder(object $test, array $attributes = []): Order
{
    return Order::create(array_merge([
        'reference' => (string) Str::uuid(),
        'user_id' => $test->user->id,
        'service_id' => $test->service->id,
        'provider_id' => $test->provider->id,
        'amount' => 1,
        'status' => 'processing',
        'provider_order_id' => 'provider-42',
        'quantity' => 500,
        'link' => 'https://example.test/post',
    ], $attributes));
}

it('completes in the same cycle from explicit provider zero without financial changes', function () {
    $order = statusTestOrder($this);
    Http::fake(['smm.test/*' => Http::response([
        'provider-42' => ['status' => 'In Progress', 'remains' => '0', 'start_count' => '10'],
    ])]);

    $this->artisan('orders:update --limit=200')->assertSuccessful();

    $order->refresh();
    expect($order->status)->toBe('completed')
        ->and($order->remains)->toBe(0)
        ->and($order->provider_response['auto_completion_reason'])->toBe('provider_processing_with_explicit_zero_remaining')
        ->and($order->provider_response['completion_evidence']['provider_order_id'])->toBe('provider-42')
        ->and((float) $this->wallet->fresh()->balance)->toBe(50.0)
        ->and(Transaction::count())->toBe(0);
});

it('does not reopen or resubmit a completed order on a later synchronization cycle', function () {
    $order = statusTestOrder($this);
    Http::fake(['smm.test/*' => Http::response([
        'provider-42' => ['status' => 'In Progress', 'remains' => 0],
    ])]);

    $this->artisan('orders:update --limit=200')->assertSuccessful();
    $this->artisan('orders:update --limit=200')->assertSuccessful();

    Http::assertSentCount(1);
    expect($order->fresh()->status)->toBe('completed')
        ->and((float) $this->wallet->fresh()->balance)->toBe(50.0)
        ->and(Transaction::count())->toBe(0);
});

it('keeps processing and preserves progress when remains is missing', function () {
    $order = statusTestOrder($this, ['remains' => 125]);
    Http::fake(['smm.test/*' => Http::response([
        'provider-42' => ['status' => 'In Progress', 'start_count' => 10],
    ])]);

    $this->artisan('orders:update --limit=200')->assertSuccessful();

    $order->refresh();
    expect($order->status)->toBe('processing')
        ->and($order->remains)->toBe(125)
        ->and($order->provider_response['remains_explicit'])->toBeFalse();
});

it('honors explicit failed partial and canceled statuses even with zero remains', function ($providerStatus, $expected) {
    $order = statusTestOrder($this);
    Http::fake(['smm.test/*' => Http::response([
        'provider-42' => ['status' => $providerStatus, 'remains' => 0],
    ])]);

    $this->artisan('orders:update --limit=200')->assertSuccessful();

    expect($order->fresh()->status)->toBe($expected)
        ->and(Transaction::count())->toBe(0);
})->with([
    ['Failed', 'failed'],
    ['Partial', 'partial'],
    ['Canceled', 'canceled'],
]);

it('does not change order state on provider errors', function () {
    $order = statusTestOrder($this, ['remains' => 200]);
    Http::fake(['smm.test/*' => Http::response(['error' => 'temporarily unavailable'])]);

    $this->artisan('orders:update --limit=200')->assertSuccessful();

    $order->refresh();
    expect($order->status)->toBe('processing')
        ->and($order->remains)->toBe(200)
        ->and($order->last_synced_at)->toBeNull();
});

it('does not poll inactive providers', function () {
    $order = statusTestOrder($this);
    $this->provider->update(['is_active' => false]);
    Http::fake();

    $this->artisan('orders:update --limit=200')->assertSuccessful();

    Http::assertNothingSent();
    expect($order->fresh()->status)->toBe('processing');
});
