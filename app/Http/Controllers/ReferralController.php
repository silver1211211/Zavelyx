<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class ReferralController extends Controller
{
    public function index(Request $request): Response
    {
        $user = $request->user()->loadMissing('referrals');

        return Inertia::render('Referrals', [
            'referralCode' => $user->referral_code,
            'referralBonus' => (float) $user->referral_bonus,
            'referralCount' => $user->referrals()->count(),
            'referrals' => $user->referrals()
                ->latest()
                ->limit(20)
                ->get(['id', 'name', 'created_at']),
        ]);
    }
}
