<?php

namespace App\Console\Commands;

use App\Models\Setting;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Illuminate\Validation\Rules\Password;

class SetAdminCredentials extends Command
{
    protected $signature = 'admin:credentials:set
        {--username= : Temporary administrator username}
        {--acknowledge-production : Confirm the owner intentionally runs this on production}';

    protected $description = 'Owner-controlled recovery: set temporary admin credentials and require replacement at first login';

    public function handle(): int
    {
        if (! $this->input->isInteractive()) {
            $this->error('This recovery command must be run interactively by the owner.');

            return self::FAILURE;
        }

        if (app()->environment('production') && ! $this->option('acknowledge-production')) {
            $this->error('Production use requires --acknowledge-production and owner review.');

            return self::FAILURE;
        }

        $username = trim((string) ($this->option('username') ?: $this->ask('Temporary administrator username')));
        $password = (string) $this->secret('Temporary administrator password');
        $confirmation = (string) $this->secret('Confirm temporary password');

        $validator = Validator::make([
            'username' => $username,
            'password' => $password,
            'password_confirmation' => $confirmation,
        ], [
            'username' => ['required', 'string', 'min:3', 'max:50'],
            'password' => ['required', 'confirmed', Password::min(12)->mixedCase()->numbers()->symbols()],
        ]);

        if ($validator->fails()) {
            foreach ($validator->errors()->all() as $error) {
                $this->error($error);
            }

            return self::FAILURE;
        }

        if (! $this->confirm('Set temporary credentials and invalidate all existing administrator sessions?')) {
            $this->warn('No changes made.');

            return self::FAILURE;
        }

        Setting::set('admin.username', $username);
        Setting::set('admin.password', Hash::make($password));
        Setting::set('admin.must_change_credentials', '1');
        Setting::set('admin.credentials_version', (string) Str::uuid());

        $this->info('Temporary credentials stored. The administrator must replace both username and password at first login.');

        return self::SUCCESS;
    }
}
