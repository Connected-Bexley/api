<?php

namespace Tests\Feature;

use App\Events\EndpointHit;
use App\Models\Audit;
use App\Models\Collection;
use App\Models\CollectionTaxonomy;
use App\Models\File;
use App\Models\Organisation;
use App\Models\OrganisationEvent;
use App\Models\Service;
use App\Models\Taxonomy;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Laravel\Passport\Passport;
use Tests\TestCase;

class CollectionOrganisationEventsTest extends TestCase
{
    /**
     * Setup
     *
     **/
    protected function setUp(): void
    {
        parent::setUp();

        $organisationEventCollection1 = Collection::factory()->typeOrganisationEvent()->create();
        $organisationEventCollection2 = Collection::factory()->typeOrganisationEvent()->create();
        $organisationEvent1 = OrganisationEvent::factory()->create();
        $organisationEvent2 = OrganisationEvent::factory()->create();
        $taxonomys1 = Taxonomy::category()->children()->inRandomOrder()->limit(5)->get();
        $taxonomys2 = Taxonomy::category()->children()->inRandomOrder()->limit(5)->get();
        $organisationEventCollection1->syncCollectionTaxonomies($taxonomys1);
        $organisationEventCollection2->syncCollectionTaxonomies($taxonomys2);
        $organisationEvent1->syncTaxonomyRelationships($taxonomys1);
        $organisationEvent2->syncTaxonomyRelationships($taxonomys2);
    }
    /*
     * List all the organisation event collections.
     */

    public function test_guest_can_list_them()
    {
        $response = $this->json('GET', '/core/v1/collections/organisation-events');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCollection([
            'id',
            'slug',
            'name',
            'intro',
            'order',
            'enabled',
            'image_file_id',
            'sideboxes' => [
                '*' => [
                    'title',
                    'content',
                ],
            ],
            'category_taxonomies' => [
                '*' => [
                    'id',
                    'parent_id',
                    'name',
                    'created_at',
                    'updated_at',
                ],
            ],
            'created_at',
            'updated_at',
        ]);
    }

    /*
     * List all the organisation event collections.
     */

    public function test_guest_can_list_all_of_them()
    {
        $response = $this->json('GET', '/core/v1/collections/organisation-events/all');

        $collectionOrganisationEventCount = Collection::organisationEvents()->count();

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure([
            'data' => [
                [
                    'id',
                    'name',
                    'intro',
                    'image_file_id',
                    'order',
                    'enabled',
                    'sideboxes' => [
                        '*' => [
                            'title',
                            'content',
                        ],
                    ],
                    'category_taxonomies' => [
                        '*' => [
                            'id',
                            'parent_id',
                            'name',
                            'created_at',
                            'updated_at',
                        ],
                    ],
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);
        $response->assertJsonCount($collectionOrganisationEventCount, 'data');
    }

    public function test_guest_can_list_enabled_and_disabled_collections()
    {
        $disabledCollection = Collection::organisationEvents()->first();

        $disabledCollection->disable()->save();

        $enabledCollection = Collection::organisationEvents()->offset(1)->first();

        $enabledCollection->enable()->save();

        $response = $this->json('GET', '/core/v1/collections/organisation-events');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $disabledCollection->id,
            'enabled' => false,
        ]);

        $response->assertJsonFragment([
            'id' => $enabledCollection->id,
            'enabled' => true,
        ]);
    }

    public function test_guest_can_list_all_enabled_and_disabled_collections()
    {
        $disabledCollection = Collection::organisationEvents()->first();

        $disabledCollection->disable()->save();

        $enabledCollection = Collection::organisationEvents()->offset(1)->first();

        $enabledCollection->enable()->save();

        $response = $this->json('GET', '/core/v1/collections/organisation-events/all');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $disabledCollection->id,
            'enabled' => false,
        ]);

        $response->assertJsonFragment([
            'id' => $enabledCollection->id,
            'enabled' => true,
        ]);
    }

    public function test_audit_created_when_listed()
    {
        $this->fakeEvents();

        $this->json('GET', '/core/v1/collections/organisation-events');

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) {
            return $event->getAction() === Audit::ACTION_READ;
        });
    }

    /*
     * Create a collection persona.
     */

    public function test_guest_cannot_create_one()
    {
        $response = $this->json('POST', '/core/v1/collections/organisation-events');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_create_one()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceWorker($service);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/organisation-events');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_cannot_create_one()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceAdmin($service);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/organisation-events');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_cannot_create_one()
    {
        /**
         * @var \App\Models\Organisation $organisation
         * @var \App\Models\User $user
         */
        $organisation = Organisation::factory()->create();
        $user = User::factory()->create();
        $user->makeOrganisationAdmin($organisation);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/organisation-events');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_global_admin_cannot_create_one()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeGlobalAdmin();

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/organisation-events');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_super_admin_can_create_one()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $randomCategory = Taxonomy::category()->children()->inRandomOrder()->firstOrFail();

        $image = File::factory()->pendingAssignment()->create([
            'filename' => Str::random().'.svg',
            'mime_type' => 'image/svg+xml',
        ]);

        $base64Image = 'data:image/svg+xml;base64,'.base64_encode(Storage::disk('local')->get('/test-data/image.svg'));

        $image->uploadBase64EncodedFile($base64Image);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/organisation-events', [
            'name' => 'Test Organisation Event',
            'intro' => 'Lorem ipsum',
            'image_file_id' => $image->id,
            'order' => 1,
            'enabled' => true,
            'sideboxes' => [],
            'category_taxonomies' => [$randomCategory->id],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonResource([
            'id',
            'slug',
            'name',
            'intro',
            'image_file_id',
            'order',
            'enabled',
            'sideboxes' => [
                '*' => [
                    'title',
                    'content',
                ],
            ],
            'category_taxonomies' => [
                '*' => [
                    'id',
                    'parent_id',
                    'name',
                    'created_at',
                    'updated_at',
                ],
            ],
            'created_at',
            'updated_at',
        ]);
        $response->assertJsonFragment([
            'name' => 'Test Organisation Event',
            'intro' => 'Lorem ipsum',
            'order' => 1,
            'enabled' => true,
            'sideboxes' => [],
        ]);
        $response->assertJsonFragment([
            'id' => $randomCategory->id,
        ]);

        $collectionArray = $this->getResponseContent($response)['data'];
        $response = $this->get("/core/v1/collections/categories/{$collectionArray['id']}/image.svg");
        $this->assertEquals(Storage::disk('local')->get('/test-data/image.svg'), $response->content());
    }

    public function test_super_admin_can_create_a_disabled_one()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $randomCategory = Taxonomy::category()->children()->inRandomOrder()->firstOrFail();

        $image = File::factory()->pendingAssignment()->create([
            'filename' => Str::random().'.svg',
            'mime_type' => 'image/svg+xml',
        ]);

        $base64Image = 'data:image/svg+xml;base64,'.base64_encode(Storage::disk('local')->get('/test-data/image.svg'));

        $image->uploadBase64EncodedFile($base64Image);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/organisation-events', [
            'name' => 'Test Organisation Event',
            'intro' => 'Lorem ipsum',
            'image_file_id' => $image->id,
            'order' => 1,
            'enabled' => false,
            'sideboxes' => [
                [
                    'title' => 'Sidebox title',
                    'content' => 'Sidebox content',
                ],
            ],
            'category_taxonomies' => [$randomCategory->id],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);

        $response->assertJsonFragment([
            'name' => 'Test Organisation Event',
            'intro' => 'Lorem ipsum',
            'order' => 1,
            'enabled' => false,
            'sideboxes' => [
                [
                    'title' => 'Sidebox title',
                    'content' => 'Sidebox content',
                ],
            ],
        ]);
    }

    public function test_order_is_updated_when_created_at_beginning()
    {
        // Delete the existing seeded personas.
        $this->truncateCollectionOrganisationEvents();

        $base64Image = 'data:image/svg+xml;base64,'.base64_encode(Storage::disk('local')->get('/test-data/image.svg'));

        $image = File::factory()->pendingAssignment()->create([
            'filename' => Str::random().'.svg',
            'mime_type' => 'image/svg+xml',
        ]);

        $image->uploadBase64EncodedFile($base64Image);

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $first = Collection::create([
            'type' => Collection::TYPE_ORGANISATION_EVENT,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_ORGANISATION_EVENT,
            'slug' => 'second',
            'name' => 'Second',
            'order' => 2,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_ORGANISATION_EVENT,
            'slug' => 'third',
            'name' => 'Third',
            'order' => 3,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/organisation-events', [
            'name' => 'Test Organisation Event',
            'intro' => 'Lorem ipsum',
            'image_file_id' => $image->id,
            'order' => 1,
            'enabled' => true,
            'sideboxes' => [],
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $this->assertDatabaseHas((new Collection())->getTable(), ['order' => 1]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $first->id, 'order' => 2]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $second->id, 'order' => 3]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $third->id, 'order' => 4]);
    }

    public function test_order_is_updated_when_created_at_middle()
    {
        // Delete the existing seeded personas.
        $this->truncateCollectionOrganisationEvents();

        $base64Image = 'data:image/svg+xml;base64,'.base64_encode(Storage::disk('local')->get('/test-data/image.svg'));

        $image = File::factory()->pendingAssignment()->create([
            'filename' => Str::random().'.svg',
            'mime_type' => 'image/svg+xml',
        ]);

        $image->uploadBase64EncodedFile($base64Image);

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $first = Collection::create([
            'type' => Collection::TYPE_ORGANISATION_EVENT,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_ORGANISATION_EVENT,
            'slug' => 'second',
            'name' => 'Second',
            'order' => 2,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_ORGANISATION_EVENT,
            'slug' => 'third',
            'name' => 'Third',
            'order' => 3,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/organisation-events', [
            'name' => 'Test Organisation Event',
            'intro' => 'Lorem ipsum',
            'image_file_id' => $image->id,
            'order' => 2,
            'enabled' => true,
            'sideboxes' => [],
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $first->id, 'order' => 1]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['order' => 2]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $second->id, 'order' => 3]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $third->id, 'order' => 4]);
    }

    public function test_order_is_updated_when_created_at_end()
    {
        // Delete the existing seeded personas.
        $this->truncateCollectionOrganisationEvents();

        $base64Image = 'data:image/svg+xml;base64,'.base64_encode(Storage::disk('local')->get('/test-data/image.svg'));

        $image = File::factory()->pendingAssignment()->create([
            'filename' => Str::random().'.svg',
            'mime_type' => 'image/svg+xml',
        ]);

        $image->uploadBase64EncodedFile($base64Image);

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $first = Collection::create([
            'type' => Collection::TYPE_ORGANISATION_EVENT,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_ORGANISATION_EVENT,
            'slug' => 'second',
            'name' => 'Second',
            'order' => 2,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_ORGANISATION_EVENT,
            'slug' => 'third',
            'name' => 'Third',
            'order' => 3,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/organisation-events', [
            'name' => 'Test Organisation Event',
            'intro' => 'Lorem ipsum',
            'image_file_id' => $image->id,
            'order' => 4,
            'enabled' => true,
            'sideboxes' => [],
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $first->id, 'order' => 1]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $second->id, 'order' => 2]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $third->id, 'order' => 3]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['order' => 4]);
    }

    public function test_order_cannot_be_less_than_1_when_created()
    {
        // Delete the existing seeded personas.
        $this->truncateCollectionOrganisationEvents();

        $base64Image = 'data:image/svg+xml;base64,'.base64_encode(Storage::disk('local')->get('/test-data/image.svg'));

        $image = File::factory()->pendingAssignment()->create([
            'filename' => Str::random().'.svg',
            'mime_type' => 'image/svg+xml',
        ]);

        $image->uploadBase64EncodedFile($base64Image);

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/organisation-events', [
            'name' => 'Test Organisation Event',
            'intro' => 'Lorem ipsum',
            'image_file_id' => $image->id,
            'order' => 0,
            'enabled' => true,
            'sideboxes' => [],
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_order_cannot_be_greater_than_count_plus_1_when_created()
    {
        // Delete the existing seeded personas.
        $this->truncateCollectionOrganisationEvents();

        $base64Image = 'data:image/svg+xml;base64,'.base64_encode(Storage::disk('local')->get('/test-data/image.svg'));

        $image = File::factory()->pendingAssignment()->create([
            'filename' => Str::random().'.svg',
            'mime_type' => 'image/svg+xml',
        ]);

        $image->uploadBase64EncodedFile($base64Image);

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        Collection::create([
            'type' => Collection::TYPE_ORGANISATION_EVENT,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        Collection::create([
            'type' => Collection::TYPE_ORGANISATION_EVENT,
            'slug' => 'second',
            'name' => 'Second',
            'order' => 2,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/organisation-events', [
            'name' => 'Test Organisation Event',
            'intro' => 'Lorem ipsum',
            'image_file_id' => $image->id,
            'order' => 4,
            'enabled' => true,
            'sideboxes' => [],
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_audit_created_when_created()
    {
        $this->fakeEvents();

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $randomCategory = Taxonomy::category()->children()->inRandomOrder()->firstOrFail();

        $base64Image = 'data:image/svg+xml;base64,'.base64_encode(Storage::disk('local')->get('/test-data/image.svg'));

        $image = File::factory()->pendingAssignment()->create([
            'filename' => Str::random().'.svg',
            'mime_type' => 'image/svg+xml',
        ]);

        $image->uploadBase64EncodedFile($base64Image);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/organisation-events', [
            'name' => 'Test Organisation Event',
            'intro' => 'Lorem ipsum',
            'image_file_id' => $image->id,
            'order' => 1,
            'enabled' => true,
            'sideboxes' => [],
            'category_taxonomies' => [$randomCategory->id],
        ]);

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $response) {
            return ($event->getAction() === Audit::ACTION_CREATE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $this->getResponseContent($response)['data']['id']);
        });
    }

    /*
     * Get a specific organisation event collection.
     */

    public function test_guest_can_view_one()
    {
        $organisationEvent = Collection::organisationEvents()->inRandomOrder()->firstOrFail();

        $response = $this->json('GET', "/core/v1/collections/organisation-events/{$organisationEvent->id}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonResource([
            'id',
            'slug',
            'name',
            'intro',
            'order',
            'enabled',
            'sideboxes' => [
                '*' => [
                    'title',
                    'content',
                ],
            ],
            'category_taxonomies' => [
                '*' => [
                    'id',
                    'parent_id',
                    'name',
                    'created_at',
                    'updated_at',
                ],
            ],
            'created_at',
            'updated_at',
        ]);
        $response->assertJsonFragment([
            'id' => $organisationEvent->id,
            'slug' => $organisationEvent->slug,
            'name' => $organisationEvent->name,
            'intro' => $organisationEvent->meta['intro'],
            'order' => $organisationEvent->order,
            'enabled' => $organisationEvent->enabled,
            'sideboxes' => $organisationEvent->meta['sideboxes'],
            'created_at' => $organisationEvent->created_at->format(CarbonImmutable::ISO8601),
            'updated_at' => $organisationEvent->updated_at->format(CarbonImmutable::ISO8601),
        ]);
    }

    public function test_guest_can_view_one_by_slug()
    {
        $organisationEvent = Collection::organisationEvents()->inRandomOrder()->firstOrFail();

        $response = $this->json('GET', "/core/v1/collections/organisation-events/{$organisationEvent->slug}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonResource([
            'id',
            'slug',
            'name',
            'intro',
            'order',
            'enabled',
            'sideboxes' => [
                '*' => [
                    'title',
                    'content',
                ],
            ],
            'category_taxonomies' => [
                '*' => [
                    'id',
                    'parent_id',
                    'name',
                    'created_at',
                    'updated_at',
                ],
            ],
            'created_at',
            'updated_at',
        ]);
        $response->assertJsonFragment([
            'id' => $organisationEvent->id,
            'slug' => $organisationEvent->slug,
            'name' => $organisationEvent->name,
            'intro' => $organisationEvent->meta['intro'],
            'order' => $organisationEvent->order,
            'enabled' => $organisationEvent->enabled,
            'sideboxes' => $organisationEvent->meta['sideboxes'],
            'created_at' => $organisationEvent->created_at->format(CarbonImmutable::ISO8601),
            'updated_at' => $organisationEvent->updated_at->format(CarbonImmutable::ISO8601),
        ]);
    }

    public function test_audit_created_when_viewed()
    {
        $this->fakeEvents();

        $organisationEvent = Collection::organisationEvents()->inRandomOrder()->firstOrFail();

        $this->json('GET', "/core/v1/collections/organisation-events/{$organisationEvent->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($organisationEvent) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getModel()->id === $organisationEvent->id);
        });
    }

    /*
     * Update a specific organisation event collection.
     */

    public function test_guest_cannot_update_one()
    {
        $organisationEvent = Collection::organisationEvents()->inRandomOrder()->firstOrFail();

        $response = $this->json('PUT', "/core/v1/collections/organisation-events/{$organisationEvent->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_update_one()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceWorker($service);
        $organisationEvent = Collection::organisationEvents()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/organisation-events/{$organisationEvent->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_cannot_update_one()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceAdmin($service);
        $organisationEvent = Collection::organisationEvents()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/organisation-events/{$organisationEvent->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_cannot_update_one()
    {
        /**
         * @var \App\Models\Organisation $organisation
         * @var \App\Models\User $user
         */
        $organisation = Organisation::factory()->create();
        $user = User::factory()->create();
        $user->makeOrganisationAdmin($organisation);
        $organisationEvent = Collection::organisationEvents()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/organisation-events/{$organisationEvent->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_global_admin_can_update_one()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeGlobalAdmin();
        $organisationEvent = Collection::organisationEvents()->inRandomOrder()->firstOrFail();
        $taxonomy = Taxonomy::category()->children()->inRandomOrder()->firstOrFail();

        $base64Image = 'data:image/svg+xml;base64,'.base64_encode(Storage::disk('local')->get('/test-data/image.svg'));

        $image = File::factory()->pendingAssignment()->create([
            'filename' => Str::random().'.svg',
            'mime_type' => 'image/svg+xml',
        ]);

        $image->uploadBase64EncodedFile($base64Image);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/organisation-events/{$organisationEvent->id}", [
            'name' => 'Test Organisation Event',
            'intro' => 'Lorem ipsum',
            'image_file_id' => $image->id,
            'order' => 1,
            'enabled' => true,
            'sideboxes' => [],
            'category_taxonomies' => [$taxonomy->id],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonResource([
            'id',
            'slug',
            'name',
            'intro',
            'order',
            'enabled',
            'sideboxes' => [
                '*' => [
                    'title',
                    'content',
                ],
            ],
            'category_taxonomies' => [
                '*' => [
                    'id',
                    'parent_id',
                    'name',
                    'created_at',
                    'updated_at',
                ],
            ],
            'created_at',
            'updated_at',
        ]);
        $response->assertJsonFragment([
            'name' => 'Test Organisation Event',
            'intro' => 'Lorem ipsum',
            'order' => 1,
            'enabled' => true,
            'sideboxes' => [],
        ]);
        $response->assertJsonFragment([
            'id' => $taxonomy->id,
        ]);
    }

    public function test_global_admin_cannot_update_status()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeGlobalAdmin();
        $organisationEvent = Collection::organisationEvents()->inRandomOrder()->firstOrFail();
        $organisationEvent->enable()->save();
        $taxonomy = Taxonomy::category()->children()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/organisation-events/{$organisationEvent->id}", [
            'name' => 'Test Organisation Event',
            'intro' => 'Lorem ipsum',
            'order' => 1,
            'enabled' => false,
            'sideboxes' => [],
            'category_taxonomies' => [$taxonomy->id],
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_super_admin_can_update_status()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $organisationEvent = Collection::organisationEvents()->inRandomOrder()->firstOrFail();
        $organisationEvent->enable()->save();
        $taxonomy = Taxonomy::category()->children()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/organisation-events/{$organisationEvent->id}", [
            'name' => 'Test Organisation Event',
            'intro' => 'Lorem ipsum',
            'image_file_id' => $organisationEvent->meta['image_file_id'],
            'order' => 1,
            'enabled' => false,
            'sideboxes' => [],
            'category_taxonomies' => [$taxonomy->id],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonResource([
            'id',
            'slug',
            'name',
            'intro',
            'order',
            'enabled',
            'sideboxes' => [
                '*' => [
                    'title',
                    'content',
                ],
            ],
            'category_taxonomies' => [
                '*' => [
                    'id',
                    'parent_id',
                    'name',
                    'created_at',
                    'updated_at',
                ],
            ],
            'created_at',
            'updated_at',
        ]);
        $response->assertJsonFragment([
            'name' => 'Test Organisation Event',
            'intro' => 'Lorem ipsum',
            'order' => 1,
            'enabled' => false,
            'sideboxes' => [],
        ]);
        $response->assertJsonFragment([
            'id' => $taxonomy->id,
        ]);
    }

    public function test_order_is_updated_when_updated_to_beginning()
    {
        // Delete the existing seeded personas.
        $this->truncateCollectionOrganisationEvents();

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $first = Collection::create([
            'type' => Collection::TYPE_ORGANISATION_EVENT,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'image_file_id' => null,
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_ORGANISATION_EVENT,
            'slug' => 'second',
            'name' => 'Second',
            'order' => 2,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'image_file_id' => null,
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_ORGANISATION_EVENT,
            'slug' => 'third',
            'name' => 'Third',
            'order' => 3,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'image_file_id' => null,
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/organisation-events/{$third->id}", [
            'slug' => 'third',
            'name' => 'Third',
            'intro' => 'Lorem ipsum',
            'order' => 1,
            'enabled' => true,
            'sideboxes' => [],
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $first->id, 'order' => 2]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $second->id, 'order' => 3]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $third->id, 'order' => 1]);
    }

    public function test_order_is_updated_when_updated_to_middle()
    {
        // Delete the existing seeded personas.
        $this->truncateCollectionOrganisationEvents();

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $first = Collection::create([
            'type' => Collection::TYPE_ORGANISATION_EVENT,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'image_file_id' => null,
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_ORGANISATION_EVENT,
            'slug' => 'second',
            'name' => 'Second',
            'order' => 2,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'image_file_id' => null,
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_ORGANISATION_EVENT,
            'slug' => 'third',
            'name' => 'Third',
            'order' => 3,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'image_file_id' => null,
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/organisation-events/{$first->id}", [
            'slug' => 'first',
            'name' => 'First',
            'intro' => 'Lorem ipsum',
            'order' => 2,
            'enabled' => true,
            'sideboxes' => [],
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $first->id, 'order' => 2]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $second->id, 'order' => 1]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $third->id, 'order' => 3]);
    }

    public function test_order_is_updated_when_updated_to_end()
    {
        // Delete the existing seeded personas.
        $this->truncateCollectionOrganisationEvents();

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $first = Collection::create([
            'type' => Collection::TYPE_ORGANISATION_EVENT,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'image_file_id' => null,
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_ORGANISATION_EVENT,
            'slug' => 'second',
            'name' => 'Second',
            'order' => 2,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'image_file_id' => null,
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_ORGANISATION_EVENT,
            'slug' => 'third',
            'name' => 'Third',
            'order' => 3,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'image_file_id' => null,
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/organisation-events/{$first->id}", [
            'slug' => 'first',
            'name' => 'First',
            'intro' => 'Lorem ipsum',
            'order' => 3,
            'enabled' => true,
            'sideboxes' => [],
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $first->id, 'order' => 3]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $second->id, 'order' => 1]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $third->id, 'order' => 2]);
    }

    public function test_order_cannot_be_less_than_1_when_updated()
    {
        // Delete the existing seeded personas.
        $this->truncateCollectionOrganisationEvents();

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $organisationEvent = Collection::create([
            'type' => Collection::TYPE_ORGANISATION_EVENT,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'image_file_id' => null,
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/organisation-events/{$organisationEvent->id}", [
            'slug' => 'first',
            'name' => 'First',
            'intro' => 'Lorem ipsum',
            'order' => 0,
            'enabled' => true,
            'sideboxes' => [],
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_order_cannot_be_greater_than_count_plus_1_when_updated()
    {
        // Delete the existing seeded personas.
        $this->truncateCollectionOrganisationEvents();

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $organisationEvent = Collection::create([
            'type' => Collection::TYPE_ORGANISATION_EVENT,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'image_file_id' => null,
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/organisation-events/{$organisationEvent->id}", [
            'slug' => 'first',
            'name' => 'First',
            'intro' => 'Lorem ipsum',
            'order' => 2,
            'enabled' => true,
            'sideboxes' => [],
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_audit_created_when_updated()
    {
        $this->fakeEvents();

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $organisationEvent = Collection::organisationEvents()->inRandomOrder()->firstOrFail();
        $taxonomy = Taxonomy::category()->children()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/organisation-events/{$organisationEvent->id}", [
            'name' => 'Test Organisation Event',
            'intro' => 'Lorem ipsum',
            'image_file_id' => $organisationEvent->meta['image_file_id'],
            'order' => 1,
            'enabled' => true,
            'sideboxes' => [],
            'category_taxonomies' => [$taxonomy->id],
        ]);

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $organisationEvent) {
            return ($event->getAction() === Audit::ACTION_UPDATE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $organisationEvent->id);
        });
    }

    /*
     * Delete a specific organisation event collection.
     */

    public function test_guest_cannot_delete_one()
    {
        $organisationEvent = Collection::organisationEvents()->inRandomOrder()->firstOrFail();

        $response = $this->json('DELETE', "/core/v1/collections/organisation-events/{$organisationEvent->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_delete_one()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceWorker($service);
        $organisationEvent = Collection::organisationEvents()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/organisation-events/{$organisationEvent->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_cannot_delete_one()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceAdmin($service);
        $organisationEvent = Collection::organisationEvents()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/organisation-events/{$organisationEvent->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_cannot_delete_one()
    {
        /**
         * @var \App\Models\Organisation $organisation
         * @var \App\Models\User $user
         */
        $organisation = Organisation::factory()->create();
        $user = User::factory()->create();
        $user->makeOrganisationAdmin($organisation);
        $organisationEvent = Collection::organisationEvents()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/organisation-events/{$organisationEvent->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_global_admin_cannot_delete_one()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeGlobalAdmin();
        $organisationEvent = Collection::organisationEvents()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/organisation-events/{$organisationEvent->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_super_admin_can_delete_one()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $organisationEvent = Collection::organisationEvents()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/organisation-events/{$organisationEvent->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing((new Collection())->getTable(), ['id' => $organisationEvent->id]);
        $this->assertDatabaseMissing((new CollectionTaxonomy())->getTable(), ['collection_id' => $organisationEvent->id]);
    }

    public function test_order_is_updated_when_deleted_at_beginning()
    {
        // Delete the existing seeded personas.
        $this->truncateCollectionOrganisationEvents();

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $first = Collection::create([
            'type' => Collection::TYPE_ORGANISATION_EVENT,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_ORGANISATION_EVENT,
            'slug' => 'second',
            'name' => 'Second',
            'order' => 2,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_ORGANISATION_EVENT,
            'slug' => 'third',
            'name' => 'Third',
            'order' => 3,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/organisation-events/{$first->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing((new Collection())->getTable(), ['id' => $first->id]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $second->id, 'order' => 1]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $third->id, 'order' => 2]);
    }

    public function test_order_is_updated_when_deleted_at_middle()
    {
        // Delete the existing seeded personas.
        $this->truncateCollectionOrganisationEvents();

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $first = Collection::create([
            'type' => Collection::TYPE_ORGANISATION_EVENT,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_ORGANISATION_EVENT,
            'slug' => 'second',
            'name' => 'Second',
            'order' => 2,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_ORGANISATION_EVENT,
            'slug' => 'third',
            'name' => 'Third',
            'order' => 3,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/organisation-events/{$second->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing((new Collection())->getTable(), ['id' => $second->id]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $first->id, 'order' => 1]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $third->id, 'order' => 2]);
    }

    public function test_order_is_updated_when_deleted_at_end()
    {
        // Delete the existing seeded personas.
        $this->truncateCollectionOrganisationEvents();

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $first = Collection::create([
            'type' => Collection::TYPE_ORGANISATION_EVENT,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_ORGANISATION_EVENT,
            'slug' => 'second',
            'name' => 'Second',
            'order' => 2,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_ORGANISATION_EVENT,
            'slug' => 'third',
            'name' => 'Third',
            'order' => 3,
            'enabled' => true,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/organisation-events/{$third->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing((new Collection())->getTable(), ['id' => $third->id]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $first->id, 'order' => 1]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $second->id, 'order' => 2]);
    }

    public function test_audit_created_when_deleted()
    {
        $this->fakeEvents();

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $organisationEventCollection = Collection::organisationEvents()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $this->json('DELETE', "/core/v1/collections/organisation-events/{$organisationEventCollection->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $organisationEventCollection) {
            return ($event->getAction() === Audit::ACTION_DELETE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $organisationEventCollection->id);
        });
    }

    /*
     * Get a specific organisation event collection's image.
     */

    public function test_guest_can_view_image()
    {
        $organisationEventCollection = Collection::organisationEvents()->inRandomOrder()->firstOrFail();

        $response = $this->get("/core/v1/collections/organisation-events/{$organisationEventCollection->id}/image.svg");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'image/svg+xml');
    }

    public function test_audit_created_when_image_viewed()
    {
        $this->fakeEvents();

        $organisationEventCollection = Collection::organisationEvents()->inRandomOrder()->firstOrFail();

        $this->get("/core/v1/collections/organisation-events/{$organisationEventCollection->id}/image.png");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($organisationEventCollection) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getModel()->id === $organisationEventCollection->id);
        });
    }

    /*
     * Upload a specific organisation event collection's image.
     */

    public function test_super_admin_can_upload_image()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $randomCategory = Taxonomy::category()->children()->inRandomOrder()->firstOrFail();
        $image = Storage::disk('local')->get('/test-data/image.png');

        Passport::actingAs($user);

        $imageResponse = $this->json('POST', '/core/v1/files', [
            'is_private' => false,
            'mime_type' => 'image/png',
            'file' => 'data:image/png;base64,'.base64_encode($image),
        ]);

        $response = $this->json('POST', '/core/v1/collections/organisation-events', [
            'name' => 'Test Organisation Event',
            'intro' => 'Lorem ipsum',
            'order' => 1,
            'enabled' => true,
            'sideboxes' => [],
            'category_taxonomies' => [$randomCategory->id],
            'image_file_id' => $this->getResponseContent($imageResponse, 'data.id'),
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $collectionArray = $this->getResponseContent($response)['data'];
        $content = $this->get("/core/v1/collections/organisation-events/{$collectionArray['id']}/image.png")->content();
        $this->assertEquals($image, $content);
    }

    /*
     * Delete a specific organisation event collection's image.
     */

    public function test_super_admin_cannot_delete_image()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $organisationEventCollection = Collection::organisationEvents()->inRandomOrder()->firstOrFail();
        $meta = $organisationEventCollection->meta;
        $meta['image_file_id'] = File::factory()->create()->id;
        $organisationEventCollection->meta = $meta;
        $organisationEventCollection->save();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/organisation-events/{$organisationEventCollection->id}", [
            'name' => $organisationEventCollection->name,
            'intro' => $organisationEventCollection->meta['intro'],
            'order' => $organisationEventCollection->order,
            'enabled' => $organisationEventCollection->enabled,
            'sideboxes' => [],
            'category_taxonomies' => $organisationEventCollection->taxonomies()->pluck(table(Taxonomy::class, 'id')),
            'image_file_id' => null,
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $organisationEventCollection = $organisationEventCollection->fresh();
        $this->assertEquals($meta['image_file_id'], $organisationEventCollection->meta['image_file_id']);
    }
}
