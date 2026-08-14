<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_gateways', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);                         // "NOWPayments"
            $table->string('driver', 50)->unique();              // "nowpayments"
            $table->boolean('is_active')->default(false);
            $table->boolean('is_default')->default(false);
            $table->text('api_key')->nullable();                 // encrypted
            $table->text('ipn_secret')->nullable();              // encrypted
            $table->json('extra_config')->nullable();            // {"sandbox": true, ...}
            $table->decimal('fee_percent', 5, 2)->default(0.00);
            $table->decimal('min_amount', 12, 2)->default(5.00);
            $table->decimal('max_amount', 12, 2)->default(10000.00);
            $table->unsignedSmallInteger('sort_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_gateways');
    }
};
