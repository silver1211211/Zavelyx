<?php

namespace App\Services;

use App\Mail\OtpVerificationMail;
use App\Models\EmailVerificationOtp;
use App\Models\User;
use Illuminate\Support\Facades\Mail;

class OtpService
{
    public function generate(User $user): EmailVerificationOtp
    {
        EmailVerificationOtp::where('user_id', $user->id)->delete();

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $otp = EmailVerificationOtp::create([
            'user_id' => $user->id,
            'code' => $code,
            'expires_at' => now()->addMinutes(10),
            'last_sent_at' => now(),
        ]);

        Mail::to($user->email)->send(new OtpVerificationMail($user, $code));

        return $otp;
    }
}
