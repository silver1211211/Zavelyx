<?php

namespace App\Console\Commands;

use App\Models\Provider;
use App\Services\SmmProviderService;
use Illuminate\Console\Command;

class SyncProviders extends Command
{
    protected $signature = 'providers:sync
        {--provider= : Only sync a specific provider ID or slug}
        {--dry-run : Show service counts without importing}';

    protected $description = 'Import/update services from all active SMM providers';

    public function handle(SmmProviderService $smm): int
    {
        $query = Provider::where('is_active', true)->where('type', 'smm');

        if ($id = $this->option('provider')) {
            $query->where(fn($q) => $q->where('id', $id)->orWhere('slug', $id));
        }

        $providers = $query->get();

        if ($providers->isEmpty()) {
            $this->warn('No active SMM providers found.');
            return self::SUCCESS;
        }

        $this->info("Syncing {$providers->count()} provider(s)…");

        $totalUpdated  = 0;
        $failed        = 0;

        foreach ($providers as $provider) {
            $this->line("  [{$provider->name}] fetching services…");

            try {
                if ($this->option('dry-run')) {
                    $raw = $smm->fetchServices($provider);
                    $this->line("    {$provider->name}: {$provider->name} has " . count($raw) . " services (dry run, not imported)");
                    continue;
                }

                $result = $smm->syncPrices($provider);
                $totalUpdated  += $result['updated'];

                $this->line("    updated={$result['updated']} unchanged={$result['unchanged']} missing={$result['missing']} failed={$result['failed']}");
            } catch (\Throwable $e) {
                $this->error("    ✗ {$provider->name}: {$e->getMessage()}");
                $failed++;
            }
        }

        $this->info("providers:sync complete — updated:{$totalUpdated}, failed:{$failed}");

        return $failed > 0 ? self::FAILURE : self::SUCCESS;
    }
}
