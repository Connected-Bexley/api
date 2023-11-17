<?php

namespace App\Providers;

use App\Http\Controllers\Passport\AuthorizationController;
use App\Models\Client;
use Illuminate\Contracts\Auth\StatefulGuard;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Laravel\Passport\Passport;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array
     */
    protected $policies = [
        'App\Model' => 'App\Policies\ModelPolicy',
    ];

    /**
     * Register any authentication / authorization services.
     */
    public function boot(): void
    {
        Passport::enableImplicitGrant();
        Passport::tokensExpireIn(Date::now()->addMonths(18));
        Passport::useClientModel(Client::class);

        $this->app->when(AuthorizationController::class)
            ->needs(StatefulGuard::class)
            ->give(fn () => Auth::guard(config('passport.guard', null)));
    }

    /**
     * Register any application services.
     */
    public function register(): void
    {
        Passport::ignoreMigrations();
    }
}
