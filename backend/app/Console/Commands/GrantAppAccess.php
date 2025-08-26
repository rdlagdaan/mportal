<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\User;
use App\Models\App;

class GrantAppAccess extends Command
{
    // Usage:
    // php artisan user:grant-app-access user@example.com MICRO
    // php artisan user:grant-app-access user@example.com OPENU --revoke
    protected $signature = 'user:grant-app-access {email} {app} {--revoke}';
    protected $description = 'Grant or revoke per-app access (LRWSIS, OPENU, MICRO) for a user';

    public function handle(): int
    {
        $email  = $this->argument('email');
        $code   = strtoupper($this->argument('app'));
        $revoke = (bool) $this->option('revoke');

        $user = User::where('email', $email)->first();
        if (!$user) {
            $this->error("User not found: {$email}");
            return 1;
        }

        $app = App::where('code', $code)->first();
        if (!$app) {
            $this->error("App not found: {$code}. Use one of: LRWSIS, OPENU, MICRO");
            return 1;
        }

        $rel = $user->appAccesses()->firstOrCreate(
            ['app_id' => $app->id],
            ['is_enabled' => true]
        );

        if ($revoke) {
            $rel->is_enabled = false;
            $rel->save();
            $this->info("Revoked {$code} from {$email}");
        } else {
            $rel->is_enabled = true;
            $rel->save();
            $this->info("Granted {$code} to {$email}");
        }

        return 0;
    }
}
