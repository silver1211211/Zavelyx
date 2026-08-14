<?php

namespace App\Console\Commands;

use App\Models\Provider;
use App\Services\SmmProviderService;
use Illuminate\Console\Command;

class CheckProviders extends Command
{
    protected $signature   = 'providers:check';
    protected $description = 'Test all active SMM provider API connections and refresh their balance';

    public function handle(SmmProviderService $smm): int
    {
        $providers = Provider::where('type', 'smm')->get();

        if ($providers->isEmpty()) {
            $this->warn('No providers found.');
            return self::SUCCESS;
        }

        $this->info("Checking {$providers->count()} provider(s)…");

        foreach ($providers as $provider) {
            $status = $provider->is_active ? '<fg=green>active</>' : '<fg=yellow>inactive</>';
            $this->line("  {$provider->name} [{$status}]");

            $result = $smm->testConnection($provider);

            if ($result['success']) {
                $balance = isset($result['balance']) ? " — balance: {$result['balance']}" : '';
                $this->line("    <fg=green>✓</> {$result['message']}{$balance}");

                if (isset($result['balance'])) {
                    $provider->update(['balance' => $result['balance']]);
                }
            } else {
                $this->line("    <fg=red>✗</> {$result['message']}");

                // Auto-deactivate provider if it fails
                if ($provider->is_active) {
                    $this->warn("    Auto-deactivating due to connection failure.");
                    $provider->update(['is_active' => false]);
                }
            }
        }

        $this->info('Done.');
        return self::SUCCESS;
    }
}
