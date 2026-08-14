<?php

namespace App\Console\Commands;

use App\Models\Provider;
use App\Services\SmmProviderService;
use Illuminate\Console\Command;

class SyncServices extends Command
{
    protected $signature   = 'services:sync {--provider= : Only sync a specific provider ID or slug}';
    protected $description = 'Import/update services from all active SMM providers';

    public function handle(SmmProviderService $smm): int
    {
        $query = Provider::where('is_active', true)->where('type', 'smm');

        if ($id = $this->option('provider')) {
            $query->where(fn($q) => $q->where('id', $id)->orWhere('slug', $id));
        }

        $providers = $query->get();

        if ($providers->isEmpty()) {
            $this->warn('No active providers found.');
            return self::SUCCESS;
        }

        $totalImported = 0;
        $totalUpdated  = 0;

        foreach ($providers as $provider) {
            $this->info("Syncing: {$provider->name}…");

            try {
                $result = $smm->importServices($provider);
                $totalImported += $result['imported'];
                $totalUpdated  += $result['updated'];

                $this->line("  ✓ {$result['imported']} imported, {$result['updated']} updated ({$result['total']} total)");
            } catch (\Throwable $e) {
                $this->error("  ✗ Failed: {$e->getMessage()}");
            }
        }

        $this->info("Sync complete. Imported: {$totalImported}, Updated: {$totalUpdated}");
        return self::SUCCESS;
    }
}
