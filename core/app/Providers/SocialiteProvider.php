<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use App\Services\AppleClientSecret;
use Laravel\Socialite\Facades\Socialite;

class SocialiteProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        //
            Socialite::extend('apple', function ($app) {
            $config = $app['config']['services.apple'];
            $config['client_secret'] = AppleClientSecret::generate();
            return Socialite::buildProvider(\SocialiteProviders\Apple\Provider::class, $config);
        });
    }
}
