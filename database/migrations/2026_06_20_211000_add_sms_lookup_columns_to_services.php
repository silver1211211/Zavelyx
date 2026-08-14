<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('services', function (Blueprint $table): void {
            if (!Schema::hasColumn('services', 'sms_country')) {
                $table->string('sms_country', 100)->nullable()->after('metadata');
            }

            if (!Schema::hasColumn('services', 'sms_country_name')) {
                $table->string('sms_country_name', 150)->nullable()->after('sms_country');
            }

            if (!Schema::hasColumn('services', 'sms_operator')) {
                $table->string('sms_operator', 100)->nullable()->after('sms_country_name');
            }

            if (!Schema::hasColumn('services', 'sms_available_count')) {
                $table->unsignedInteger('sms_available_count')->default(0)->after('sms_operator');
            }

            if (!Schema::hasColumn('services', 'number_provider_driver')) {
                $table->string('number_provider_driver', 50)->nullable()->after('sms_available_count');
            }
        });

        $this->addIndex('services', ['type', 'is_active', 'sms_country'], 'services_type_active_sms_country_idx');
        $this->addIndex('services', ['type', 'is_active', 'provider_service_code', 'sms_country', 'selling_price'], 'services_sms_code_country_price_idx');
        $this->addIndex('services', ['type', 'is_active', 'name', 'sms_country', 'selling_price'], 'services_sms_name_country_price_idx');
        $this->addIndex('services', ['type', 'is_active', 'number_provider_driver'], 'services_type_active_number_driver_idx');

        $lastId = 0;
        do {
            $ids = DB::table('services')
                ->where('type', 'sms')
                ->where('id', '>', $lastId)
                ->orderBy('id')
                ->limit(5000)
                ->pluck('id');

            if ($ids->isEmpty()) {
                break;
            }

            $min = (int) $ids->first();
            $max = (int) $ids->last();
            $lastId = $max;

            DB::statement(<<<'SQL'
                UPDATE services
                SET
                    sms_country = NULLIF(JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.country')), 'null'),
                    sms_country_name = NULLIF(JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.country_name')), 'null'),
                    sms_operator = NULLIF(JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.operator')), 'null'),
                    sms_available_count = CASE
                        WHEN JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.available_count')) REGEXP '^[0-9]+$'
                        THEN CAST(JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.available_count')) AS UNSIGNED)
                        ELSE 0
                    END,
                    number_provider_driver = NULLIF(JSON_UNQUOTE(JSON_EXTRACT(metadata, '$.number_provider_driver')), 'null')
                WHERE type = 'sms' AND id BETWEEN ? AND ?
            SQL, [$min, $max]);
        } while (true);
    }

    public function down(): void
    {
        $this->dropIndex('services', 'services_type_active_sms_country_idx');
        $this->dropIndex('services', 'services_sms_code_country_price_idx');
        $this->dropIndex('services', 'services_sms_name_country_price_idx');
        $this->dropIndex('services', 'services_type_active_number_driver_idx');

        Schema::table('services', function (Blueprint $table): void {
            foreach (['number_provider_driver', 'sms_available_count', 'sms_operator', 'sms_country_name', 'sms_country'] as $column) {
                if (Schema::hasColumn('services', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }

    private function addIndex(string $table, array $columns, string $name): void
    {
        if (!$this->indexExists($table, $name)) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->index($columns, $name));
        }
    }

    private function dropIndex(string $table, string $name): void
    {
        if ($this->indexExists($table, $name)) {
            Schema::table($table, fn (Blueprint $blueprint) => $blueprint->dropIndex($name));
        }
    }

    private function indexExists(string $table, string $name): bool
    {
        $database = DB::getDatabaseName();

        return DB::table('information_schema.statistics')
            ->where('table_schema', $database)
            ->where('table_name', $table)
            ->where('index_name', $name)
            ->exists();
    }
};
