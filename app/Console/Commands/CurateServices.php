<?php

namespace App\Console\Commands;

use App\Models\Service;
use App\Services\SmmProviderService;
use App\Http\Controllers\OrderController;
use Illuminate\Console\Command;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class CurateServices extends Command
{
    protected $signature = 'services:curate {--dry-run : Report selections without saving}';
    protected $description = 'Select a concise, provider-balanced customer SMM catalog';

    private const MAJOR = ['instagram', 'facebook', 'tiktok', 'youtube', 'telegram'];
    private const REJECT = [
        'test', 'testing', 'disabled', 'unavailable', 'do not order', 'maintenance', 'separator',
        'provider', 'scrape', 'impossible', 'updateing', 'updating', 'own provided',
    ];
    private const SERVICE_KINDS = [
        'followers' => ['followers', 'follower'],
        'likes' => ['likes', 'like', 'dislikes'],
        'views' => ['views', 'view'],
        'comments' => ['comments', 'comment'],
        'reactions' => ['reactions', 'reaction'],
        'members' => ['members', 'member'],
        'subscribers' => ['subscribers', 'subscriber'],
        'plays' => ['plays', 'play', 'streams', 'stream', 'listeners', 'listener'],
        'shares' => ['shares', 'share'],
        'saves' => ['saves', 'save'],
        'reach' => ['reach', 'impressions', 'impression'],
        'watch-hours' => ['watch hours', 'watch time'],
        'stories' => ['story', 'stories'],
        'live' => ['live stream', 'livestream'],
        'votes' => ['poll votes', 'votes'],
        'reposts' => ['reposts', 'repost', 'retweets', 'retweet'],
        'traffic' => ['traffic', 'visitors', 'website visits'],
    ];

    public function handle(SmmProviderService $smm): int
    {
        ini_set('memory_limit', '512M');

        $services = Service::query()
            ->where('type', 'smm')->whereNotNull('provider_id')
            ->with(['category:id,name', 'provider:id,name,slug'])
            ->withCount(['orders as completed_orders_count' => fn ($q) => $q->where('status', 'completed')])
            ->get();

        $eligible = $services->filter(fn (Service $service) => $this->eligible($service));
        $previouslyApprovedServices = $eligible
            ->filter(fn (Service $service) => data_get($service->metadata, 'catalog_approved') === true)
            ->values();
        $selectedCategoryIds = collect();

        // Keep every configured provider represented on each platform where it
        // has a valid offering, even when another provider has a lower price.
        foreach ($eligible->groupBy(fn (Service $service) => $service->provider_id.'|'.$this->platform($service)) as $group) {
            $best = $group->sortByDesc(fn (Service $service) => $this->score($service))->first();
            if ($best) $selectedCategoryIds->push($best->category_id);
        }

        // Add the strongest real provider category for every service type on
        // every platform. This fills important gaps (for example Telegram
        // reactions) without exposing the provider's entire 20k-service feed.
        foreach ($eligible->groupBy(fn (Service $service) => $this->platform($service).'|'.$this->serviceKind($service)) as $key => $group) {
            if (str_ends_with((string) $key, '|other')) continue;
            $bestCategory = $group->groupBy('category_id')
                ->sortByDesc(fn (Collection $items) => $items->max(fn (Service $service) => $this->score($service)))
                ->keys()->first();
            if ($bestCategory !== null) $selectedCategoryIds->push($bestCategory);
        }

        // Keep two individually ranked choices for each useful platform/service
        // type. Category-only selection could previously choose a broad category
        // whose top three entries were all views, leaving valid comments,
        // reactions, followers, or saves invisible to customers.
        $coverageSelections = collect();
        foreach ($eligible->groupBy(fn (Service $service) => $this->platform($service).'|'.$this->serviceKind($service)) as $key => $group) {
            if (str_ends_with((string) $key, '|other')) continue;

            $coverageSelections->push(...$group
                ->sortByDesc(fn (Service $service) => $this->score($service))
                ->take(2));
        }

        $selectedCategoryIds = $selectedCategoryIds->filter()->unique();
        $selected = collect();
        foreach ($eligible->whereIn('category_id', $selectedCategoryIds)->groupBy('category_id') as $group) {
            // Two or three useful choices per displayed category, where the
            // provider has that many valid services.
            $selected->push(...$group->sortByDesc(fn (Service $service) => $this->score($service))->take(3));
        }

        // Never hide a valid service that has already completed real customer orders.
        $selected = $selected
            ->merge($previouslyApprovedServices)
            ->merge($coverageSelections)
            ->merge($eligible->filter(fn (Service $service) =>
                $service->provider?->slug === 'jinglesmm'
                && (string) $service->provider_service_code === '3453'
            ))
            ->merge($eligible->where('completed_orders_count', '>', 0))
            ->unique('id')
            ->values();

        $this->table(['Provider', 'Platform', 'Selected'], $selected
            ->groupBy(fn (Service $service) => ($service->provider?->name ?? '#'.$service->provider_id).' / '.$this->platform($service))
            ->map(fn (Collection $items, string $label) => [$label, $this->platform($items->first()), $items->count()])
            ->values()->all());

        if (! $this->option('dry-run')) {
            $previouslyApproved = Service::query()->where('type', 'smm')
                ->where('metadata->catalog_approved', true)->pluck('id')->values()->all();
            $backupPath = 'backups/service-catalog-'.now()->format('Ymd-His').'.json';
            Storage::disk('local')->put($backupPath, json_encode([
                'created_at' => now()->toISOString(),
                'service_ids' => $previouslyApproved,
            ], JSON_PRETTY_PRINT));

            DB::table('services')->where('type', 'smm')->whereNotNull('provider_id')
                ->update(['metadata' => DB::raw("JSON_SET(COALESCE(metadata, JSON_OBJECT()), '$.catalog_approved', false)")]);
            DB::table('services')->whereIn('id', $selected->pluck('id'))
                ->update(['metadata' => DB::raw("JSON_SET(COALESCE(metadata, JSON_OBJECT()), '$.catalog_approved', true)")]);
            $smm->clearUserServiceCaches();
            $this->line('Previous catalogue backup: storage/app/private/'.$backupPath);
        }

        $chosenPlatforms = [];
        foreach ($selected as $service) {
            $chosenPlatforms[$this->platform($service)] = true;
        }
        $this->info('Selected '.$selected->count().' services in '.$selected->pluck('category_id')->unique()->count().' categories across '.count($chosenPlatforms).' platforms.');
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
        $name = strtolower($service->name.' '.($service->category?->name ?? ''));
        return $service->is_active
            && (float) $service->selling_price > 0
            && (float) $service->min_amount > 0
            && (float) $service->max_amount >= (float) $service->min_amount
            && trim((string) data_get($service->metadata, 'description')) !== ''
            && ! ($service->provider?->slug === 'jinglesmm'
                && in_array((string) $service->provider_service_code, ['3761', '3762'], true))
            && ! collect(self::REJECT)->contains(fn (string $term) => str_contains($name, $term));
    }

    private function serviceKind(Service $service): string
    {
        $haystack = strtolower($service->name.' '.($service->category?->name ?? ''));
        foreach (self::SERVICE_KINDS as $kind => $terms) {
            if (collect($terms)->contains(fn (string $term) => str_contains($haystack, $term))) return $kind;
        }
        return 'other';
    }

    private function platform(Service $service): string
    {
        $haystack = strtolower($service->name.' '.($service->category?->name ?? ''));
        $aliases = [
            'rednote' => ['red note', 'rednote', 'xiaohongshu'],
            'coinmarketcap' => ['coinmarketcap'],
            'soundcloud' => ['soundcloud'],
        ];
        foreach ($aliases as $platform => $terms) {
            if (collect($terms)->contains(fn (string $term) => str_contains($haystack, $term))) return $platform;
        }
        foreach (['instagram', 'facebook', 'tiktok', 'youtube', 'telegram', 'spotify', 'crypto', 'google', 'twitter', 'twitch', 'website', 'linkedin', 'traffic', 'threads', 'discord', 'seo', 'reddit', 'pinterest', 'whatsapp', 'kwai', 'kick', 'rutube', 'jaco', 'quora'] as $platform) {
            if (str_contains($haystack, $platform)) return $platform;
        }
        return strtolower((string) data_get($service->metadata, 'platform', 'other')) ?: 'other';
    }

    private function score(Service $service): float
    {
        $metadata = $service->metadata ?? [];
        $verifiedPreference = $service->provider?->slug === 'jinglesmm'
            && (string) $service->provider_service_code === '3453' ? 500000 : 0;

        return ($service->completed_orders_count * 1000000)
            + $verifiedPreference
            + (! empty($metadata['description']) ? 10000 : 0)
            + (! empty($metadata['refill']) ? 500 : 0)
            + (! empty($metadata['cancel']) ? 250 : 0)
            + max(0, 100 - log10(max(1, (float) $service->selling_price)) * 10);
    }
}
