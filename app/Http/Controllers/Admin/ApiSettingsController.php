<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Provider;
use App\Models\Service;
use App\Services\SmmProviderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ApiSettingsController extends Controller
{
    public function __construct(private SmmProviderService $smm) {}

    public function index(): Response
    {
        $providers = Provider::withCount('services')
            ->orderBy('priority')
            ->get()
            ->map(fn(Provider $p) => [
                'id' => $p->id,
                'name' => $p->name,
                'slug' => $p->slug,
                'type' => $p->type,
                'base_url' => $p->base_url,
                'is_active' => $p->is_active,
                'priority' => $p->priority,
                'markup_type' => $p->markup_type ?? 'percentage',
                'markup_value' => (float) ($p->markup_value ?? 0),
                'balance' => $p->balance,
                'last_synced_at' => $p->last_synced_at?->toISOString(),
                'services_count' => $p->services_count,
                // Never expose credentials/API key to frontend
            ]);

        return Inertia::render('Admin/ApiSettings', [
            'providers' => $providers,
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:100'],
            'base_url' => ['required', 'url', 'max:255'],
            'api_key' => ['required', 'string', 'max:500'],
            'markup_type' => ['required', Rule::in(['percentage', 'fixed'])],
            'markup_value' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ]);

        $slug = Str::slug($data['name']);
        $base = $slug;
        $i = 2;
        while (Provider::where('slug', $slug)->exists()) {
            $slug = $base . '-' . $i++;
        }

        Provider::create([
            'name' => $data['name'],
            'slug' => $slug,
            'type' => 'smm',
            'base_url' => $data['base_url'],
            'credentials' => ['api_key' => $data['api_key']],
            'markup_type' => $data['markup_type'],
            'markup_value' => $data['markup_value'],
            'is_active' => $data['is_active'] ?? false,
            'priority' => Provider::max('priority') + 1,
        ]);

        return back()->with('success', 'Provider added.');
    }

    public function update(Request $request, Provider $provider): RedirectResponse
    {
        $rules = [
            'name' => ['required', 'string', 'max:100'],
            'base_url' => ['required', 'url', 'max:255'],
            'markup_type' => ['required', Rule::in(['percentage', 'fixed'])],
            'markup_value' => ['required', 'numeric', 'min:0'],
            'is_active' => ['boolean'],
        ];

        if ($request->filled('api_key')) {
            $rules['api_key'] = ['string', 'max:500'];
        }

        $data = $request->validate($rules);

        $markupChanged = $data['markup_type'] !== $provider->markup_type
            || (float) $data['markup_value'] !== (float) $provider->markup_value;
        $activeChanged = (bool) ($data['is_active'] ?? $provider->is_active) !== (bool) $provider->is_active;

        $payload = [
            'name' => $data['name'],
            'base_url' => $data['base_url'],
            'markup_type' => $data['markup_type'],
            'markup_value' => $data['markup_value'],
            'is_active' => $data['is_active'] ?? $provider->is_active,
        ];

        if ($request->filled('api_key')) {
            $existing = $provider->credentials ?? [];
            $payload['credentials'] = array_merge($existing, ['api_key' => $data['api_key']]);
        }

        $provider->update($payload);

        $suffix = '';
        if ($markupChanged) {
            $count  = $this->smm->recalculateMarkup($provider->fresh());
            $suffix = " {$count} service prices updated.";
        } elseif ($activeChanged) {
            $this->smm->clearUserServiceCaches();
        }

        return back()->with('success', 'Provider updated.' . $suffix);
    }

    public function destroy(Provider $provider): RedirectResponse
    {
        $provider->services()->delete();
        $provider->delete();
        $this->smm->clearUserServiceCaches();

        return back()->with('success', 'Provider and all its services deleted.');
    }

    public function toggle(Provider $provider): RedirectResponse
    {
        $provider->update(['is_active' => !$provider->is_active]);
        $this->smm->clearUserServiceCaches();

        $label = $provider->is_active
            ? 'activated — all its services are now visible to users'
            : 'deactivated — all its services are now hidden from users';

        return back()->with('success', "Provider {$label}.");
    }

    public function recalculateMarkup(Provider $provider): RedirectResponse
    {
        $count = $this->smm->recalculateMarkup($provider);

        return back()->with('success', "Markup recalculated — {$count} service prices updated.");
    }

    public function testConnection(Provider $provider): \Illuminate\Http\JsonResponse
    {
        $result = $this->smm->testConnection($provider);

        if ($result['success'] && isset($result['balance'])) {
            $provider->update(['balance' => $result['balance']]);
        }

        return response()->json($result);
    }

    public function importServices(Provider $provider): RedirectResponse
    {
        try {
            $result = $this->smm->importServices($provider);
            $deactivated = (int) ($result['deactivated'] ?? 0);
            $suffix = $deactivated > 0 ? ", {$deactivated} hidden as stale" : '';

            return back()->with('success',
                "Import complete: {$result['imported']} new, {$result['updated']} updated{$suffix} ({$result['total']} total)."
            );
        } catch (\Throwable $e) {
            return back()->withErrors(['import' => 'Import failed: ' . $e->getMessage()]);
        }
    }

    public function services(Provider $provider): \Illuminate\Http\JsonResponse
    {
        $services = Service::where('provider_id', $provider->id)
            ->with('category:id,name')
            ->orderBy('name')
            ->get(['id', 'name', 'category_id', 'provider_service_code', 'cost_price', 'selling_price', 'min_amount', 'max_amount', 'is_active']);

        return response()->json($services);
    }

    public function toggleService(Service $service): \Illuminate\Http\JsonResponse
    {
        $service->update(['is_active' => !$service->is_active]);
        $this->smm->clearUserServiceCaches();

        return response()->json(['is_active' => $service->is_active]);
    }
}
