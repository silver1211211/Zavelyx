<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('number_order_id')->constrained()->cascadeOnDelete();
            $table->string('sender')->nullable();
            $table->text('message');
            $table->string('code')->nullable(); // extracted OTP/code
            $table->json('raw_response')->nullable();
            $table->timestamp('received_at');
            $table->timestamps();

            $table->index('number_order_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_messages');
    }
};
