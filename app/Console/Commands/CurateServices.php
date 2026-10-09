<?php

namespace App\Console\Commands;

use App\Models\Service;
use App\Services\SmmProviderService;
use App\Http\Controllers\OrderController;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class CurateServices extends Command
{
    protected $signature = 'services:curate {--dry-run : Report selections without saving}';
    protected $description = 'Select a concise, provider-balanced customer SMM catalog';

    private const MAJOR = ['instagram', 'facebook', 'tiktok', 'youtube', 'telegram'];
    private const REJECT = ['test', 'testing', 'disabled', 'unavailable', 'do not order', 'maintenance', 'separator'];

    public function handle(SmmProviderService $smm): int
    {
        $services = Service::query()
            ->where('type', 'smm')->whereNotNull('provider_id')
            ->withCount(['orders as completed_orders_count' => fn ($q) => $q->where('status', 'completed')])
            ->get();

        $eligible = $services->filter(fn (Service $service) => $this->eligible($service));
        $selected = collect();

        foreach ($eligible->groupBy(fn (Service $service) => $service->provider_id.'|'.$this->platform($service)) as $group) {
            $platform = $this->platform($group->first());
            $limit = in_array($platform, self::MAJOR, true) ? 6 : 2;
            $selected->push(...$group->sortByDesc(fn (Service $service) => $this->score($service))->take($limit));
        }

        // Never hide a valid service that has already completed real customer orders.
        $selected = $selected->merge($eligible->where('completed_orders_count', '>', 0))->unique('id')->values();

        $this->table(['Provider', 'Platform', 'Selected'], $selected
            ->groupBy(fn (Service $service) => ($service->provider?->name ?? '#'.$service->provider_id).' / '.$this->platform($service))
            ->map(fn (Collection $items, string $label) => [$label, $this->platform($items->first()), $items->count()])
            ->values()->all());

        if (! $this->option('dry-run')) {
            DB::table('services')->where('type', 'smm')->whereNotNull('provider_id')
                ->update(['metadata' => DB::raw("JSON_SET(COALESCE(metadata, JSON_OBJECT()), '$.catalog_approved', false)")]);
            DB::table('services')->whereIn('id', $selected->pluck('id'))
                ->update(['metadata' => DB::raw("JSON_SET(COALESCE(metadata, JSON_OBJECT()), '$.catalog_approved', true)")]);
            $smm->clearUserServiceCaches();
        }

        $chosenPlatforms = [];
        foreach ($selected as $service) {
            $chosenPlatforms[$this->platform($service)] = true;
        }
        $this->info('Selected '.$selected->count().' services across '.count($chosenPlatforms).' platforms.');
        if (! $this->option('dry-run')) {
            $approved = Service::available()->where('type', 'smm');
            $approvedCount = (clone $approved)->count();
            $missingDescriptions = (clone $approved)
                ->where(fn ($query) => $query->whereNull('metadata->description')->orWhere('metadata->description', ''))
                ->count();
            $this->line("Active customer catalog: {$approvedCount}; missing descriptions: {$missingDescriptions}.");

            $checks = [];
            foreach (self::MAJOR as $platform) {
                $response = app(OrderController::class)->loadServices(Request::create('/orders/services', 'GET', ['platform' => $platform]));
                foreach (collect($response->getData(true))->take(2) as $service) {
                    $checks[] = [
                        $platform,
                        $service['id'],
                        mb_strimwidth($service['name'], 0, 52, '…'),
                        trim((string) data_get($service, 'metadata.description')) !== '' ? 'yes' : 'NO',
                    ];
                }
            }
            $this->table(['Platform', 'ID', 'Controller service', 'Description'], $checks);
        }

        return self::SUCCESS;
    }

    private function eligible(Service $service): bool
    {
        $name = strtolower($service->name);
        return $service->is_active
            && (float) $service->selling_price > 0
            && (float) $service->min_amount > 0
            && (float) $service->max_amount >= (float) $service->min_amount
            && ! collect(self::REJECT)->contains(fn (string $term) => str_contains($name, $term));
    }

    private function platform(Service $service): string
    {
        return strtolower((string) data_get($service->metadata, 'platform', 'other')) ?: 'other';
    }

    private function score(Service $service): float
    {
        $metadata = $service->metadata ?? [];
        return ($service->completed_orders_count * 1000000)
            + (! empty($metadata['description']) ? 10000 : 0)
            + (! empty($metadata['refill']) ? 500 : 0)
            + (! empty($metadata['cancel']) ? 250 : 0)
            + max(0, 100 - log10(max(1, (float) $service->selling_price)) * 10);
    }
}
