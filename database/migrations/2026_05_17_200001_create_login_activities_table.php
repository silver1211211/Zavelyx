<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('login_activities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('ip_address', 45)->nullable();
            $table->text('user_agent')->nullable();
            $table->string('device_type', 16)->nullable();  // desktop, mobile, tablet
            $table->string('browser', 64)->nullable();
            $table->string('os', 64)->nullable();
            $table->string('country', 64)->nullable();
            $table->string('city', 64)->nullable();
            $table->enum('action', ['login', 'logout', 'failed'])->default('login');
            $table->string('session_id', 128)->nullable()->index();
            $table->boolean('is_current')->default(false);
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('login_activities');
    }
};
