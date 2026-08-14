<?php

namespace App\Http\Controllers;

use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Laravel\Sanctum\PersonalAccessToken;

class ApiCenterController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user();
        $token = $user->tokens()->where('name', 'api-key')->latest()->first();

        return Inertia::render('ApiCenter', [
            'apiKey' => $token ? $token->id . '|' . '**hidden**' : null,
            'tokenCreatedAt' => $token?->created_at,
            'requestCount' => $user->tokens()->where('name', 'api-key')->count(),
            'lastUsed' => $token?->last_used_at,
        ]);
    }

    public function regenerate(Request $request): RedirectResponse
    {
        $user = $request->user();

        // Revoke old API tokens
        $user->tokens()->where('name', 'api-key')->delete();

        // Create new token and flash the plain text value once
        $token = $user->createToken('api-key');
        session()->flash('newApiKey', $token->plainTextToken);

        return redirect()->route('api-center.index');
    }
}
