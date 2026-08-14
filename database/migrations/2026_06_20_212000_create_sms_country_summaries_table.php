<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sms_country_summaries', function (Blueprint $table): void {
            $table->string('code', 100)->primary();
            $table->string('name', 150);
            $table->unsignedBigInteger('qty')->default(0);
            $table->timestamps();
        });

        DB::statement(<<<'SQL'
            INSERT INTO sms_country_summaries (code, name, qty, created_at, updated_at)
            SELECT
                sms_country,
                COALESCE(NULLIF(MAX(sms_country_name), ''), sms_country) as name,
                SUM(sms_available_count) as qty,
                NOW(),
                NOW()
            FROM services
            WHERE type = 'sms'
              AND is_active = 1
              AND sms_country IS NOT NULL
              AND sms_country <> ''
              AND sms_country <> 'any'
            GROUP BY sms_country
        SQL);
    }

    public function down(): void
    {
        Schema::dropIfExists('sms_country_summaries');
    }
};
