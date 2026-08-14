<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Providers\ProviderService;
use Inertia\Inertia;
use Inertia\Response;

class ProviderController extends Controller
{
    public function __construct(private readonly ProviderService $providers)
    {
    }

    public function index(): Response
    {
        return Inertia::render('Admin/Providers', [
            'providers' => $this->providers->paginateForAdmin(),
        ]);
    }
}
