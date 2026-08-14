<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('number_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('number_provider_id')->constrained()->cascadeOnDelete();
            $table->foreignId('wallet_id')->constrained()->cascadeOnDelete();

            // 5SIM fields
            $table->string('activation_id')->unique(); // 5SIM order ID
            $table->string('country');
            $table->string('operator')->nullable();
            $table->string('service');           // e.g. "telegram", "whatsapp"
            $table->string('phone_number');

            // Pricing
            $table->decimal('provider_cost', 10, 4); // cost from 5SIM in their currency
            $table->decimal('amount', 10, 8);        // what we charged the user (USD)
            $table->decimal('balance_before', 10, 8);
            $table->decimal('balance_after', 10, 8);

            // Status: PENDING | RECEIVED | FINISHED | CANCELLED | BANNED | EXPIRED | TIMEOUT
            $table->string('status')->default('PENDING');

            // SMS data (updated on poll)
            $table->string('otp_code')->nullable();
            $table->text('sms_text')->nullable();

            $table->timestamp('expires_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->json('raw_response')->nullable();

            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index('activation_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('number_orders');
    }
};
