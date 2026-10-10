<?php

use App\Models\Setting;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

it('fails closed when no admin password hash is configured', function () {
    Setting::set('admin.username', 'owner');
    Setting::set('admin.password', '');

    $this->post(route('admin.login.post'), ['username' => 'owner', 'password' => 'anything'])
        ->assertSessionHasErrors('username');

    $this->assertGuest();
    expect(session('admin_authenticated'))->not->toBeTrue();
});

it('requires temporary credentials to be replaced before dashboard access', function () {
    $hash = Hash::make('Temporary!Pass123');
    Setting::set('admin.username', 'temporary-owner');
    Setting::set('admin.password', $hash);
    Setting::set('admin.must_change_credentials', '1');

    $this->post(route('admin.login.post'), [
        'username' => 'temporary-owner',
        'password' => 'Temporary!Pass123',
    ])->assertRedirect(route('admin.credentials.edit'));

    $this->get(route('admin.dashboard'))->assertRedirect(route('admin.credentials.edit'));
});

it('changes both temporary credentials and rotates the session version', function () {
    $hash = Hash::make('Temporary!Pass123');
    Setting::set('admin.username', 'temporary-owner');
    Setting::set('admin.password', $hash);
    Setting::set('admin.must_change_credentials', '1');

    $this->post(route('admin.login.post'), [
        'username' => 'temporary-owner',
        'password' => 'Temporary!Pass123',
    ]);

    $this->post(route('admin.credentials.update'), [
        'current_password' => 'Temporary!Pass123',
        'username' => 'secured-owner',
        'password' => 'Different!Secure456',
        'password_confirmation' => 'Different!Secure456',
    ])->assertRedirect(route('admin.dashboard'));

    expect(Setting::get('admin.username'))->toBe('secured-owner')
        ->and(Hash::check('Different!Secure456', Setting::get('admin.password')))->toBeTrue()
        ->and(Setting::get('admin.must_change_credentials'))->toBe('0')
        ->and(session('admin_credentials_version'))->toBe(Setting::get('admin.credentials_version'));
});

it('rate limits repeated administrator login failures', function () {
    Setting::set('admin.username', 'owner');
    Setting::set('admin.password', Hash::make('Correct!Password123'));
    Setting::set('security.login_attempts_limit', '2');
    Setting::set('security.lockout_duration', '1');
    RateLimiter::clear('admin-login|owner|127.0.0.1');

    $credentials = ['username' => 'owner', 'password' => 'Wrong!Password123'];
    $this->post(route('admin.login.post'), $credentials);
    $this->post(route('admin.login.post'), $credentials);

    expect(RateLimiter::tooManyAttempts('admin-login|owner|127.0.0.1', 2))->toBeTrue();
});

it('invalidates an older administrator session after credentials rotate', function () {
    Setting::set('admin.username', 'owner');
    Setting::set('admin.password', Hash::make('Correct!Password123'));
    Setting::set('admin.credentials_version', 'new-version');

    $this->withSession([
        'admin_authenticated' => true,
        'admin_username' => 'owner',
        'admin_credentials_version' => 'old-version',
        'admin_last_activity_at' => now()->timestamp,
    ])->get(route('admin.dashboard'))->assertRedirect(route('admin.login'));
});
