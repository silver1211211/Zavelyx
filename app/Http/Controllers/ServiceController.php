<?php

namespace App\Http\Controllers;

use App\Models\Service;
use Illuminate\Support\Facades\Cache;
use Inertia\Inertia;
use Inertia\Response;

class ServiceController extends Controller
{
    public function index(): Response
    {
        $services = Cache::remember('services.index.smm', 600, fn () =>
            Service::available()
                ->where('services.type', 'smm')
                ->with('category:id,name,slug,icon')
                ->orderBy('category_id')
                ->orderBy('selling_price')
                ->limit(1000)
                ->get(['id', 'category_id', 'name', 'selling_price', 'min_amount', 'max_amount'])
        );

        return Inertia::render('Services/Index', [
            'services' => $services,
        ]);
    }
}
