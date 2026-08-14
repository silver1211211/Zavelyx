<?php

use App\Http\Controllers\Admin\AccessControlController as AdminAccessControlController;
use App\Http\Controllers\Admin\ApiSettingsController as AdminApiSettingsController;
use App\Http\Controllers\Admin\ContactSettingsController as AdminContactSettingsController;
use App\Http\Controllers\Admin\AuthController as AdminAuthController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\GatewayController as AdminGatewayController;
use App\Http\Controllers\Admin\GeneralSettingsController as AdminGeneralSettingsController;
use App\Http\Controllers\Admin\NotificationController as AdminNotificationController;
use App\Http\Controllers\Admin\NumberOrderController as AdminNumberOrderController;
use App\Http\Controllers\Admin\NumberProviderController as AdminNumberProviderController;
use App\Http\Controllers\Admin\OrderController as AdminOrderController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\ProviderController as AdminProviderController;
use App\Http\Controllers\Admin\SecuritySettingsController as AdminSecuritySettingsController;
use App\Http\Controllers\Admin\ServiceController as AdminServiceController;
use App\Http\Controllers\Admin\ThemeSettingsController as AdminThemeSettingsController;
use App\Http\Controllers\Admin\TicketController as AdminTicketController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\WebsiteSettingsController as AdminWebsiteSettingsController;
use App\Http\Controllers\ApiCenterController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\DepositController;
use App\Http\Controllers\InvoiceDepositController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\OrderController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ReferralController;
use App\Http\Controllers\ServiceController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\SmsController;
use App\Http\Controllers\TicketController;
use App\Http\Controllers\TransactionController;
use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

// ── Public routes ───────────────────────────────────────────────────────────

Route::get('/', function () {
    return Inertia::render('Welcome', [
        'canLogin'    => Route::has('login'),
        'canRegister' => Route::has('register'),
    ]);
});

Route::get('/terms',   fn () => Inertia::render('Terms'))->name('terms');
Route::get('/privacy', fn () => Inertia::render('Privacy'))->name('privacy');

// ── User dashboard ──────────────────────────────────────────────────────────

Route::middleware(['auth', 'user.active'])->group(function (): void {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');

    // Live balance polling (lightweight JSON — no Inertia overhead)
    Route::get('/wallet/balance', function (\Illuminate\Http\Request $request) {
        $wallet = $request->user()->loadMissing('wallet')->wallet;
        return response()->json([
            'balance'  => (float) ($wallet?->balance ?? 0),
            'currency' => $wallet?->currency ?? 'USD',
        ]);
    })->name('wallet.balance');

    // Orders
    Route::get('/orders',          [OrderController::class, 'index'])->name('orders.index');
    Route::get('/orders/new',      [OrderController::class, 'create'])->name('orders.create');
    Route::get('/orders/services', [OrderController::class, 'loadServices'])->name('orders.services');
    Route::post('/orders',         [OrderController::class, 'store'])->name('orders.store');

    // Services
    Route::get('/services', [ServiceController::class, 'index'])->name('services.index');

    // API Center
    Route::get('/api-center',              [ApiCenterController::class, 'index'])->name('api-center.index');
    Route::post('/api-center/regenerate',  [ApiCenterController::class, 'regenerate'])->name('api-center.regenerate');

    // Support tickets
    Route::get('/tickets',                      [TicketController::class, 'index'])->name('tickets.index');
    Route::post('/tickets',                     [TicketController::class, 'store'])->name('tickets.store');
    Route::get('/tickets/{ticket}',             [TicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/reply',      [TicketController::class, 'reply'])->name('tickets.reply');
    Route::patch('/tickets/{ticket}/close',     [TicketController::class, 'close'])->name('tickets.close');
    Route::patch('/tickets/{ticket}/reopen',    [TicketController::class, 'reopen'])->name('tickets.reopen');

    // Transactions & referrals
    Route::get('/transactions',        [TransactionController::class, 'index'])->name('transactions.index');
    Route::get('/transactions/export', [TransactionController::class, 'export'])->name('transactions.export');
    Route::get('/referrals',           [ReferralController::class, 'index'])->name('referrals.index');

    // ── Deposit ─────────────────────────────────────────────────────────────
    Route::get('/deposit',  [DepositController::class, 'index'])->name('deposit.index');
    Route::post('/deposit', [DepositController::class, 'create'])->name('deposit.create');

    // OxaPay coin-locked invoice flow
    Route::post('/deposit/invoice',               [InvoiceDepositController::class, 'store'])->name('deposit.invoice.create');
    Route::get('/deposit/invoice/{reference}',    [InvoiceDepositController::class, 'show'])->name('deposit.invoice.pay')
        ->where('reference', '[0-9a-f-]{36}');

    // Payment result pages (NOWPayments redirects user here after payment)
    Route::get('/payments/success', [PaymentController::class, 'success'])->name('payments.success');
    Route::get('/payments/cancel',  [PaymentController::class, 'cancel'])->name('payments.cancel');

    // User profile & settings
    Route::get('/settings',                             [SettingsController::class, 'index'])->name('settings.index');
    Route::patch('/settings/profile',                   [SettingsController::class, 'updateProfile'])->name('settings.profile');
    Route::post('/settings/avatar',                     [SettingsController::class, 'updateAvatar'])->name('settings.avatar');
    Route::patch('/settings/password',                  [SettingsController::class, 'updatePassword'])->name('settings.password');
    Route::patch('/settings/preferences',               [SettingsController::class, 'updatePreferences'])->name('settings.preferences');
    Route::patch('/settings/currency',                  [SettingsController::class, 'updateCurrency'])->name('settings.currency');
    Route::delete('/settings/sessions/{session}',       [SettingsController::class, 'revokeSession'])->name('settings.sessions.revoke');
    Route::delete('/settings/sessions',                 [SettingsController::class, 'revokeAllSessions'])->name('settings.sessions.revoke-all');

    // Notifications
    Route::get('/notifications',                             [NotificationController::class, 'index'])->name('notifications.index');
    Route::get('/notifications/data',                        [NotificationController::class, 'data'])->name('notifications.data');
    Route::post('/notifications/read-all',                   [NotificationController::class, 'markAllRead'])->name('notifications.read-all');
    Route::post('/notifications/{notification}/read',        [NotificationController::class, 'markRead'])->name('notifications.read');

    // SMS / OTP Numbers
    Route::get('/sms/buy',                          [SmsController::class, 'buy'])->name('sms.buy');
    Route::get('/sms/numbers',                      [SmsController::class, 'numbers'])->name('sms.numbers');
    Route::get('/sms/my-numbers',                   [SmsController::class, 'numbers'])->name('sms.my-numbers');
    Route::get('/sms/services',                     [SmsController::class, 'services'])->name('sms.services');
    Route::get('/sms/countries',                    [SmsController::class, 'countries'])->name('sms.countries');
    Route::get('/sms/country-stock',                [SmsController::class, 'countryStock'])->name('sms.country-stock');
    Route::get('/sms/products',                     [SmsController::class, 'products'])->name('sms.products');
    Route::post('/sms/buy',                         [SmsController::class, 'purchase'])->name('sms.purchase');
    Route::get('/sms/orders/{order}/poll',          [SmsController::class, 'poll'])->name('sms.poll');
    Route::post('/sms/orders/{order}/cancel',       [SmsController::class, 'cancel'])->name('sms.cancel');
    Route::post('/sms/orders/{order}/finish',       [SmsController::class, 'finish'])->name('sms.finish');

    Route::get('/profile',     [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile',   [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile',  [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// ── Admin panel ─────────────────────────────────────────────────────────────

Route::get('/admin', fn () => redirect('/admin/login'));

// Public admin auth routes
Route::prefix('admin')->name('admin.')->group(function (): void {
    Route::get('/login',  [AdminAuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AdminAuthController::class, 'login'])->name('login.post');
    Route::post('/logout',[AdminAuthController::class, 'logout'])->name('logout');
});

// Protected admin routes
Route::prefix('admin')->name('admin.')->middleware(['admin.auth', 'admin.ip'])->group(function (): void {

    Route::get('/dashboard', AdminDashboardController::class)->name('dashboard');

    // ── User management ──────────────────────────────────────────────────────
    Route::get('/users',                            [AdminUserController::class, 'index'])->name('users.index');
    Route::get('/users/{user}',                     [AdminUserController::class, 'show'])->name('users.show');
    Route::post('/users/{user}/adjust-balance',     [AdminUserController::class, 'adjustBalance'])->name('users.adjust-balance');
    Route::patch('/users/{user}/freeze',            [AdminUserController::class, 'freeze'])->name('users.freeze');
    Route::patch('/users/{user}/unfreeze',          [AdminUserController::class, 'unfreeze'])->name('users.unfreeze');

    // ── Order & service management ───────────────────────────────────────────
    Route::get('/orders',                           [AdminOrderController::class, 'index'])->name('orders.index');
    Route::patch('/orders/{order}/status',          [AdminOrderController::class, 'updateStatus'])->name('orders.update-status');
    Route::post('/orders/{order}/sync',             [AdminOrderController::class, 'syncStatus'])->name('orders.sync');
    Route::get('/services',                         [AdminServiceController::class, 'index'])->name('services.index');
    Route::post('/services',                        [AdminServiceController::class, 'store'])->name('services.store');
    Route::delete('/services/clear',               [AdminServiceController::class, 'clear'])->name('services.clear');
    Route::put('/services/{service}',               [AdminServiceController::class, 'update'])->name('services.update');
    Route::patch('/services/{service}/toggle',      [AdminServiceController::class, 'toggle'])->name('services.toggle');
    Route::delete('/services/{service}',            [AdminServiceController::class, 'destroy'])->name('services.destroy');
    Route::get('/providers',                        [AdminProviderController::class, 'index'])->name('providers.index');

    // ── Finance — Payment logs & manual actions ──────────────────────────────
    Route::get('/payments/export',                  [AdminPaymentController::class, 'export'])->name('payments.export');
    Route::get('/payments',                         [AdminPaymentController::class, 'index'])->name('payments.index');
    Route::get('/payments/{invoice}',               [AdminPaymentController::class, 'show'])->name('payments.show');
    Route::post('/payments/{invoice}/approve',      [AdminPaymentController::class, 'approve'])->name('payments.approve');
    Route::post('/payments/{invoice}/reject',       [AdminPaymentController::class, 'reject'])->name('payments.reject');
    Route::post('/payments/{invoice}/retry',        [AdminPaymentController::class, 'retry'])->name('payments.retry');
    Route::post('/payments/{invoice}/resend',       [AdminPaymentController::class, 'resendCallback'])->name('payments.resend');

    // ── Payment gateways (dynamic gateway architecture) ──────────────────────
    Route::get('/gateways',                         [AdminGatewayController::class, 'index'])->name('gateways.index');
    Route::post('/gateways',                        [AdminGatewayController::class, 'store'])->name('gateways.store');
    Route::put('/gateways/{gateway}',               [AdminGatewayController::class, 'update'])->name('gateways.update');
    Route::delete('/gateways/{gateway}',            [AdminGatewayController::class, 'destroy'])->name('gateways.destroy');
    Route::patch('/gateways/{gateway}/toggle',      [AdminGatewayController::class, 'toggle'])->name('gateways.toggle');
    Route::patch('/gateways/{gateway}/default',     [AdminGatewayController::class, 'setDefault'])->name('gateways.default');
    Route::post('/gateways/{gateway}/test',         [AdminGatewayController::class, 'test'])->name('gateways.test');

    // ── Number providers & orders ────────────────────────────────────────────
    Route::get('/number-providers',                          [AdminNumberProviderController::class, 'index'])->name('number-providers.index');
    Route::post('/number-providers',                         [AdminNumberProviderController::class, 'store'])->name('number-providers.store');
    Route::put('/number-providers/{numberProvider}',         [AdminNumberProviderController::class, 'update'])->name('number-providers.update');
    Route::delete('/number-providers/{numberProvider}',      [AdminNumberProviderController::class, 'destroy'])->name('number-providers.destroy');
    Route::patch('/number-providers/{numberProvider}/toggle',[AdminNumberProviderController::class, 'toggle'])->name('number-providers.toggle');
    Route::post('/number-providers/{numberProvider}/test',   [AdminNumberProviderController::class, 'test'])->name('number-providers.test');
    Route::post('/number-providers/{numberProvider}/sync',   [AdminNumberProviderController::class, 'sync'])->name('number-providers.sync');

    Route::get('/number-orders',                             [AdminNumberOrderController::class, 'index'])->name('number-orders.index');
    Route::get('/number-orders/{numberOrder}',               [AdminNumberOrderController::class, 'show'])->name('number-orders.show');

    // ── Notifications ─────────────────────────────────────────────────────────
    Route::get('/notifications',                               [AdminNotificationController::class, 'index'])->name('notifications.index');
    Route::post('/notifications',                              [AdminNotificationController::class, 'store'])->name('notifications.store');
    Route::put('/notifications/{broadcast}',                   [AdminNotificationController::class, 'update'])->name('notifications.update');
    Route::delete('/notifications/{broadcast}',                [AdminNotificationController::class, 'destroy'])->name('notifications.destroy');
    Route::post('/notifications/{broadcast}/send',             [AdminNotificationController::class, 'send'])->name('notifications.send');
    Route::get('/notifications/{broadcast}/analytics',         [AdminNotificationController::class, 'analytics'])->name('notifications.analytics');
    Route::get('/users/{user}/notifications',                  [AdminNotificationController::class, 'userNotifications'])->name('users.notifications');

    // ── Settings ─────────────────────────────────────────────────────────────
    Route::get('/settings', fn () => Inertia::render('Admin/Settings'))->name('settings.index');

    // Contact settings
    Route::get('/settings/contact',  [AdminContactSettingsController::class, 'index'])->name('settings.contact.index');
    Route::post('/settings/contact', [AdminContactSettingsController::class, 'save'])->name('settings.contact.save');

    // General settings (site, SEO, homepage)
    Route::get('/settings/general',           [AdminGeneralSettingsController::class, 'index'])->name('settings.general.index');
    Route::post('/settings/general/general',  [AdminGeneralSettingsController::class, 'saveGeneral'])->name('settings.general.save-general');
    Route::post('/settings/general/seo',      [AdminGeneralSettingsController::class, 'saveSeo'])->name('settings.general.save-seo');
    Route::post('/settings/general/homepage', [AdminGeneralSettingsController::class, 'saveHomepage'])->name('settings.general.save-homepage');
    Route::post('/settings/general/logo',              [AdminGeneralSettingsController::class, 'uploadLogo'])->name('settings.general.upload-logo');
    Route::post('/settings/general/favicon',           [AdminGeneralSettingsController::class, 'uploadFavicon'])->name('settings.general.upload-favicon');
    Route::post('/settings/general/branding/{type}',   [AdminGeneralSettingsController::class, 'uploadBranding'])->name('settings.general.upload-branding');
    Route::delete('/settings/general/branding/{type}', [AdminGeneralSettingsController::class, 'deleteBranding'])->name('settings.general.delete-branding');

    // Security settings
    Route::get('/settings/security',              [AdminSecuritySettingsController::class, 'index'])->name('settings.security.index');
    Route::post('/settings/security/platform',    [AdminSecuritySettingsController::class, 'savePlatform'])->name('settings.security.save-platform');
    Route::post('/settings/security/password',    [AdminSecuritySettingsController::class, 'savePassword'])->name('settings.security.save-password');
    Route::post('/settings/security/admin',        [AdminSecuritySettingsController::class, 'saveAdmin'])->name('settings.security.save-admin');
    Route::post('/settings/security/ip-whitelist', [AdminSecuritySettingsController::class, 'saveIpWhitelist'])->name('settings.security.save-ip-whitelist');

    // Theme settings
    Route::get('/settings/theme',  [AdminThemeSettingsController::class, 'index'])->name('settings.theme.index');
    Route::post('/settings/theme', [AdminThemeSettingsController::class, 'save'])->name('settings.theme.save');

    // Access Control (roles, permissions, user roles)
    Route::get('/settings/access-control',                              [AdminAccessControlController::class, 'index'])->name('settings.access-control.index');
    Route::post('/settings/access-control/roles',                       [AdminAccessControlController::class, 'createRole'])->name('access-control.roles.create');
    Route::delete('/settings/access-control/roles/{role}',             [AdminAccessControlController::class, 'deleteRole'])->name('access-control.roles.destroy');
    Route::post('/settings/access-control/roles/seed',                  [AdminAccessControlController::class, 'seedDefaultRoles'])->name('access-control.roles.seed');
    Route::post('/settings/access-control/users/{user}/assign-role',    [AdminAccessControlController::class, 'assignRole'])->name('access-control.users.assign-role');
    Route::delete('/settings/access-control/users/{user}/remove-role',  [AdminAccessControlController::class, 'removeRole'])->name('access-control.users.remove-role');

    // Tickets — admin support
    Route::get('/tickets',                          [AdminTicketController::class, 'index'])->name('tickets.index');
    Route::get('/tickets/{ticket}',                 [AdminTicketController::class, 'show'])->name('tickets.show');
    Route::post('/tickets/{ticket}/reply',          [AdminTicketController::class, 'reply'])->name('tickets.reply');
    Route::patch('/tickets/{ticket}/status',        [AdminTicketController::class, 'updateStatus'])->name('tickets.status');
    Route::patch('/tickets/{ticket}/priority',      [AdminTicketController::class, 'updatePriority'])->name('tickets.priority');
    Route::patch('/tickets/{ticket}/category',      [AdminTicketController::class, 'updateCategory'])->name('tickets.category');
    Route::patch('/tickets/{ticket}/pin',           [AdminTicketController::class, 'pin'])->name('tickets.pin');
    Route::patch('/tickets/{ticket}/read',          [AdminTicketController::class, 'markRead'])->name('tickets.read');
    Route::delete('/tickets/{ticket}',              [AdminTicketController::class, 'destroy'])->name('tickets.destroy');

    // Currency settings
    Route::get('/settings/currencies',                          [AdminWebsiteSettingsController::class, 'index'])->name('settings.currencies.index');
    Route::get('/website-settings',                             [AdminWebsiteSettingsController::class, 'index'])->name('website-settings.index');
    Route::post('/website-settings/currencies',                 [AdminWebsiteSettingsController::class, 'store'])->name('website-settings.currencies.store');
    Route::put('/website-settings/currencies/{currency}',       [AdminWebsiteSettingsController::class, 'update'])->name('website-settings.currencies.update');
    Route::delete('/website-settings/currencies/{currency}',    [AdminWebsiteSettingsController::class, 'destroy'])->name('website-settings.currencies.destroy');
    Route::patch('/website-settings/currencies/{currency}/toggle',  [AdminWebsiteSettingsController::class, 'toggle'])->name('website-settings.currencies.toggle');
    Route::patch('/website-settings/currencies/{currency}/default', [AdminWebsiteSettingsController::class, 'setDefault'])->name('website-settings.currencies.default');
    Route::post('/website-settings/currencies/refresh-rates',        [AdminWebsiteSettingsController::class, 'refreshRates'])->name('website-settings.currencies.refresh-rates');
    Route::post('/website-settings/currency-settings',               [AdminWebsiteSettingsController::class, 'saveCurrencySettings'])->name('website-settings.currency-settings.save');

    // SMM Provider API settings
    Route::get('/settings/api',                                 [AdminApiSettingsController::class, 'index'])->name('settings.api.index');
    Route::get('/api-settings',                                 [AdminApiSettingsController::class, 'index'])->name('api-settings.index');
    Route::post('/api-settings/providers',                      [AdminApiSettingsController::class, 'store'])->name('api-settings.providers.store');
    Route::put('/api-settings/providers/{provider}',            [AdminApiSettingsController::class, 'update'])->name('api-settings.providers.update');
    Route::delete('/api-settings/providers/{provider}',         [AdminApiSettingsController::class, 'destroy'])->name('api-settings.providers.destroy');
    Route::patch('/api-settings/providers/{provider}/toggle',   [AdminApiSettingsController::class, 'toggle'])->name('api-settings.providers.toggle');
    Route::post('/api-settings/providers/{provider}/test',      [AdminApiSettingsController::class, 'testConnection'])->name('api-settings.providers.test');
    Route::post('/api-settings/providers/{provider}/import',    [AdminApiSettingsController::class, 'importServices'])->name('api-settings.providers.import');
    Route::post('/api-settings/providers/{provider}/recalculate',[AdminApiSettingsController::class, 'recalculateMarkup'])->name('api-settings.providers.recalculate');
    Route::get('/api-settings/providers/{provider}/services',   [AdminApiSettingsController::class, 'services'])->name('api-settings.providers.services');
    Route::patch('/api-settings/services/{service}/toggle',     [AdminApiSettingsController::class, 'toggleService'])->name('api-settings.services.toggle');
});

require __DIR__.'/auth.php';
