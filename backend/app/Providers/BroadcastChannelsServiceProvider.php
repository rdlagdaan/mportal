<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;

class BroadcastChannelsServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        // Only load channel definitions; we already have the auth route set up.
        require base_path('routes/channels.php');
    }
}
