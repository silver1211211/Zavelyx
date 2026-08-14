<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('notifications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('type', 64)->index();          // welcome, deposit_success, …
            $table->string('category', 32)->default('system'); // system, transaction, security, promotion
            $table->string('priority', 16)->default('info');   // success, warning, error, info, promotion
            $table->string('title');
            $table->text('message');
            $table->json('data')->nullable();             // extra key/value metadata
            $table->string('action_url')->nullable();
            $table->string('action_label', 64)->nullable();
            $table->string('icon', 32)->nullable();       // lucide icon name hint
            $table->boolean('is_read')->default(false);
            $table->timestamp('read_at')->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'is_read']);
            $table->index(['user_id', 'created_at']);
        });

        Schema::create('notification_broadcasts', function (Blueprint $table) {
            $table->id();
            $table->string('title');
            $table->text('message');
            $table->string('type', 64)->default('admin_custom');
            $table->string('category', 32)->default('system');
            $table->string('priority', 16)->default('info');
            $table->json('data')->nullable();
            $table->string('action_url')->nullable();
            $table->string('action_label', 64)->nullable();
            $table->string('icon', 32)->nullable();
            $table->boolean('is_pinned')->default(false);
            $table->timestamp('expires_at')->nullable();
            $table->string('target_type', 32)->default('all'); // all, active, inactive, role, country, balance_range, specific
            $table->json('target_config')->nullable();         // {"role":"user"} / {"user_ids":[1,2]} / {"min_balance":10}
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->unsignedBigInteger('recipients_count')->default(0);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('notification_broadcasts');
        Schema::dropIfExists('notifications');
    }
};
