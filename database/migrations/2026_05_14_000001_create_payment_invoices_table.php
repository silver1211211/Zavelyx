<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_invoices', function (Blueprint $table) {
            $table->id();
            $table->string('reference', 64)->unique();       // our internal idempotency key
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('gateway', 20)->default('nowpayments');
            $table->string('gateway_invoice_id')->nullable()->index(); // NOWPayments invoice id
            $table->string('gateway_payment_id')->nullable()->index(); // NOWPayments payment id
            $table->decimal('price_amount', 18, 8);          // requested USD amount
            $table->string('price_currency', 10)->default('usd');
            $table->string('pay_currency', 20)->nullable();  // crypto (btc, eth, usdt…)
            $table->decimal('pay_amount', 28, 18)->nullable();
            $table->string('pay_address', 255)->nullable();
            $table->string('status', 30)->default('waiting')->index(); // waiting|confirming|confirmed|sending|partially_paid|finished|failed|refunded|expired
            $table->string('payment_url', 500)->nullable();
            $table->decimal('actually_paid', 28, 18)->nullable();
            $table->timestamp('credited_at')->nullable();    // when wallet was credited
            $table->json('gateway_payload')->nullable();     // last IPN payload
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_invoices');
    }
};
