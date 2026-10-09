<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('currencies', function (Blueprint $table): void {
            $table->decimal('source_exchange_rate', 20, 10)->nullable()->after('exchange_rate');
            $table->boolean('auto_update')->default(true)->after('source_exchange_rate');
        });

        $currencies = [
            ['USD', 'US Dollar', '$'],
            ['NGN', 'Nigerian Naira', '₦'],
            ['EUR', 'Euro', '€'],
            ['GBP', 'British Pound', '£'],
            ['GHS', 'Ghanaian Cedi', 'GH₵'],
            ['KES', 'Kenyan Shilling', 'KSh'],
            ['ZAR', 'South African Rand', 'R'],
            ['XOF', 'West African CFA Franc', 'CFA'],
            ['INR', 'Indian Rupee', '₹'],
            ['PKR', 'Pakistani Rupee', '₨'],
            ['BDT', 'Bangladeshi Taka', '৳'],
            ['IDR', 'Indonesian Rupiah', 'Rp'],
            ['PHP', 'Philippine Peso', '₱'],
            ['BRL', 'Brazilian Real', 'R$'],
            ['AED', 'UAE Dirham', 'د.إ'],
        ];

        $now = now();

        foreach ($currencies as $sortOrder => [$code, $name, $symbol]) {
            $existing = DB::table('currencies')->where('code', $code)->first();

            if ($existing) {
                DB::table('currencies')->where('id', $existing->id)->update([
                    'name' => $name,
                    'symbol' => $symbol,
                    'is_active' => true,
                    'sort_order' => $sortOrder,
                    'source_exchange_rate' => $code === 'USD' ? 1 : $existing->exchange_rate,
                    'auto_update' => $code !== 'USD',
                    'updated_at' => $now,
                ]);
                continue;
            }

            DB::table('currencies')->insert([
                'code' => $code,
                'name' => $name,
                'symbol' => $symbol,
                'exchange_rate' => 1,
                'source_exchange_rate' => 1,
                'auto_update' => $code !== 'USD',
                'is_active' => true,
                'is_default' => false,
                'sort_order' => $sortOrder,
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        DB::table('currencies')->where('code', 'USD')->update([
            'exchange_rate' => 1,
            'source_exchange_rate' => 1,
            'auto_update' => false,
            'is_active' => true,
            'is_default' => true,
        ]);
        DB::table('currencies')->where('code', '!=', 'USD')->update(['is_default' => false]);

        foreach ([
            'currency.live_rates_enabled' => '1',
            'currency.exchange_api_url' => 'https://open.er-api.com/v6/latest/USD',
            'currency.exchange_refresh_interval' => '1440',
            'currency.rate_markup_percent' => '4',
        ] as $key => $value) {
            DB::table('settings')->updateOrInsert(
                ['key' => $key],
                ['value' => $value, 'updated_at' => $now, 'created_at' => $now],
            );
        }
    }

    public function down(): void
    {
        Schema::table('currencies', function (Blueprint $table): void {
            $table->dropColumn(['source_exchange_rate', 'auto_update']);
        });
    }
};
