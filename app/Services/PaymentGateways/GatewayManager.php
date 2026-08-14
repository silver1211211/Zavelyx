<?php

namespace App\Services\PaymentGateways;

use App\Models\PaymentGateway;

/**
 * Factory: resolves a PaymentGateway model → concrete driver.
 *
 * To add a new gateway:
 *  1. Implement GatewayContract in a new class (e.g. CoinbaseGateway)
 *  2. Add a case to self::make()
 *  3. Add the driver to GatewayController::SUPPORTED_DRIVERS
 */
class GatewayManager
{
    public static function make(PaymentGateway $gateway): GatewayContract
    {
        return match ($gateway->driver) {
            'paymento'        => new PaymentoGateway($gateway),
            'oxapay'          => new OxaPayGateway($gateway),
            'oxapay_invoice'  => new OxaPayInvoiceGateway($gateway),
            default           => throw new \InvalidArgumentException(
                "Unsupported payment gateway driver: [{$gateway->driver}]"
            ),
        };
    }

    /** Resolve the active gateway for a given driver slug. */
    public static function forDriver(string $driver): GatewayContract
    {
        $gateway = PaymentGateway::where('driver', $driver)
            ->where('is_active', true)
            ->firstOrFail();

        return self::make($gateway);
    }
}
