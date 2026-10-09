<?php

namespace App\Console\Commands;

use App\Models\Provider;
use App\Services\SmmProviderService;
use Illuminate\Console\Command;

class BackfillServiceDescriptions extends Command
{
    protected $signature = 'services:backfill-descriptions {--provider= : Provider ID or slug} {--dry-run}';
    protected $description = 'Create factual descriptions from verified provider service attributes';

    public function handle(SmmProviderService $smm): int
    {
        $providerKey = $this->option('provider');
        $providers = Provider::where('type', 'smm')
            ->when($providerKey, fn ($query) => $query->where(fn ($q) => $q->where('id', $providerKey)->orWhere('slug', $providerKey)))
            ->orderBy('id')
            ->get();

        if ($providers->isEmpty()) {
            $this->error('SMM provider not found.');
            return self::FAILURE;
        }

        $updated = $preserved = 0;
        foreach ($providers as $provider) {
            $provider->services()->where('type', 'smm')->with('category:id,name')->orderBy('id')->chunkById(250, function ($services) use ($smm, &$updated, &$preserved): void {
                foreach ($services as $service) {
                    $metadata = $service->metadata ?? [];
                    if (trim((string) ($metadata['description'] ?? '')) !== '') {
                        $preserved++;
                        continue;
                    }

                    $metadata['description'] = $smm->buildFactualDescription($service);
                    $metadata['description_source'] = 'verified_attributes';
                    $metadata['documentation_status'] = 'factual_basics_complete';
                    if (!$this->option('dry-run')) {
                        $service->update(['metadata' => $metadata]);
                    }
                    $updated++;
                }
            });
        }

        if (!$this->option('dry-run')) {
            $smm->clearUserServiceCaches();
        }
        $this->info("Descriptions generated={$updated} preserved={$preserved}" . ($this->option('dry-run') ? ' [DRY RUN]' : ''));
        return self::SUCCESS;
    }
}
