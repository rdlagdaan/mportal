<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use App\Models\Assets\Asset;
use App\Policies\AssetPolicy;

class AuthServiceProvider extends ServiceProvider
{
    protected $policies = [
        Asset::class => AssetPolicy::class,
    ];

    public function boot(): void
    {
        $this->registerPolicies();
    }
}
