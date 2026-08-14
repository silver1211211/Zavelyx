<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('deposit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('invoice_id')
                ->constrained('payment_invoices')
                ->cascadeOnDelete();

            // Events: webhook_received, signature_invalid, invoice_not_found,
            //         status_updated, credit_queued, credit_attempted,
            //         credit_succeeded, credit_failed, credit_skipped,
            //         retry_scheduled, manual_approve, manual_reject,
            //         poll_triggered, poll_paid, poll_expired
            $table->string('event', 40)->index();
            $table->text('message')->nullable();
            $table->json('metadata')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['invoice_id', 'event']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('deposit_logs');
    }
};
