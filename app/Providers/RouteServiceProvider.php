<?php

namespace App\Providers;

use App\Models\Collection;
use App\Models\Organisation;
use App\Models\OrganisationEvent;
use App\Models\Page;
use App\Models\Service;
use App\Models\Taxonomy;
use Illuminate\Foundation\Support\Providers\RouteServiceProvider as ServiceProvider;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Route;

class RouteServiceProvider extends ServiceProvider
{
    /**
     * The path to the "home" route for your application.
     *
     * @var string
     */
    public const HOME = '/';

    /**
     * Define your route model bindings, pattern filters, etc.
     */
    public function boot(): void
    {
        $this->routes(function () {
            $this->mapApiRoutes();

            $this->mapWebRoutes();

            $this->mapPassportRoutes();
        });
        // Resolve by ID first, then resort to slug.
        Route::bind('organisation', function ($value) {
            return Organisation::query()->find($value) ?? Organisation::query()->where('slug', '=', $value)->first() ?? abort(Response::HTTP_NOT_FOUND);
        });

        // Resolve by ID first, then resort to slug.
        Route::bind('organisation_event', function ($value) {
            return OrganisationEvent::query()->find($value) ?? OrganisationEvent::query()->where('slug', '=', $value)->first() ?? abort(Response::HTTP_NOT_FOUND);
        });

        // Resolve by ID first, then resort to slug.
        Route::bind('service', function ($value) {
            return Service::query()->find($value) ?? Service::query()->where('slug', '=', $value)->first() ?? abort(Response::HTTP_NOT_FOUND);
        });

        // Resolve by ID first, then resort to slug.
        Route::bind('page', function ($value) {
            return Page::query()->find($value) ?? Page::query()->where('slug', '=', $value)->first() ?? abort(Response::HTTP_NOT_FOUND);
        });

        // Resolve by ID first, then resort to slug.
        Route::bind('collection', function ($value) {
            return Collection::query()->find($value) ?? Collection::query()->where('slug', '=', $value)->first() ?? abort(Response::HTTP_NOT_FOUND);
        });

        // Resolve by ID first, then resort to slug.
        Route::bind('taxonomy', function ($value) {
            return Taxonomy::query()->find($value) ?? Taxonomy::query()->where('slug', $value)->first() ?? abort(Response::HTTP_NOT_FOUND);
        });
    }

    /**
     * Define the routes for the application.
     */

    /**
     * Define the "web" routes for the application.
     *
     * These routes all receive session state, CSRF protection, etc.
     */
    protected function mapWebRoutes()
    {
        Route::middleware('web')
            ->group(base_path('routes/web.php'));
    }

    /**
     * Define the "api" routes for the application.
     *
     * These routes are typically stateless.
     */
    protected function mapApiRoutes()
    {
        Route::middleware('api')
            ->group(base_path('routes/api.php'));
    }

    /**
     * Define the "passport" routes for the application.
     */
    protected function mapPassportRoutes()
    {
        Route::middleware(['web', 'auth'])
            ->group(base_path('routes/passport.php'));
    }
}
