<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndex('services', ['type', 'is_active', 'name'], 'services_type_active_name_idx');
        $this->addIndex('services', ['type', 'is_active', 'id'], 'services_type_active_id_idx');
        $this->addIndex('services', ['type', 'is_active', 'provider_service_code'], 'services_type_active_provider_code_idx');
        $this->addIndex('services', ['type', 'is_active', 'category_id', 'selling_price'], 'services_type_active_category_price_idx');
        $this->addIndex('services', ['provider_id', 'provider_service_code'], 'services_provider_service_code_idx');

        $this->addIndex('orders', ['user_id', 'status', 'created_at'], 'orders_user_status_created_idx');
        $this->addIndex('orders', ['provider_id', 'last_synced_at'], 'orders_provider_synced_idx');
        $this->addIndex('orders', ['refund_status', 'status'], 'orders_refund_status_idx');

        $this->addIndex('number_orders', ['user_id', 'created_at'], 'number_orders_user_created_idx');
        $this->addIndex('number_orders', ['user_id', 'status', 'expires_at'], 'number_orders_user_status_expires_idx');
        $this->addIndex('number_providers', ['is_active', 'priority'], 'number_providers_active_priority_idx');

        $this->addIndex('payment_invoices', ['user_id', 'status', 'created_at'], 'payment_invoices_user_status_created_idx');
        $this->addIndex('notifications', ['user_id', 'is_read', 'created_at'], 'notifications_user_read_created_idx');
    }

    public function down(): void
    {
        foreach ([
            ['services', 'services_type_active_name_idx'],
            ['services', 'services_type_active_id_idx'],
            ['services', 'services_type_active_provider_code_idx'],
            ['services', 'services_type_active_category_price_idx'],
            ['services', 'services_provider_service_code_idx'],
            ['orders', 'orders_user_status_created_idx'],
            ['orders', 'orders_provider_synced_idx'],
            ['orders', 'orders_refund_status_idx'],
            ['number_orders', 'number_orders_user_created_idx'],
            ['number_orders', 'number_orders_user_status_expires_idx'],
            ['number_providers', 'number_providers_active_priority_idx'],
            ['payment_invoices', 'payment_invoices_user_status_created_idx'],
            ['notifications', 'notifications_user_read_created_idx'],
        ] as [$table, $index]) {
            $this->dropIndex($table, $index);
        }
    }

    private function addIndex(string $table, array $columns, string $name): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        try {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($columns, $name));
        } catch (\Throwable) {
            // Already present or not supported on an older local schema.
        }
    }

    private function dropIndex(string $table, string $name): void
    {
        if (!Schema::hasTable($table)) {
            return;
        }

        try {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($name));
        } catch (\Throwable) {
            // Nothing to drop.
        }
    }
};
