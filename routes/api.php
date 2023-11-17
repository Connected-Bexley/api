<?php

use App\Http\Controllers\Core;
use App\Http\Controllers\MetaController;
use App\Http\Controllers\Passport;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| is assigned the "api" middleware group. Enjoy building your API!
|
 */

// OAuth.
Route::get('/oauth/clients', [Passport\ClientController::class, 'index']);

// Meta.
Route::post('/_exception', [MetaController::class, 'exception']);

// Core.
Route::prefix('/core/v1')
    ->name('core.v1.')
    ->group(
        function () {
            // Audits.
            Route::match(['GET', 'POST'], '/audits/index', [Core\V1\AuditController::class, 'index']);
            Route::apiResource('/audits', Core\V1\AuditController::class)
                ->only('index', 'show');

            // Collection Categories.
            Route::match(['GET', 'POST'], '/collections/categories/index', [Core\V1\CollectionCategoryController::class, 'index']);
            Route::get('/collections/categories/all', [Core\V1\CollectionCategoryController::class, 'index'])->name('collection-categories.all');
            Route::apiResource('/collections/categories', Core\V1\CollectionCategoryController::class)
                ->parameter('categories', 'collection')
                ->names([
                    'index' => 'collection-categories.index',
                    'store' => 'collection-categories.store',
                    'show' => 'collection-categories.show',
                    'update' => 'collection-categories.update',
                    'destroy' => 'collection-categories.destroy',
                ]);
            Route::get('/collections/categories/{collection}/image{suffix?}', Core\V1\CollectionCategory\ImageController::class)
                ->where('suffix', '.png|.jpg|.jpeg|.svg')
                ->name('collection-categories.image');

            // Collection Organisation Events.
            Route::match(['GET', 'POST'], '/collections/organisation-events/index', [Core\V1\CollectionOrganisationEventController::class, 'index']);
            Route::get('/collections/organisation-events/all', [Core\V1\CollectionOrganisationEventController::class, 'index'])->name('collection-organisation-events.all');
            Route::apiResource('/collections/organisation-events', Core\V1\CollectionOrganisationEventController::class)
                ->parameter('organisation-events', 'collection')
                ->names([
                    'index' => 'collection-organisation-events.index',
                    'store' => 'collection-organisation-events.store',
                    'show' => 'collection-organisation-events.show',
                    'update' => 'collection-organisation-events.update',
                    'destroy' => 'collection-organisation-events.destroy',
                ]);
            Route::get('/collections/organisation-events/{collection}/image{suffix?}', Core\V1\CollectionOrganisationEvent\ImageController::class)
                ->where('suffix', '.png|.jpg|.jpeg|.svg')
                ->name('collection-organisation-events.image');

            // Collection Personas.
            Route::match(['GET', 'POST'], '/collections/personas/index', [Core\V1\CollectionPersonaController::class, 'index']);
            Route::get('/collections/personas/all', [Core\V1\CollectionPersonaController::class, 'index'])->name('collection-personas.all');
            Route::apiResource('/collections/personas', Core\V1\CollectionPersonaController::class)
                ->parameter('personas', 'collection')
                ->names([
                    'index' => 'collection-personas.index',
                    'store' => 'collection-personas.store',
                    'show' => 'collection-personas.show',
                    'update' => 'collection-personas.update',
                    'destroy' => 'collection-personas.destroy',
                ]);
            Route::get('/collections/personas/{collection}/image{suffix?}', Core\V1\CollectionPersona\ImageController::class)
                ->where('suffix', '.png|.jpg|.jpeg|.svg')
                ->name('collection-personas.image');

            // Files.
            Route::apiResource('/files', Core\V1\FileController::class)
                ->only('store');

            // Locations.
            Route::match(['GET', 'POST'], '/locations/index', [Core\V1\LocationController::class, 'index']);
            Route::apiResource('/locations', Core\V1\LocationController::class);
            Route::get('/locations/{location}/image{suffix?}', Core\V1\Location\ImageController::class)
                ->where('suffix', '.png|.jpg|.jpeg|.svg')
                ->name('locations.image');

            // Notifications.
            Route::match(['GET', 'POST'], '/notifications/index', [Core\V1\NotificationController::class, 'index']);
            Route::apiResource('/notifications', Core\V1\NotificationController::class)
                ->only('index', 'show');

            // Organisations.
            Route::match(['GET', 'POST'], '/organisations/index', [Core\V1\OrganisationController::class, 'index']);
            Route::apiResource('/organisations', Core\V1\OrganisationController::class);
            Route::get('/organisations/{organisation}/logo{suffix?}', Core\V1\Organisation\LogoController::class)
                ->where('suffix', '.png|.jpg|.jpeg|.svg')
                ->name('organisations.logo');
            Route::post('/organisations/import', Core\V1\Organisation\ImportController::class)
                ->name('organisations.import');

            // Organisation Events.
            Route::match(['GET', 'POST'], '/organisation-events/index', [Core\V1\OrganisationEventController::class, 'index']);
            Route::apiResource('/organisation-events', Core\V1\OrganisationEventController::class);
            Route::get('/organisation-events/new/image{suffix?}', [Core\V1\OrganisationEvent\ImageController::class, 'showNew'])
                ->where('suffix', '.png|.jpg|.jpeg|.svg')
                ->name('organisation-events.image-new');
            Route::get('/organisation-events/{organisationEvent}/image{suffix?}', [Core\V1\OrganisationEvent\ImageController::class, 'show'])
                ->where('suffix', '.png|.jpg|.jpeg|.svg')
                ->name('organisation-events.image');
            Route::get('/organisation-events/{organisationEvent}/event.ics', Core\V1\OrganisationEvent\CalendarController::class);

            // Organisation Sign Up Forms.
            Route::apiResource('/organisation-sign-up-forms', Core\V1\OrganisationSignUpFormController::class)
                ->only('store');

            // Pages.
            Route::match(['GET', 'POST'], '/pages/index', [Core\V1\PageController::class, 'index']);
            Route::apiResource('/pages', Core\V1\PageController::class);
            Route::get('/pages/new/image{suffix?}', [Core\V1\Page\ImageController::class, 'showNew'])
                ->where('suffix', '.png|.jpg|.jpeg|.svg')
                ->name('pages.image-new');
            Route::get('/pages/{page}/image{suffix?}', [Core\V1\Page\ImageController::class, 'show'])
                ->where('suffix', '.png|.jpg|.jpeg|.svg')
                ->name('pages.image');

            // Page Feedbacks.
            Route::match(['GET', 'POST'], '/page-feedbacks/index', [Core\V1\PageFeedbackController::class, 'index']);
            Route::apiResource('/page-feedbacks', Core\V1\PageFeedbackController::class)
                ->only('index', 'store', 'show');

            // Referrals.
            Route::match(['GET', 'POST'], '/referrals/index', [Core\V1\ReferralController::class, 'index']);
            Route::apiResource('/referrals', Core\V1\ReferralController::class);

            // Report Schedules.
            Route::match(['GET', 'POST'], '/report-schedules/index', [Core\V1\ReportScheduleController::class, 'index']);
            Route::apiResource('/report-schedules', Core\V1\ReportScheduleController::class);

            // Reports.
            Route::match(['GET', 'POST'], '/reports/index', [Core\V1\ReportController::class, 'index']);
            Route::apiResource('/reports', Core\V1\ReportController::class)
                ->only('index', 'store', 'show', 'destroy');
            Route::get('/reports/{report}/download', [Core\V1\Report\DownloadController::class, 'show'])
                ->name('reports.download');

            // Search.
            Route::post('/search', Core\V1\SearchController::class)->name('search');
            Route::post('/search/collections/categories', Core\V1\Search\CollectionCategoryController::class)
                ->name('search.collections.categories');
            Route::post('/search/collections/personas', Core\V1\Search\CollectionPersonaController::class)
                ->name('search.collections.personas');
            Route::post('/search/pages', Core\V1\Search\PageController::class)->name('search.pages');
            Route::post('/search/events', Core\V1\Search\EventController::class)->name('search.events');

            // Service Locations.
            Route::match(['GET', 'POST'], '/service-locations/index', [Core\V1\ServiceLocationController::class, 'index']);
            Route::apiResource('/service-locations', Core\V1\ServiceLocationController::class);
            Route::get('/service-locations/{service_location}/image{suffix?}', Core\V1\ServiceLocation\ImageController::class)
                ->where('suffix', '.png|.jpg|.jpeg|.svg')
                ->name('service-locations.image');

            // Services.
            Route::match(['GET', 'POST'], '/services/index', [Core\V1\ServiceController::class, 'index']);
            Route::put('/services/disable-stale', Core\V1\Service\DisableStaleController::class)
                ->name('services.disable-stale');
            Route::apiResource('/services', Core\V1\ServiceController::class);
            Route::put('/services/{service}/refresh', Core\V1\Service\RefreshController::class)
                ->name('services.refresh');
            Route::get('/services/{service}/related', Core\V1\Service\RelatedController::class)
                ->name('services.related');
            Route::get('/services/new/logo{suffix?}', [Core\V1\Service\LogoController::class, 'showNew'])
                ->where('suffix', '.png|.jpg|.jpeg|.svg')
                ->name('services.logo-new');
            Route::get('/services/{service}/logo{suffix?}', [Core\V1\Service\LogoController::class, 'show'])
                ->where('suffix', '.png|.jpg|.jpeg|.svg')
                ->name('services.logo');
            Route::get('/services/{service}/gallery-items/{file}', Core\V1\Service\GalleryItemController::class)
                ->name('services.gallery-items');
            Route::post('/services/import', Core\V1\Service\ImportController::class)
                ->name('services.import');

            // Settings.
            Route::get('/settings', [Core\V1\SettingController::class, 'index'])
                ->name('settings.index');
            Route::put('/settings', [Core\V1\SettingController::class, 'update'])
                ->name('settings.update');
            Route::get('/settings/banner-image{suffix?}', Core\V1\Setting\BannerImageController::class)
                ->where('suffix', '.png|.jpg|.jpeg|.svg')
                ->name('settings.banner-image');

            // Status Updates.
            Route::match(['GET', 'POST'], '/status-updates/index', [Core\V1\StatusUpdateController::class, 'index']);
            Route::apiResource('/status-updates', Core\V1\StatusUpdateController::class);

            // Stop words.
            Route::get('/stop-words', [Core\V1\StopWordsController::class, 'index'])
                ->name('stop-words.index');
            Route::put('/stop-words', [Core\V1\StopWordsController::class, 'update'])
                ->name('stop-words.update');

            // Tags.
            Route::get('/tags', [Core\V1\TagController::class, 'index'])
                ->name('tags.index');

            // Taxonomy Categories.
            Route::match(['GET', 'POST'], '/taxonomies/categories/index', [Core\V1\TaxonomyCategoryController::class, 'index']);
            Route::apiResource('/taxonomies/categories', Core\V1\TaxonomyCategoryController::class)
                ->parameter('categories', 'taxonomy')
                ->names([
                    'index' => 'taxonomy-categories.index',
                    'store' => 'taxonomy-categories.store',
                    'show' => 'taxonomy-categories.show',
                    'update' => 'taxonomy-categories.update',
                    'destroy' => 'taxonomy-categories.destroy',
                ]);

            // Taxonomy Organisations.
            Route::match(['GET', 'POST'], '/taxonomies/organisations/index', [Core\V1\TaxonomyOrganisationController::class, 'index']);
            Route::apiResource('/taxonomies/organisations', Core\V1\TaxonomyOrganisationController::class)
                ->parameter('organisations', 'taxonomy')
                ->names([
                    'index' => 'taxonomy-organisations.index',
                    'store' => 'taxonomy-organisations.store',
                    'show' => 'taxonomy-organisations.show',
                    'update' => 'taxonomy-organisations.update',
                    'destroy' => 'taxonomy-organisations.destroy',
                ]);

            // Taxonomy Service Eligibility.
            Route::get('/taxonomies/service-eligibilities', [Core\V1\TaxonomyServiceEligibilityController::class, 'index']);
            Route::apiResource('/taxonomies/service-eligibilities', Core\V1\TaxonomyServiceEligibilityController::class)
                ->parameter('service-eligibilities', 'taxonomy')
                ->names([
                    'index' => 'taxonomy-service-eligibilities.index',
                    'store' => 'taxonomy-service-eligibilities.store',
                    'show' => 'taxonomy-service-eligibilities.show',
                    'update' => 'taxonomy-service-eligibilities.update',
                    'destroy' => 'taxonomy-service-eligibilities.destroy',
                ]);

            // Thesaurus.
            Route::get('/thesaurus', [Core\V1\ThesaurusController::class, 'index'])
                ->name('thesaurus.index');
            Route::put('/thesaurus', [Core\V1\ThesaurusController::class, 'update'])
                ->name('thesaurus.update');

            // Update Requests.
            Route::match(['GET', 'POST'], '/update-requests/index', [Core\V1\UpdateRequestController::class, 'index']);
            Route::apiResource('/update-requests', Core\V1\UpdateRequestController::class)
                ->only('index', 'show', 'destroy');
            Route::put('/update-requests/{update_request}/approve', [Core\V1\UpdateRequest\ApproveController::class, 'update'])
                ->name('update-requests.approve');

            // Users.
            Route::match(['GET', 'POST'], '/users/index', [Core\V1\UserController::class, 'index']);
            Route::get('/users/user', [Core\V1\UserController::class, 'user'])
                ->name('users.user');
            Route::delete('/users/user/sessions', [Core\V1\User\SessionController::class, 'destroy'])
                ->name('users.user.sessions.destroy');
            Route::apiResource('/users', Core\V1\UserController::class);
        }
    );
