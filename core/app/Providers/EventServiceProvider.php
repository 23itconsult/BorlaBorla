<?php

namespace App\Providers;

use Illuminate\Foundation\Support\Providers\EventServiceProvider as ServiceProvider;
use SocialiteProviders\Manager\SocialiteWasCalled;
use SocialiteProviders\Apple\AppleExtendSocialite;

class EventServiceProvider extends ServiceProvider
{
    protected $listen = [
        SocialiteWasCalled::class => [
            AppleExtendSocialite::class.'@handle',
        ],
    ];

    public function boot(): void
    {
        parent::boot();
    }
}
