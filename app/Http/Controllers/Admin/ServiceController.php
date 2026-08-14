<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Service;
use App\Services\SmmProviderService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    public function __construct(private SmmProviderService $smm) {}

    public function index(): Response
    {
        $services = Service::query()
            ->with(['category:id,name', 'provider:id,name'])
            ->latest()
            ->paginate(200)
            ->withQueryString();

        return Inertia::render('Admin/Services', [
            'services'   => $services,
            'categories' => Category::orderBy('name')->get(['id', 'name', 'type']),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'category_id'   => ['nullable', 'integer', 'exists:categories,id'],
            'type'          => ['required', Rule::in(['smm', 'vtu'])],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'cost_price'    => ['nullable', 'numeric', 'min:0'],
            'min_amount'    => ['nullable', 'numeric', 'min:1'],
            'max_amount'    => ['nullable', 'numeric', 'min:1'],
            'description'   => ['nullable', 'string', 'max:1000'],
            'is_active'     => ['boolean'],
        ]);

        $slug = Str::slug($data['name']);
        $base = $slug;
        $i    = 2;
        while (Service::where('slug', $slug)->exists()) {
            $slug = "{$base}-{$i}";
            $i++;
        }

        Service::create([
            'name'          => $data['name'],
            'slug'          => $slug,
            'category_id'   => $data['category_id'] ?? null,
            'provider_id'   => null,
            'type'          => $data['type'],
            'selling_price' => $data['selling_price'],
            'cost_price'    => $data['cost_price'] ?? $data['selling_price'],
            'min_amount'    => $data['min_amount'] ?? 10,
            'max_amount'    => $data['max_amount'] ?? 1000000,
            'metadata'      => ['description' => $data['description'] ?? '', 'source' => 'manual'],
            'is_active'     => $data['is_active'] ?? true,
        ]);
        $this->smm->clearUserServiceCaches();

        return back()->with('success', 'Service created.');
    }

    public function update(Request $request, Service $service): RedirectResponse
    {
        $data = $request->validate([
            'name'          => ['required', 'string', 'max:255'],
            'category_id'   => ['nullable', 'integer', 'exists:categories,id'],
            'type'          => ['required', Rule::in(['smm', 'vtu'])],
            'selling_price' => ['required', 'numeric', 'min:0'],
            'cost_price'    => ['nullable', 'numeric', 'min:0'],
            'min_amount'    => ['nullable', 'numeric', 'min:1'],
            'max_amount'    => ['nullable', 'numeric', 'min:1'],
            'description'   => ['nullable', 'string', 'max:1000'],
            'is_active'     => ['boolean'],
        ]);

        $service->update([
            'name'          => $data['name'],
            'category_id'   => $data['category_id'] ?? $service->category_id,
            'type'          => $data['type'],
            'selling_price' => $data['selling_price'],
            'cost_price'    => $data['cost_price'] ?? $service->cost_price,
            'min_amount'    => $data['min_amount'] ?? $service->min_amount,
            'max_amount'    => $data['max_amount'] ?? $service->max_amount,
            'metadata'      => array_merge($service->metadata ?? [], ['description' => $data['description'] ?? '']),
            'is_active'     => $data['is_active'] ?? $service->is_active,
        ]);
        $this->smm->clearUserServiceCaches();

        return back()->with('success', 'Service updated.');
    }

    public function destroy(Service $service): RedirectResponse
    {
        $service->delete();
        $this->smm->clearUserServiceCaches();

        return back()->with('success', 'Service deleted.');
    }

    public function toggle(Service $service): RedirectResponse
    {
        $enabled = !$service->is_active;
        $service->update(['is_active' => $enabled]);
        $this->smm->clearUserServiceCaches();

        return back()->with('success', 'Service ' . ($enabled ? 'enabled' : 'disabled') . '.');
    }

    public function clear(): RedirectResponse
    {
        Service::query()->delete();
        $this->smm->clearUserServiceCaches();

        return back()->with('success', 'All services deleted.');
    }
}
