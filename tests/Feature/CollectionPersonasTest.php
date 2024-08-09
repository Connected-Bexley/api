<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\File;
use App\Models\User;
use App\Models\Audit;
use App\Models\Service;
use App\Models\Taxonomy;
use App\Models\Collection;
use App\Events\EndpointHit;
use Carbon\CarbonImmutable;
use App\Models\Organisation;
use Illuminate\Http\Response;
use Laravel\Passport\Passport;
use App\Models\CollectionTaxonomy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

class CollectionPersonasTest extends TestCase
{
    /*
     * List all the persona collections.
     */

    /**
     * @test
     */
    public function guest_can_list_them(): void
    {
        $response = $this->json('GET', '/core/v1/collections/personas');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCollection([
            'id',
            'slug',
            'name',
            'intro',
            'subtitle',
            'order',
            'enabled',
            'homepage',
            'sideboxes' => [
                '*' => [
                    'title',
                    'content',
                ],
            ],
            'image',
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
     * List all the persona collections.
     */

    /**
     * @test
     */
    public function guest_can_list_all_of_them(): void
    {
        $response = $this->json('GET', '/core/v1/collections/personas/all');

        $collectionPersonaCount = Collection::personas()->count();

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure([
            'data' => [
                [
                    'id',
                    'name',
                    'intro',
                    'subtitle',
                    'order',
                    'enabled',
                    'homepage',
                    'sideboxes' => [
                        '*' => [
                            'title',
                            'content',
                        ],
                    ],
                    'image',
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
        $response->assertJsonCount($collectionPersonaCount, 'data');
    }

    /**
     * @test
     */
    public function guest_can_list_enabled_and_disabled_collections(): void
    {
        $disabledCollection = Collection::personas()->first();

        $disabledCollection->disable()->save();

        $enabledCollection = Collection::personas()->offset(1)->first();

        $enabledCollection->enable()->save();

        $response = $this->json('GET', '/core/v1/collections/personas');

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

    /**
     * @test
     */
    public function guest_can_list_all_enabled_and_disabled_collections(): void
    {
        $disabledCollection = Collection::personas()->first();

        $disabledCollection->disable()->save();

        $enabledCollection = Collection::personas()->offset(1)->first();

        $enabledCollection->enable()->save();

        $response = $this->json('GET', '/core/v1/collections/personas/all');

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

    /**
     * @test
     */
    public function audit_created_when_listed(): void
    {
        $this->fakeEvents();

        $this->json('GET', '/core/v1/collections/personas');

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) {
            return $event->getAction() === Audit::ACTION_READ;
        });
    }

    /*
     * Create a collection persona.
     */

    /**
     * @test
     */
    public function guest_cannot_create_one(): void
    {
        $response = $this->json('POST', '/core/v1/collections/personas');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @test
     */
    public function service_worker_cannot_create_one(): void
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceWorker($service);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/personas');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function service_admin_cannot_create_one(): void
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceAdmin($service);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/personas');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function organisation_admin_cannot_create_one(): void
    {
        /**
         * @var \App\Models\Organisation $organisation
         * @var \App\Models\User $user
         */
        $organisation = Organisation::factory()->create();
        $user = User::factory()->create();
        $user->makeOrganisationAdmin($organisation);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/personas');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function global_admin_cannot_create_one(): void
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeGlobalAdmin();

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/personas');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function super_admin_can_create_one(): void
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $randomCategory = Taxonomy::category()->children()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/personas', [
            'name' => 'Test Persona',
            'intro' => 'Lorem ipsum',
            'subtitle' => 'Subtitle here',
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'sideboxes' => [],
            'category_taxonomies' => [$randomCategory->id],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonResource([
            'id',
            'slug',
            'name',
            'intro',
            'subtitle',
            'order',
            'enabled',
            'homepage',
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
            'name' => 'Test Persona',
            'intro' => 'Lorem ipsum',
            'subtitle' => 'Subtitle here',
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'sideboxes' => [],
        ]);
        $response->assertJsonFragment([
            'id' => $randomCategory->id,
        ]);
    }

    /**
     * @test
     */
    public function super_admin_can_create_a_disabled_one(): void
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $randomCategory = Taxonomy::category()->children()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/personas', [
            'name' => 'Test Persona',
            'intro' => 'Lorem ipsum',
            'subtitle' => 'Subtitle here',
            'order' => 1,
            'enabled' => false,
            'homepage' => false,
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
            'name' => 'Test Persona',
            'intro' => 'Lorem ipsum',
            'subtitle' => 'Subtitle here',
            'order' => 1,
            'enabled' => false,
            'homepage' => false,
            'sideboxes' => [
                [
                    'title' => 'Sidebox title',
                    'content' => 'Sidebox content',
                ],
            ],
        ]);
    }

    /**
     * @test
     */
    public function super_admin_can_create_a_homepage_one(): void
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $randomCategory = Taxonomy::category()->children()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/personas', [
            'name' => 'Test Persona',
            'intro' => 'Lorem ipsum',
            'subtitle' => 'Subtitle here',
            'order' => 1,
            'enabled' => true,
            'homepage' => true,
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
            'name' => 'Test Persona',
            'intro' => 'Lorem ipsum',
            'subtitle' => 'Subtitle here',
            'order' => 1,
            'enabled' => true,
            'homepage' => true,
            'sideboxes' => [
                [
                    'title' => 'Sidebox title',
                    'content' => 'Sidebox content',
                ],
            ],
        ]);
    }

    /**
     * @test
     */
    public function order_is_updated_when_created_at_beginning(): void
    {
        // Delete the existing seeded personas.
        $this->truncateCollectionPersonas();

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $first = Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'subtitle' => 'Subtitle here',
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'second',
            'name' => 'Second',
            'order' => 2,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'subtitle' => 'Subtitle here',
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'third',
            'name' => 'Third',
            'order' => 3,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'subtitle' => 'Subtitle here',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/personas', [
            'name' => 'Test Persona',
            'intro' => 'Lorem ipsum',
            'subtitle' => 'Subtitle here',
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'sideboxes' => [],
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $this->assertDatabaseHas((new Collection())->getTable(), ['order' => 1]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $first->id, 'order' => 2]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $second->id, 'order' => 3]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $third->id, 'order' => 4]);
    }

    /**
     * @test
     */
    public function order_is_updated_when_created_at_middle(): void
    {
        // Delete the existing seeded personas.
        $this->truncateCollectionPersonas();

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $first = Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'subtitle' => 'Subtitle here',
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'second',
            'name' => 'Second',
            'order' => 2,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'subtitle' => 'Subtitle here',
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'third',
            'name' => 'Third',
            'order' => 3,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'subtitle' => 'Subtitle here',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/personas', [
            'name' => 'Test Persona',
            'intro' => 'Lorem ipsum',
            'subtitle' => 'Subtitle here',
            'order' => 2,
            'enabled' => true,
            'homepage' => false,
            'sideboxes' => [],
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $first->id, 'order' => 1]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['order' => 2]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $second->id, 'order' => 3]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $third->id, 'order' => 4]);
    }

    /**
     * @test
     */
    public function order_is_updated_when_created_at_end(): void
    {
        // Delete the existing seeded personas.
        $this->truncateCollectionPersonas();

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $first = Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'subtitle' => 'Subtitle here',
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'second',
            'name' => 'Second',
            'order' => 2,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'subtitle' => 'Subtitle here',
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'third',
            'name' => 'Third',
            'order' => 3,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'subtitle' => 'Subtitle here',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/personas', [
            'name' => 'Test Persona',
            'intro' => 'Lorem ipsum',
            'subtitle' => 'Subtitle here',
            'order' => 4,
            'enabled' => true,
            'homepage' => false,
            'sideboxes' => [],
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $first->id, 'order' => 1]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $second->id, 'order' => 2]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $third->id, 'order' => 3]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['order' => 4]);
    }

    /**
     * @test
     */
    public function order_cannot_be_less_than_1_when_created(): void
    {
        // Delete the existing seeded personas.
        $this->truncateCollectionPersonas();

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/personas', [
            'name' => 'Test Persona',
            'intro' => 'Lorem ipsum',
            'subtitle' => 'Subtitle here',
            'order' => 0,
            'enabled' => true,
            'homepage' => false,
            'sideboxes' => [],
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @test
     */
    public function order_cannot_be_greater_than_count_plus_1_when_created(): void
    {
        // Delete the existing seeded personas.
        $this->truncateCollectionPersonas();

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'subtitle' => 'Subtitle here',
                'sideboxes' => [],
            ],
        ]);
        Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'second',
            'name' => 'Second',
            'order' => 2,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'subtitle' => 'Subtitle here',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/personas', [
            'name' => 'Test Persona',
            'intro' => 'Lorem ipsum',
            'subtitle' => 'Subtitle here',
            'order' => 4,
            'enabled' => true,
            'homepage' => false,
            'sideboxes' => [],
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @test
     */
    public function audit_created_when_created(): void
    {
        $this->fakeEvents();

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $randomCategory = Taxonomy::category()->children()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/personas', [
            'name' => 'Test Persona',
            'intro' => 'Lorem ipsum',
            'subtitle' => 'Subtitle here',
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'sideboxes' => [],
            'category_taxonomies' => [$randomCategory->id],
        ]);

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $response) {
            return ($event->getAction() === Audit::ACTION_CREATE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $this->getResponseContent($response)['data']['id']);
        });
    }

    /**
     * @test
     */
    public function createCollectionWithUniqueSlugAsSuperAdmin201(): void
    {
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/personas', [
            'name' => 'Test Persona',
            'intro' => 'Lorem ipsum',
            'subtitle' => 'Subtitle here',
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'sideboxes' => [],
            'category_taxonomies' => [Taxonomy::category()->children()->inRandomOrder()->firstOrFail()->id],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);

        $this->assertDatabaseHas('collections', [
            'id' => $response->json('data.id'),
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'test-persona',
        ]);

        $response = $this->json('POST', '/core/v1/collections/personas', [
            'name' => 'Test Persona',
            'intro' => 'Lorem ipsum',
            'subtitle' => 'Subtitle here',
            'order' => 2,
            'enabled' => true,
            'homepage' => false,
            'sideboxes' => [],
            'category_taxonomies' => [Taxonomy::category()->children()->inRandomOrder()->firstOrFail()->id],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);

        $this->assertDatabaseHas('collections', [
            'id' => $response->json('data.id'),
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'test-persona-1',
        ]);
    }

    /*
     * Get a specific persona collection.
     */

    /**
     * @test
     */
    public function guest_can_view_one(): void
    {
        $persona = Collection::personas()->inRandomOrder()->firstOrFail();

        $response = $this->json('GET', "/core/v1/collections/personas/{$persona->id}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonResource([
            'id',
            'slug',
            'name',
            'intro',
            'subtitle',
            'order',
            'enabled',
            'homepage',
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
            'id' => $persona->id,
            'slug' => $persona->slug,
            'name' => $persona->name,
            'intro' => $persona->meta['intro'],
            'subtitle' => $persona->meta['subtitle'],
            'order' => $persona->order,
            'enabled' => $persona->enabled,
            'homepage' => $persona->homepage,
            'sideboxes' => $persona->meta['sideboxes'],
            'created_at' => $persona->created_at->format(CarbonImmutable::ISO8601),
            'updated_at' => $persona->updated_at->format(CarbonImmutable::ISO8601),
        ]);
    }

    /**
     * @test
     */
    public function guest_can_view_one_by_slug(): void
    {
        $persona = Collection::personas()->inRandomOrder()->firstOrFail();

        $response = $this->json('GET', "/core/v1/collections/personas/{$persona->slug}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonResource([
            'id',
            'slug',
            'name',
            'intro',
            'subtitle',
            'order',
            'enabled',
            'homepage',
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
            'id' => $persona->id,
            'slug' => $persona->slug,
            'name' => $persona->name,
            'intro' => $persona->meta['intro'],
            'subtitle' => $persona->meta['subtitle'],
            'order' => $persona->order,
            'enabled' => $persona->enabled,
            'homepage' => $persona->homepage,
            'sideboxes' => $persona->meta['sideboxes'],
            'created_at' => $persona->created_at->format(CarbonImmutable::ISO8601),
            'updated_at' => $persona->updated_at->format(CarbonImmutable::ISO8601),
        ]);
    }

    /**
     * @test
     */
    public function audit_created_when_viewed(): void
    {
        $this->fakeEvents();

        $persona = Collection::personas()->inRandomOrder()->firstOrFail();

        $this->json('GET', "/core/v1/collections/personas/{$persona->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($persona) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getModel()->id === $persona->id);
        });
    }

    /*
     * Update a specific persona collection.
     */

    /**
     * @test
     */
    public function guest_cannot_update_one(): void
    {
        $persona = Collection::personas()->inRandomOrder()->firstOrFail();

        $response = $this->json('PUT', "/core/v1/collections/personas/{$persona->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @test
     */
    public function service_worker_cannot_update_one(): void
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceWorker($service);
        $persona = Collection::personas()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/personas/{$persona->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function service_admin_cannot_update_one(): void
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceAdmin($service);
        $persona = Collection::personas()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/personas/{$persona->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function organisation_admin_cannot_update_one(): void
    {
        /**
         * @var \App\Models\Organisation $organisation
         * @var \App\Models\User $user
         */
        $organisation = Organisation::factory()->create();
        $user = User::factory()->create();
        $user->makeOrganisationAdmin($organisation);
        $persona = Collection::personas()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/personas/{$persona->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function global_admin_cannot_update_one(): void
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeGlobalAdmin();
        $persona = Collection::personas()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/personas/{$persona->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function super_admin_can_update_one(): void
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $persona = Collection::personas()->inRandomOrder()->firstOrFail();
        $taxonomy = Taxonomy::category()->children()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/personas/{$persona->id}", [
            'name' => 'Test Persona',
            'intro' => 'Lorem ipsum',
            'subtitle' => 'Subtitle here',
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'sideboxes' => [],
            'category_taxonomies' => [$taxonomy->id],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonResource([
            'id',
            'slug',
            'name',
            'intro',
            'subtitle',
            'order',
            'enabled',
            'homepage',
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
            'name' => 'Test Persona',
            'intro' => 'Lorem ipsum',
            'subtitle' => 'Subtitle here',
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'sideboxes' => [],
        ]);
        $response->assertJsonFragment([
            'id' => $taxonomy->id,
        ]);
    }

    /**
     * @test
     */
    public function super_admin_can_update_status(): void
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $persona = Collection::personas()->inRandomOrder()->firstOrFail();
        $persona->enable()->save();
        $taxonomy = Taxonomy::category()->children()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/personas/{$persona->id}", [
            'name' => 'Test Persona',
            'intro' => 'Lorem ipsum',
            'subtitle' => 'Subtitle here',
            'order' => 1,
            'enabled' => false,
            'homepage' => false,
            'sideboxes' => [],
            'category_taxonomies' => [$taxonomy->id],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonResource([
            'id',
            'slug',
            'name',
            'intro',
            'subtitle',
            'order',
            'enabled',
            'homepage',
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
            'name' => 'Test Persona',
            'intro' => 'Lorem ipsum',
            'subtitle' => 'Subtitle here',
            'order' => 1,
            'enabled' => false,
            'homepage' => false,
            'sideboxes' => [],
        ]);
        $response->assertJsonFragment([
            'id' => $taxonomy->id,
        ]);
    }

    /**
     * @test
     */
    public function global_admin_can_update_homepage(): void
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $persona = Collection::personas()->inRandomOrder()->firstOrFail();
        $persona->addToHomepage()->save();
        $taxonomy = Taxonomy::category()->children()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/personas/{$persona->id}", [
            'name' => 'Test Persona',
            'intro' => 'Lorem ipsum',
            'subtitle' => 'Subtitle here',
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'sideboxes' => [],
            'category_taxonomies' => [$taxonomy->id],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonResource([
            'id',
            'slug',
            'name',
            'intro',
            'subtitle',
            'order',
            'enabled',
            'homepage',
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
            'name' => 'Test Persona',
            'intro' => 'Lorem ipsum',
            'subtitle' => 'Subtitle here',
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'sideboxes' => [],
        ]);
        $response->assertJsonFragment([
            'id' => $taxonomy->id,
        ]);
    }

    /**
     * @test
     */
    public function order_is_updated_when_updated_to_beginning(): void
    {
        // Delete the existing seeded personas.
        $this->truncateCollectionPersonas();

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $first = Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'subtitle' => 'Subtitle here',
                'image_file_id' => null,
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'second',
            'name' => 'Second',
            'order' => 2,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'subtitle' => 'Subtitle here',
                'image_file_id' => null,
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'third',
            'name' => 'Third',
            'order' => 3,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'subtitle' => 'Subtitle here',
                'image_file_id' => null,
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/personas/{$third->id}", [
            'name' => 'Third',
            'intro' => 'Lorem ipsum',
            'subtitle' => 'Subtitle here',
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'sideboxes' => [],
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $first->id, 'order' => 2]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $second->id, 'order' => 3]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $third->id, 'order' => 1]);
    }

    /**
     * @test
     */
    public function order_is_updated_when_updated_to_middle(): void
    {
        // Delete the existing seeded personas.
        $this->truncateCollectionPersonas();

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $first = Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'subtitle' => 'Subtitle here',
                'image_file_id' => null,
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'second',
            'name' => 'Second',
            'order' => 2,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'subtitle' => 'Subtitle here',
                'image_file_id' => null,
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'third',
            'name' => 'Third',
            'order' => 3,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'subtitle' => 'Subtitle here',
                'image_file_id' => null,
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/personas/{$first->id}", [
            'name' => 'First',
            'intro' => 'Lorem ipsum',
            'subtitle' => 'Subtitle here',
            'order' => 2,
            'enabled' => true,
            'homepage' => false,
            'sideboxes' => [],
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $first->id, 'order' => 2]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $second->id, 'order' => 1]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $third->id, 'order' => 3]);
    }

    /**
     * @test
     */
    public function order_is_updated_when_updated_to_end(): void
    {
        // Delete the existing seeded personas.
        $this->truncateCollectionPersonas();

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $first = Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'subtitle' => 'Subtitle here',
                'image_file_id' => null,
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'second',
            'name' => 'Second',
            'order' => 2,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'subtitle' => 'Subtitle here',
                'image_file_id' => null,
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'third',
            'name' => 'Third',
            'order' => 3,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'subtitle' => 'Subtitle here',
                'image_file_id' => null,
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/personas/{$first->id}", [
            'name' => 'First',
            'intro' => 'Lorem ipsum',
            'subtitle' => 'Subtitle here',
            'order' => 3,
            'enabled' => true,
            'homepage' => false,
            'sideboxes' => [],
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $first->id, 'order' => 3]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $second->id, 'order' => 1]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $third->id, 'order' => 2]);
    }

    /**
     * @test
     */
    public function order_cannot_be_less_than_1_when_updated(): void
    {
        // Delete the existing seeded personas.
        $this->truncateCollectionPersonas();

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $persona = Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'subtitle' => 'Subtitle here',
                'image_file_id' => null,
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/personas/{$persona->id}", [
            'name' => 'First',
            'intro' => 'Lorem ipsum',
            'subtitle' => 'Subtitle here',
            'order' => 0,
            'enabled' => true,
            'homepage' => false,
            'sideboxes' => [],
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @test
     */
    public function order_cannot_be_greater_than_count_plus_1_when_updated(): void
    {
        // Delete the existing seeded personas.
        $this->truncateCollectionPersonas();

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $persona = Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'subtitle' => 'Subtitle here',
                'image_file_id' => null,
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/personas/{$persona->id}", [
            'name' => 'First',
            'intro' => 'Lorem ipsum',
            'subtitle' => 'Subtitle here',
            'order' => 2,
            'enabled' => true,
            'homepage' => false,
            'sideboxes' => [],
            'category_taxonomies' => [],
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @test
     */
    public function audit_created_when_updated(): void
    {
        $this->fakeEvents();

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $persona = Collection::personas()->inRandomOrder()->firstOrFail();
        $taxonomy = Taxonomy::category()->children()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $this->json('PUT', "/core/v1/collections/personas/{$persona->id}", [
            'name' => 'Test Persona',
            'intro' => 'Lorem ipsum',
            'subtitle' => 'Subtitle here',
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'sideboxes' => [],
            'category_taxonomies' => [$taxonomy->id],
        ]);

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $persona) {
            return ($event->getAction() === Audit::ACTION_UPDATE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $persona->id);
        });
    }

    /**
     * @test
     */
    public function updateCollectionWithUniqueSlugAsSuperAdmin200(): void
    {
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $persona1 = Collection::factory()->typePersona()->create([
            'name' => 'Test Persona',
            'slug' => 'test-persona',
        ]);
        $persona2 = Collection::factory()->typePersona()->create([
            'name' => 'Other Persona',
            'slug' => 'other-persona',
        ]);

        Passport::actingAs($user);

        $this->json('PUT', "/core/v1/collections/personas/{$persona2->id}", [
            'name' => 'Test Persona',
            'intro' => $persona2->meta['intro'],
            'order' => $persona2->order,
            'subtitle' => 'Subtitle here',
            'enabled' => true,
            'homepage' => false,
            'sideboxes' => [],
            'category_taxonomies' => [],
        ]);

        $this->assertDatabaseHas('collections', [
            'id' => $persona2->id,
            'slug' => 'test-persona-1',
        ]);
    }

    /*
     * Delete a specific persona collection.
     */

    /**
     * @test
     */
    public function guest_cannot_delete_one(): void
    {
        $persona = Collection::personas()->inRandomOrder()->firstOrFail();

        $response = $this->json('DELETE', "/core/v1/collections/personas/{$persona->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @test
     */
    public function service_worker_cannot_delete_one(): void
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceWorker($service);
        $persona = Collection::personas()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/personas/{$persona->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function service_admin_cannot_delete_one(): void
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceAdmin($service);
        $persona = Collection::personas()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/personas/{$persona->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function organisation_admin_cannot_delete_one(): void
    {
        /**
         * @var \App\Models\Organisation $organisation
         * @var \App\Models\User $user
         */
        $organisation = Organisation::factory()->create();
        $user = User::factory()->create();
        $user->makeOrganisationAdmin($organisation);
        $persona = Collection::personas()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/personas/{$persona->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function global_admin_cannot_delete_one(): void
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeGlobalAdmin();
        $persona = Collection::personas()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/personas/{$persona->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function super_admin_can_delete_one(): void
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $persona = Collection::personas()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/personas/{$persona->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing((new Collection())->getTable(), ['id' => $persona->id]);
        $this->assertDatabaseMissing((new CollectionTaxonomy())->getTable(), ['collection_id' => $persona->id]);
    }

    /**
     * @test
     */
    public function order_is_updated_when_deleted_at_beginning(): void
    {
        // Delete the existing seeded personas.
        $this->truncateCollectionPersonas();

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $first = Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'subtitle' => 'Subtitle here',
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'second',
            'name' => 'Second',
            'order' => 2,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'subtitle' => 'Subtitle here',
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'third',
            'name' => 'Third',
            'order' => 3,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'subtitle' => 'Subtitle here',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/personas/{$first->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing((new Collection())->getTable(), ['id' => $first->id]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $second->id, 'order' => 1]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $third->id, 'order' => 2]);
    }

    /**
     * @test
     */
    public function order_is_updated_when_deleted_at_middle(): void
    {
        // Delete the existing seeded personas.
        $this->truncateCollectionPersonas();

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $first = Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'subtitle' => 'Subtitle here',
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'second',
            'name' => 'Second',
            'order' => 2,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'subtitle' => 'Subtitle here',
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'third',
            'name' => 'Third',
            'order' => 3,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'subtitle' => 'Subtitle here',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/personas/{$second->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing((new Collection())->getTable(), ['id' => $second->id]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $first->id, 'order' => 1]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $third->id, 'order' => 2]);
    }

    /**
     * @test
     */
    public function order_is_updated_when_deleted_at_end(): void
    {
        // Delete the existing seeded personas.
        $this->truncateCollectionPersonas();

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $first = Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'subtitle' => 'Subtitle here',
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'second',
            'name' => 'Second',
            'order' => 2,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'subtitle' => 'Subtitle here',
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'third',
            'name' => 'Third',
            'order' => 3,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'subtitle' => 'Subtitle here',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/personas/{$third->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing((new Collection())->getTable(), ['id' => $third->id]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $first->id, 'order' => 1]);
        $this->assertDatabaseHas((new Collection())->getTable(), ['id' => $second->id, 'order' => 2]);
    }

    /**
     * @test
     */
    public function audit_created_when_deleted(): void
    {
        $this->fakeEvents();

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $persona = Collection::personas()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $this->json('DELETE', "/core/v1/collections/personas/{$persona->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $persona) {
            return ($event->getAction() === Audit::ACTION_DELETE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $persona->id);
        });
    }

    /*
     * Get a specific persona collection's image.
     */

    /**
     * @test
     */
    public function guest_can_view_image(): void
    {
        $persona = Collection::personas()->inRandomOrder()->firstOrFail();

        $response = $this->get("/core/v1/collections/personas/{$persona->id}/image.png");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'image/png');
    }

    /**
     * @test
     */
    public function audit_created_when_image_viewed(): void
    {
        $this->fakeEvents();

        $persona = Collection::personas()->inRandomOrder()->firstOrFail();

        $this->get("/core/v1/collections/personas/{$persona->id}/image.png");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($persona) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getModel()->id === $persona->id);
        });
    }

    /*
     * Upload a specific persona collection's image.
     */

    /**
     * @test
     */
    public function super_admin_can_upload_image(): void
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
            'alt_text' => 'image description',
            'file' => 'data:image/png;base64,' . base64_encode($image),
        ]);
        $imageResponse->assertStatus(Response::HTTP_CREATED);

        $file = File::find($this->getResponseContent($imageResponse, 'data.id'));

        $response = $this->json('POST', '/core/v1/collections/personas', [
            'name' => 'Test Persona',
            'intro' => 'Lorem ipsum',
            'subtitle' => 'Subtitle here',
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'sideboxes' => [],
            'category_taxonomies' => [$randomCategory->id],
            'image_file_id' => $file->id,
        ]);

        $response->assertStatus(Response::HTTP_CREATED);

        $response->assertJsonFragment([
            'image' => [
                'id' => $file->id,
                'mime_type' => 'image/png',
                'alt_text' => 'image description',
                'url' => $file->url(),
            ],
        ]);
        $collectionArray = $this->getResponseContent($response)['data'];
        $content = $this->get("/core/v1/collections/personas/{$collectionArray['id']}/image.png")->content();
        $this->assertEquals($image, $content);
    }

    /*
     * Delete a specific persona collection's image.
     */

    /**
     * @test
     */
    public function super_admin_can_delete_image(): void
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $persona = Collection::personas()->inRandomOrder()->firstOrFail();
        $meta = $persona->meta;
        $meta['image_file_id'] = File::factory()->create()->id;
        $persona->meta = $meta;
        $persona->save();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/personas/{$persona->id}", [
            'name' => $persona->name,
            'intro' => $persona->meta['intro'],
            'subtitle' => $persona->meta['subtitle'],
            'order' => $persona->order,
            'enabled' => $persona->enabled,
            'homepage' => $persona->homepage,
            'sideboxes' => [],
            'category_taxonomies' => $persona->taxonomies()->pluck(table(Taxonomy::class, 'id')),
            'image_file_id' => null,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $persona = $persona->fresh();
        $this->assertEquals(null, $persona->meta['image_file_id']);
    }
}
