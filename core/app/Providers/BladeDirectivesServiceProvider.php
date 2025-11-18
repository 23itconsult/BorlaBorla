<?php

namespace App\Providers;

use Illuminate\Support\ServiceProvider;
use Illuminate\Support\Facades\Blade;


class BladeDirectivesServiceProvider extends ServiceProvider
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

           $this->app->afterResolving('blade.compiler', function () {
        Blade::if('gs', function ($exp) {
            return gs($exp);
        });
    });
        // Blade::if('gs', function($exp){
        //     return gs($exp);
        // });
    }
}
