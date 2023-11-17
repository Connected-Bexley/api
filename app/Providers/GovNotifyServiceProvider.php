<?php

namespace App\Providers;

use GuzzleHttp\Client as HttpClient;
use Illuminate\Support\ServiceProvider;

class GovNotifyServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $this->app->singleton(\Alphagov\Notifications\Client::class, function () {
            return new \Alphagov\Notifications\Client([
                'apiKey' => config('gov_uk_notify.gov_notify_api_key'),
                'httpClient' => new HttpClient(),
            ]);
        });
    }

    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }
}
