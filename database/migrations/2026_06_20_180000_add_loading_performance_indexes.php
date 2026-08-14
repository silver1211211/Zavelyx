<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndex('services', ['type', 'is_active'], 'services_type_active_idx');
        $this->addIndex('services', ['provider_id', 'is_active'], 'services_provider_active_idx');
        $this->addIndex('services', ['category_id', 'type', 'is_active'], 'services_category_type_active_idx');
        $this->addIndex('services', ['provider_service_code'], 'services_provider_code_idx');

        $this->addIndex('orders', ['user_id', 'created_at'], 'orders_user_created_idx');
        $this->addIndex('orders', ['provider_id', 'status'], 'orders_provider_status_idx');
        $this->addIndex('orders', ['service_id', 'status'], 'orders_service_status_idx');
        $this->addIndex('orders', ['status', 'created_at'], 'orders_status_created_idx');

        $this->addIndex('users', ['is_active', 'created_at'], 'users_active_created_idx');
        $this->addIndex('providers', ['type', 'is_active', 'priority'], 'providers_type_active_priority_idx');
        $this->addIndex('categories', ['type', 'is_active', 'sort_order'], 'categories_type_active_sort_idx');

        $this->addIndex('number_orders', ['number_provider_id', 'status'], 'number_orders_provider_status_idx');
        $this->addIndex('number_orders', ['status', 'created_at'], 'number_orders_status_created_idx');

        $this->addIndex('transactions', ['user_id', 'created_at'], 'transactions_user_created_idx');
        $this->addIndex('transactions', ['type', 'status', 'created_at'], 'transactions_type_status_created_idx');

        $this->addIndex('payment_invoices', ['user_id', 'created_at'], 'payment_invoices_user_created_idx');
        $this->addIndex('payment_invoices', ['status', 'created_at'], 'payment_invoices_status_created_idx');
    }

    public function down(): void
    {
        foreach ([
            ['services', 'services_type_active_idx'],
            ['services', 'services_provider_active_idx'],
            ['services', 'services_category_type_active_idx'],
            ['services', 'services_provider_code_idx'],
            ['orders', 'orders_user_created_idx'],
            ['orders', 'orders_provider_status_idx'],
            ['orders', 'orders_service_status_idx'],
            ['orders', 'orders_status_created_idx'],
            ['users', 'users_active_created_idx'],
            ['providers', 'providers_type_active_priority_idx'],
            ['categories', 'categories_type_active_sort_idx'],
            ['number_orders', 'number_orders_provider_status_idx'],
            ['number_orders', 'number_orders_status_created_idx'],
            ['transactions', 'transactions_user_created_idx'],
            ['transactions', 'transactions_type_status_created_idx'],
            ['payment_invoices', 'payment_invoices_user_created_idx'],
            ['payment_invoices', 'payment_invoices_status_created_idx'],
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
            // Index already exists or column differs in an older local install.
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
