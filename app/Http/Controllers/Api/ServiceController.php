<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Repositories\ServiceRepository;
use Illuminate\Http\JsonResponse;

class ServiceController extends Controller
{
    public function __construct(private readonly ServiceRepository $services)
    {
    }

    public function index(): JsonResponse
    {
        return response()->json(['data' => $this->services->active()]);
    }
}
