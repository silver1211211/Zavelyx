<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Provider;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        $adminRole = Role::firstOrCreate(['name' => 'admin']);
        Role::firstOrCreate(['name' => 'customer']);
        Role::firstOrCreate(['name' => 'vendor']);
        Role::firstOrCreate(['name' => 'agent']);
        Role::firstOrCreate(['name' => 'affiliate']);

        $admin = User::firstOrCreate([
            'email' => 'admin@admin.com',
        ], [
            'name' => 'Platform Admin',
            'password' => Hash::make('1234'),
            'email_verified_at' => now(),
        ]);

        $admin->assignRole($adminRole);
        $admin->wallet()->firstOrCreate(['currency' => 'NGN']);

        $categories = collect([
            ['name' => 'Instagram Followers', 'type' => 'smm', 'icon' => 'instagram'],
            ['name' => 'TikTok Likes', 'type' => 'smm', 'icon' => 'heart'],
            ['name' => 'YouTube Views', 'type' => 'smm', 'icon' => 'youtube'],
            ['name' => 'Telegram Members', 'type' => 'smm', 'icon' => 'send'],
            ['name' => 'Facebook Engagement', 'type' => 'smm', 'icon' => 'thumbs-up'],
            ['name' => 'Twitter/X Services', 'type' => 'smm', 'icon' => 'at-sign'],
            ['name' => 'Airtime Recharge', 'type' => 'vtu', 'icon' => 'smartphone'],
            ['name' => 'SME Data Subscription', 'type' => 'vtu', 'icon' => 'radio'],
            ['name' => 'Corporate Data', 'type' => 'vtu', 'icon' => 'building'],
            ['name' => 'Electricity Bills', 'type' => 'vtu', 'icon' => 'zap'],
            ['name' => 'Cable TV', 'type' => 'vtu', 'icon' => 'tv'],
        ])->map(fn (array $category) => Category::firstOrCreate(
            ['slug' => Str::slug($category['name'])],
            $category + ['is_active' => true],
        ));

        $manualProvider = Provider::firstOrCreate([
            'slug' => 'manual-fulfillment',
        ], [
            'name' => 'Manual Fulfillment',
            'type' => 'internal',
            'is_active' => false,
            'priority' => 1,
        ]);

        $categories->each(function (Category $category) use ($manualProvider): void {
            Service::firstOrCreate([
                'slug' => $category->slug.'-starter',
            ], [
                'category_id' => $category->id,
                'provider_id' => $manualProvider->id,
                'name' => $category->name.' Starter',
                'type' => $category->type,
                'selling_price' => $category->type === 'smm' ? 1000 : 500,
                'cost_price' => $category->type === 'smm' ? 850 : 450,
                'metadata' => ['phase' => 'foundation', 'enabled_for_ordering' => false],
                'is_active' => true,
            ]);
        });
    }
}
