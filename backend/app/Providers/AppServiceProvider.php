<?php

/*namespace App\Providers;

use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
   /* public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
   /* public function boot(): void
    {
        ResetPassword::createUrlUsing(function (object $notifiable, string $token) {
            return config('app.frontend_url')."/password-reset/$token?email={$notifiable->getEmailForPasswordReset()}";
        });
    }
}
*/



namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\URL; 
//mobile
use Laravel\Sanctum\Sanctum; 
use App\Models\Mobile\PersonalAccessToken;
use App\Models\Mobile\Event;
use App\Observers\EventObserver;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Cache\RateLimiting\Limit;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        //mobile
        Sanctum::usePersonalAccessTokenModel(PersonalAccessToken::class);
        Event::observe(EventObserver::class);
        \Log::info("EventObserver REGISTERED");

         RateLimiter::for('geofence', function ($request) {
        return Limit::perMinute(120)->by(
            $request->header('X-Device-Auth') ?? $request->ip()
        );
        });
        // mobile
        
        //web
        // Force HTTPS in production so redirects use https://
        if (app()->environment('production')) {
            URL::forceScheme('https');
            URL::forceRootUrl(config('app.url')); // uses your APP_URL=https://...
        }
    }
    
}
