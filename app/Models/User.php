<?php

namespace App\Models;

use Database\Factories\UserFactory;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;
use Spatie\Permission\Traits\HasRoles;

class User extends Authenticatable implements MustVerifyEmail
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, HasRoles, Notifiable;

    protected $fillable = [
        'name', 'email', 'password', 'is_active',
        'avatar', 'phone', 'country', 'timezone',
        'account_level', 'last_active_at', 'preferences',
        'referral_code', 'referred_by', 'referral_bonus',
        'preferred_currency',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'last_active_at'    => 'datetime',
            'password'          => 'hashed',
            'is_active'         => 'boolean',
            'referral_bonus'    => 'decimal:2',
            'preferences'       => 'array',
        ];
    }

    public function wallet(): HasOne
    {
        return $this->hasOne(Wallet::class);
    }

    public function emailVerificationOtps(): HasMany
    {
        return $this->hasMany(EmailVerificationOtp::class);
    }

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class);
    }

    public function orders(): HasMany
    {
        return $this->hasMany(Order::class);
    }

    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    public function referrals(): HasMany
    {
        return $this->hasMany(User::class, 'referred_by');
    }

    public function notifications(): HasMany
    {
        return $this->hasMany(Notification::class);
    }

    public function loginActivities(): HasMany
    {
        return $this->hasMany(LoginActivity::class);
    }

    public function unreadNotificationsCount(): int
    {
        return $this->notifications()->unread()->active()->count();
    }

    public function touchLastActive(): void
    {
        $this->timestamps = false;
        $this->update(['last_active_at' => now()]);
        $this->timestamps = true;
    }

    public function getAccountLevelLabelAttribute(): string
    {
        return match ($this->account_level ?? 'basic') {
            'verified' => 'Verified',
            'premium'  => 'Premium',
            'vip'      => 'VIP',
            default    => 'Basic',
        };
    }
}
