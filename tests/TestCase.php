<?php

namespace Tests;

use App\Models\Collection;
use App\Models\OrganisationEvent;
use App\Models\Page;
use App\Models\Service;
use App\Models\Taxonomy;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Http\Response;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Testing\TestResponse;
use Laravel\Passport\Passport;

abstract class TestCase extends BaseTestCase
{
    use CreatesApplication;
    use RefreshDatabase;
    use WithFaker;

    /**
     * @var bool
     */
    protected static $testLogCleared = false;

    /**
     * @var bool
     */
    protected static $elasticsearchInitialised = false;

    /**
     * @var \Carbon\CarbonImmutable
     */
    protected $now;

    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        // Clear the cache.
        $this->artisan('cache:clear');

        // Disable the API throttle middleware.
        $this->withoutMiddleware('throttle');

        $this->clearLog();
        $this->setUpElasticsearch();

        $this->now = Date::now();
    }

    /**
     * Clean up the testing environment before the next test.
     *
     *
     * @throws \Throwable
     */
    protected function tearDown(): void
    {
        Storage::cloud()->deleteDirectory('files');

        $this->tearDownElasticsearch();

        parent::tearDown();
    }

    /**
     * Setup up the Faker instance.
     *
     * @return void
     */
    protected function setUpFaker()
    {
        $this->faker = $this->makeFaker(config('app.faker_locale'));
    }

    /**
     * Delete all the collection categories and pivot records.
     */
    protected function truncateCollectionCategories()
    {
        Collection::categories()->get()->each(function (Collection $collection) {
            $collection->collectionTaxonomies()->delete();
        });
        Collection::categories()->delete();
    }

    /**
     * Delete all the collection personas and pivot records.
     */
    protected function truncateCollectionPersonas()
    {
        Collection::personas()->get()->each(function (Collection $collection) {
            $collection->collectionTaxonomies()->delete();
        });
        Collection::personas()->delete();
    }

    /**
     * Delete all the collection organisation events and pivot records.
     */
    protected function truncateCollectionOrganisationEvents()
    {
        Collection::organisationEvents()->get()->each(function (Collection $collection) {
            $collection->collectionTaxonomies()->delete();
        });
        Collection::organisationEvents()->delete();
    }

    /**
     * Delete all the category taxonomy records.
     */
    protected function truncateTaxonomies()
    {
        Taxonomy::category()->children->each->delete();
    }

    /**
     * Clears the testing log file.
     */
    protected function clearLog()
    {
        if (! static::$testLogCleared) {
            file_put_contents(config('logging.channels.testing.path'), '');
            static::$testLogCleared = true;
        }
    }

    /**
     * Sets up the Elasticsearch indices.
     */
    protected function setUpElasticsearch()
    {
        if (! $this instanceof UsesElasticsearch) {
            Service::disableSearchSyncing();
            OrganisationEvent::disableSearchSyncing();
            Page::disableSearchSyncing();

            return;
        } else {
            Service::enableSearchSyncing();
            OrganisationEvent::enableSearchSyncing();
            Page::enableSearchSyncing();
        }

        if (! static::$elasticsearchInitialised) {
            $this->artisan('ck:reindex-elasticsearch');
            static::$elasticsearchInitialised = true;
        }
    }

    /**
     * Tears down the Elasticsearch indices.
     */
    protected function tearDownElasticsearch()
    {
        if (! $this instanceof UsesElasticsearch) {
            Service::disableSearchSyncing();
            OrganisationEvent::disableSearchSyncing();
            Page::disableSearchSyncing();

            return;
        } else {
            Service::enableSearchSyncing();
            OrganisationEvent::enableSearchSyncing();
            Page::enableSearchSyncing();
        }

        try {
            $this->artisan('scout:flush', ['model' => Service::class]);
            $this->artisan('scout:flush', ['model' => Page::class]);
            $this->artisan('scout:flush', ['model' => OrganisationEvent::class]);
        } catch (\Exception $exception) {
            // Do nothing.
        }
    }

    protected function dumpResponse(TestResponse $response)
    {
        dump(json_decode($response->getContent(), true));
    }

    /**
     * @return array|string
     */
    protected function getResponseContent(TestResponse $response, string $key = null)
    {
        $content = json_decode($response->getContent(), true);

        if ($key !== null) {
            return Arr::get($content, $key);
        }

        return $content;
    }

    /**
     * Fakes events except for models.
     */
    protected function fakeEvents()
    {
        $initialDispatcher = Event::getFacadeRoot();
        Event::fake();
        Model::setEventDispatcher($initialDispatcher);
    }

    /**
     * Approve an update request
     *
     * @param  string  $updateRequestId
     * @return array
     **/
    public function approveUpdateRequest($updateRequestId)
    {
        $user = Auth::user();

        Passport::actingAs(User::factory()->create()->makeSuperAdmin());

        $response = $this->json('PUT', "/core/v1/update-requests/{$updateRequestId}/approve");

        $response->assertStatus(Response::HTTP_OK);

        Passport::actingAs($user);

        return $response->json();
    }
}
