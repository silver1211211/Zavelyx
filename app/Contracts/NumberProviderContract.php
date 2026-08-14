<?php

namespace App\Contracts;

/**
 * Contract that every number-provider driver must implement.
 *
 * All methods return data normalised to the 5SIM-compatible shape so that
 * SmsController can remain provider-agnostic.
 *
 * Normalised shapes:
 *
 * getAllProducts()
 *   [ 'service_id' => ['Category'=>'...', 'Qty'=>0, 'Price'=>0.0], ... ]
 *
 * getCountries()
 *   [ 'country_code' => ['text_en'=>'...', 'iso'=>'XX', 'prefix'=>'1'], ... ]
 *
 * getPrices(string $product)
 *   [ 'country' => [ 'operator' => ['count'=>0, 'cost'=>0.0, 'rate'=>0.0] ] ]
 *
 * getAllProductsByCountry(string $country)
 *   [ 'service_id' => ['Category'=>'...', 'Qty'=>0, 'Price'=>0.0], ... ]
 *
 * buyNumber()
 *   [ 'id'=>'...', 'phone'=>'...', 'status'=>'PENDING', 'expires'=>'ISO8601', 'sms'=>[] ]
 *
 * checkOrder()
 *   [ 'id'=>'...', 'phone'=>'...', 'status'=>'PENDING|RECEIVED|...', 'sms'=>[...] ]
 *   sms items: [ 'text'=>'...', 'sender'=>'...', 'created_at'=>'ISO8601' ]
 *
 * cancelOrder() / finishOrder()
 *   [ 'id'=>'...', 'status'=>'CANCELLED|FINISHED' ]
 */
interface NumberProviderContract
{
    /** Check connectivity and return balance. */
    public function getBalance(): float;

    /** Quick connectivity test — returns true if the API key is valid. */
    public function testConnection(): bool;

    /**
     * All products across any country/operator.
     * Normalised to Structure A: { service_id => { Category, Qty, Price } }
     */
    public function getAllProducts(): array;

    /** Supported countries. Normalised to { code => { text_en, iso, prefix } } */
    public function getCountries(): array;

    /**
     * Prices for one product across all countries/operators.
     * Normalised to { country => { operator => { count, cost, rate } } }
     */
    public function getPrices(string $product): array;

    /** All products available in a specific country. Normalised to Structure A. */
    public function getAllProductsByCountry(string $country): array;

    /**
     * Batch version of getAllProductsByCountry.
     * Returns { countryCode => { service_id => { Category, Qty, Price } } }
     */
    public function getProductsForCountries(array $countries): array;

    /**
     * Purchase a temporary number.
     * Returns normalised buy response.
     */
    public function buyNumber(string $country, string $operator, string $service): array;

    /** Poll an active order for SMS messages / status changes. */
    public function checkOrder(int|string $orderId): array;

    /** Cancel an order (refund eligible). */
    public function cancelOrder(int|string $orderId): array;

    /** Mark order finished (keep number, stop polling). */
    public function finishOrder(int|string $orderId): array;

    /** Flush all cached data for this provider. */
    public static function bustCache(): void;
}
