<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\EmailVerificationOtp;
use App\Services\OtpService;
use Illuminate\Auth\Events\Verified;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class OtpVerificationController extends Controller
{
    public function __construct(private readonly OtpService $otpService) {}

    public function show(Request $request): RedirectResponse|Response
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        $otp = EmailVerificationOtp::where('user_id', $request->user()->id)->latest()->first();

        if (! $otp) {
            $otp = $this->otpService->generate($request->user());
        }

        return Inertia::render('Auth/VerifyEmail', [
            'status' => session('status'),
            'email' => $request->user()->email,
            'expires_at' => $otp->expires_at->toIso8601String(),
            'resend_available_at' => $otp->last_sent_at?->addSeconds(60)->toIso8601String(),
        ]);
    }

    public function verify(Request $request): RedirectResponse
    {
        $request->validate([
            'code' => ['required', 'string', 'size:6', 'regex:/^[0-9]{6}$/'],
        ]);

        $otp = EmailVerificationOtp::where('user_id', $request->user()->id)->latest()->first();

        if (! $otp || $otp->isExpired()) {
            return back()->withErrors(['code' => 'Your verification code has expired. Please request a new one.']);
        }

        if ($otp->hasExceededAttempts()) {
            return back()->withErrors(['code' => 'Too many incorrect attempts. Please request a new code.']);
        }

        if ($otp->code !== $request->code) {
            $attemptsAfter = $otp->attempts + 1;
            $otp->increment('attempts');
            $remaining = max(0, 5 - $attemptsAfter);

            return back()->withErrors([
                'code' => $remaining > 0
                    ? "Incorrect code. {$remaining} attempt(s) remaining."
                    : 'Incorrect code. No attempts remaining — please request a new code.',
            ]);
        }

        $request->user()->markEmailAsVerified();
        event(new Verified($request->user()));
        $otp->delete();

        return redirect()->intended(route('dashboard', absolute: false).'?verified=1');
    }

    public function resend(Request $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return redirect()->intended(route('dashboard', absolute: false));
        }

        $otp = EmailVerificationOtp::where('user_id', $request->user()->id)->latest()->first();

        if ($otp && ! $otp->canResend()) {
            return back()->withErrors(['resend' => 'Please wait before requesting another code.']);
        }

        $this->otpService->generate($request->user());

        return back()->with('status', 'verification-link-sent');
    }
}
