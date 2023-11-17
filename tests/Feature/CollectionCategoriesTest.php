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
use Illuminate\Support\Str;
use App\Models\Organisation;
use Illuminate\Http\Response;
use Laravel\Passport\Passport;
use App\Models\CollectionTaxonomy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

class CollectionCategoriesTest extends TestCase
{
    /*
     * List all the category collections.
     */

    /**
     * @test
     */
    public function guest_can_list_them(): void
    {
        $response = $this->json('GET', '/core/v1/collections/categories');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCollection([
            'id',
            'slug',
            'name',
            'intro',
            'order',
            'enabled',
            'homepage',
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
     * List all the category collections.
     */

    /**
     * @test
     */
    public function guest_can_list_all_of_them(): void
    {
        $collectionCategoryCount = Collection::categories()->count();

        $response = $this->json('GET', '/core/v1/collections/categories/all');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonStructure([
            'data' => [
                [
                    'id',
                    'name',
                    'intro',
                    'order',
                    'enabled',
                    'homepage',
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
                ],
            ],
        ]);
        $response->assertJsonCount($collectionCategoryCount, 'data');
    }

    /**
     * @test
     */
    public function guest_can_list_enabled_and_disabled_collections(): void
    {
        $disabledCollection = Collection::categories()->first();

        $disabledCollection->disable()->save();

        $enabledCollection = Collection::categories()->offset(1)->first();

        $enabledCollection->enable()->save();

        $response = $this->json('GET', '/core/v1/collections/categories');

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
        $disabledCollection = Collection::categories()->first();

        $disabledCollection->disable()->save();

        $enabledCollection = Collection::categories()->offset(1)->first();

        $enabledCollection->enable()->save();

        $response = $this->json('GET', '/core/v1/collections/categories/all');

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

        $this->json('GET', '/core/v1/collections/categories');

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) {
            return $event->getAction() === Audit::ACTION_READ;
        });
    }

    /*
     * Create a collection category.
     */

    /**
     * @test
     */
    public function guest_cannot_create_one(): void
    {
        $response = $this->json('POST', '/core/v1/collections/categories');

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

        $response = $this->json('POST', '/core/v1/collections/categories');

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

        $response = $this->json('POST', '/core/v1/collections/categories');

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

        $response = $this->json('POST', '/core/v1/collections/categories');

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

        $response = $this->json('POST', '/core/v1/collections/categories');

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

        $image = File::factory()->pendingAssignment()->create([
            'filename' => Str::random() . '.svg',
            'mime_type' => 'image/svg+xml',
        ]);

        $base64Image = 'data:image/svg+xml;base64,' . base64_encode(Storage::disk('local')->get('/test-data/image.svg'));

        $image->uploadBase64EncodedFile($base64Image);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/categories', [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'image_file_id' => $image->id,
            'order' => 1,
            'enabled' => true,
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
        $response->assertJsonResource([
            'id',
            'slug',
            'name',
            'intro',
            'order',
            'enabled',
            'homepage',
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
        $response->assertJsonFragment([
            'slug' => 'test-category',
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'sideboxes' => [
                [
                    'title' => 'Sidebox title',
                    'content' => 'Sidebox content',
                ],
            ],
        ]);
        $response->assertJsonFragment([
            'id' => $randomCategory->id,
        ]);

        $collectionArray = $this->getResponseContent($response)['data'];
        $response = $this->get("/core/v1/collections/categories/{$collectionArray['id']}/image.svg");
        $this->assertEquals(Storage::disk('local')->get('/test-data/image.svg'), $response->content());
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

        $image = File::factory()->pendingAssignment()->create([
            'filename' => Str::random() . '.svg',
            'mime_type' => 'image/svg+xml',
        ]);

        $base64Image = 'data:image/svg+xml;base64,' . base64_encode(Storage::disk('local')->get('/test-data/image.svg'));

        $image->uploadBase64EncodedFile($base64Image);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/categories', [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'image_file_id' => $image->id,
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
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
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

        $image = File::factory()->pendingAssignment()->create([
            'filename' => Str::random() . '.svg',
            'mime_type' => 'image/svg+xml',
        ]);

        $base64Image = 'data:image/svg+xml;base64,' . base64_encode(Storage::disk('local')->get('/test-data/image.svg'));

        $image->uploadBase64EncodedFile($base64Image);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/categories', [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'image_file_id' => $image->id,
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
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
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
    public function super_admin_cannot_create_one_without_an_image(): void
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $randomCategory = Taxonomy::category()->children()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/categories', [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'sideboxes' => [
                [
                    'title' => 'Sidebox title',
                    'content' => 'Sidebox content',
                ],
            ],
            'category_taxonomies' => [$randomCategory->id],
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $response = $this->json('POST', '/core/v1/collections/categories', [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'image_file_id' => '',
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'sideboxes' => [
                [
                    'title' => 'Sidebox title',
                    'content' => 'Sidebox content',
                ],
            ],
            'category_taxonomies' => [$randomCategory->id],
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $response = $this->json('POST', '/core/v1/collections/categories', [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'image_file_id' => uuid(),
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'sideboxes' => [
                [
                    'title' => 'Sidebox title',
                    'content' => 'Sidebox content',
                ],
            ],
            'category_taxonomies' => [$randomCategory->id],
        ]);

        $response->assertStatus(Response::HTTP_NOT_FOUND);
    }

    /**
     * @test
     */
    public function super_admin_cannot_create_one_with_an_assigned_image(): void
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $randomCategory = Taxonomy::category()->children()->inRandomOrder()->firstOrFail();

        $image = File::factory()->create([
            'filename' => Str::random() . '.svg',
            'mime_type' => 'image/svg+xml',
        ]);

        $base64Image = 'data:image/svg+xml;base64,' . base64_encode(Storage::disk('local')->get('/test-data/image.svg'));

        $image->uploadBase64EncodedFile($base64Image);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/categories', [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'image_file_id' => $image->id,
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'sideboxes' => [
                [
                    'title' => 'Sidebox title',
                    'content' => 'Sidebox content',
                ],
            ],
            'category_taxonomies' => [$randomCategory->id],
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @test
     */
    public function super_admin_creating_one_with_an_image_assigns_the_image(): void
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $randomCategory = Taxonomy::category()->children()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        // SVG
        $image = File::factory()->pendingAssignment()->imageSvg()->create();

        $response = $this->json('POST', '/core/v1/collections/categories', [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'image_file_id' => $image->id,
            'order' => 1,
            'enabled' => true,
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

        $collectionArray = $this->getResponseContent($response)['data'];
        $content = $this->get("/core/v1/collections/categories/{$collectionArray['id']}/image.svg")->content();
        $this->assertEquals(Storage::disk('local')->get('/test-data/image.svg'), $content);

        $this->assertEquals($image->id, $collectionArray['image_file_id']);

        $this->assertDatabaseHas($image->getTable(), [
            'id' => $image->id,
            'meta' => null,
        ]);

        // PNG
        $image = File::factory()->pendingAssignment()->imagePng()->create();

        $response = $this->json('POST', '/core/v1/collections/categories', [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'image_file_id' => $image->id,
            'order' => 1,
            'enabled' => true,
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

        $collectionArray = $this->getResponseContent($response)['data'];
        $content = $this->get("/core/v1/collections/categories/{$collectionArray['id']}/image.png")->content();
        $this->assertEquals(Storage::disk('local')->get('/test-data/image.png'), $content);

        $this->assertEquals($image->id, $collectionArray['image_file_id']);

        $this->assertDatabaseHas($image->getTable(), [
            'id' => $image->id,
            'meta' => null,
        ]);

        // JPG
        $image = File::factory()->pendingAssignment()->imageJpg()->create();

        $response = $this->json('POST', '/core/v1/collections/categories', [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'image_file_id' => $image->id,
            'order' => 1,
            'enabled' => true,
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

        $collectionArray = $this->getResponseContent($response)['data'];
        $content = $this->get("/core/v1/collections/categories/{$collectionArray['id']}/image.jpg")->content();
        $this->assertEquals(Storage::disk('local')->get('/test-data/image.jpg'), $content);

        $this->assertEquals($image->id, $collectionArray['image_file_id']);

        $this->assertDatabaseHas($image->getTable(), [
            'id' => $image->id,
            'meta' => null,
        ]);
    }

    /**
     * @test
     */
    public function order_is_updated_when_created_at_beginning(): void
    {
        // Delete the existing seeded categories.
        $this->truncateCollectionCategories();

        $image = File::factory()->pendingAssignment()->create([
            'filename' => Str::random() . '.svg',
            'mime_type' => 'image/svg+xml',
        ]);

        $base64Image = 'data:image/svg+xml;base64,' . base64_encode(Storage::disk('local')->get('/test-data/image.svg'));

        $image->uploadBase64EncodedFile($base64Image);

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $first = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'second',
            'name' => 'Second',
            'order' => 2,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'third',
            'name' => 'Third',
            'order' => 3,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/categories', [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'image_file_id' => $image->id,
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
        // Delete the existing seeded categories.
        $this->truncateCollectionCategories();

        $image = File::factory()->pendingAssignment()->create([
            'filename' => Str::random() . '.svg',
            'mime_type' => 'image/svg+xml',
        ]);

        $base64Image = 'data:image/svg+xml;base64,' . base64_encode(Storage::disk('local')->get('/test-data/image.svg'));

        $image->uploadBase64EncodedFile($base64Image);

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $first = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'second',
            'name' => 'Second',
            'order' => 2,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'third',
            'name' => 'Third',
            'order' => 3,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/categories', [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'image_file_id' => $image->id,
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
        // Delete the existing seeded categories.
        $this->truncateCollectionCategories();

        $image = File::factory()->pendingAssignment()->create([
            'filename' => Str::random() . '.svg',
            'mime_type' => 'image/svg+xml',
        ]);

        $base64Image = 'data:image/svg+xml;base64,' . base64_encode(Storage::disk('local')->get('/test-data/image.svg'));

        $image->uploadBase64EncodedFile($base64Image);

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $first = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'second',
            'name' => 'Second',
            'order' => 2,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'third',
            'name' => 'Third',
            'order' => 3,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/categories', [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'image_file_id' => $image->id,
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
        // Delete the existing seeded categories.
        $this->truncateCollectionCategories();

        $image = File::factory()->pendingAssignment()->create([
            'filename' => Str::random() . '.svg',
            'mime_type' => 'image/svg+xml',
        ]);

        $base64Image = 'data:image/svg+xml;base64,' . base64_encode(Storage::disk('local')->get('/test-data/image.svg'));

        $image->uploadBase64EncodedFile($base64Image);

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/categories', [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'image_file_id' => $image->id,
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
        // Delete the existing seeded categories.
        $this->truncateCollectionCategories();

        $image = File::factory()->pendingAssignment()->create([
            'filename' => Str::random() . '.svg',
            'mime_type' => 'image/svg+xml',
        ]);

        $base64Image = 'data:image/svg+xml;base64,' . base64_encode(Storage::disk('local')->get('/test-data/image.svg'));

        $image->uploadBase64EncodedFile($base64Image);

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'second',
            'name' => 'Second',
            'order' => 2,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/categories', [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'image_file_id' => $image->id,
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

        $image = File::factory()->pendingAssignment()->create([
            'filename' => Str::random() . '.svg',
            'mime_type' => 'image/svg+xml',
        ]);

        $base64Image = 'data:image/svg+xml;base64,' . base64_encode(Storage::disk('local')->get('/test-data/image.svg'));

        $image->uploadBase64EncodedFile($base64Image);

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $randomCategory = Taxonomy::category()->children()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/collections/categories', [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'image_file_id' => $image->id,
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

    /*
     * Get a specific category collection.
     */

    /**
     * @test
     */
    public function guest_can_view_one(): void
    {
        $collectionCategory = Collection::categories()->inRandomOrder()->firstOrFail();

        $response = $this->json('GET', "/core/v1/collections/categories/{$collectionCategory->id}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonResource([
            'id',
            'slug',
            'name',
            'intro',
            'order',
            'enabled',
            'image_file_id',
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
            'id' => $collectionCategory->id,
            'name' => $collectionCategory->name,
            'intro' => $collectionCategory->meta['intro'],
            'order' => $collectionCategory->order,
            'enabled' => $collectionCategory->enabled,
            'homepage' => $collectionCategory->homepage,
            'sideboxes' => $collectionCategory->meta['sideboxes'],
            'created_at' => $collectionCategory->created_at->format(CarbonImmutable::ISO8601),
            'updated_at' => $collectionCategory->updated_at->format(CarbonImmutable::ISO8601),
        ]);
    }

    /**
     * @test
     */
    public function guest_can_view_one_by_slug(): void
    {
        $collectionCategory = Collection::categories()->inRandomOrder()->firstOrFail();

        $response = $this->json('GET', "/core/v1/collections/categories/{$collectionCategory->slug}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonResource([
            'id',
            'slug',
            'name',
            'intro',
            'order',
            'enabled',
            'image_file_id',
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
            'id' => $collectionCategory->id,
            'slug' => $collectionCategory->slug,
            'name' => $collectionCategory->name,
            'intro' => $collectionCategory->meta['intro'],
            'order' => $collectionCategory->order,
            'enabled' => $collectionCategory->enabled,
            'homepage' => $collectionCategory->homepage,
            'sideboxes' => $collectionCategory->meta['sideboxes'],
            'created_at' => $collectionCategory->created_at->format(CarbonImmutable::ISO8601),
            'updated_at' => $collectionCategory->updated_at->format(CarbonImmutable::ISO8601),
        ]);
    }

    /**
     * @test
     */
    public function audit_created_when_viewed(): void
    {
        $this->fakeEvents();

        $collectionCategory = Collection::categories()->inRandomOrder()->firstOrFail();

        $response = $this->json('GET', "/core/v1/collections/categories/{$collectionCategory->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($response) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getModel()->id === $this->getResponseContent($response)['data']['id']);
        });
    }

    /*
     * Get a specific categories image.
     */

    /**
     * @test
     */
    public function guest_can_view_image_as_svg(): void
    {
        $collectionCategory = Collection::categories()->inRandomOrder()->firstOrFail();

        $image = File::factory()->pendingAssignment()->create([
            'filename' => Str::random() . '.svg',
            'mime_type' => 'image/svg+xml',
        ]);

        $base64Image = 'data:image/svg+xml;base64,' . base64_encode(Storage::disk('local')->get('/test-data/image.svg'));

        $image->uploadBase64EncodedFile($base64Image);

        $meta = $collectionCategory->meta;
        $meta['image_file_id'] = $image->id;
        $collectionCategory->meta = $meta;
        $collectionCategory->save();

        $response = $this->get("/core/v1/collections/categories/{$collectionCategory->id}/image.svg");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'image/svg+xml');
        $this->assertEquals(Storage::disk('local')->get('/test-data/image.svg'), $response->content());
    }

    /**
     * @test
     */
    public function guest_can_view_image_as_png(): void
    {
        $collectionCategory = Collection::categories()->inRandomOrder()->firstOrFail();

        $image = File::factory()->pendingAssignment()->create([
            'filename' => Str::random() . '.png',
            'mime_type' => 'image/png',
        ]);

        $base64Image = 'data:image/png;base64,' . base64_encode(Storage::disk('local')->get('/test-data/image.png'));

        $image->uploadBase64EncodedFile($base64Image);

        $meta = $collectionCategory->meta;
        $meta['image_file_id'] = $image->id;
        $collectionCategory->meta = $meta;
        $collectionCategory->save();

        $response = $this->get("/core/v1/collections/categories/{$collectionCategory->id}/image.png");
        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'image/png');
        $this->assertEquals(Storage::disk('local')->get('/test-data/image.png'), $response->content());
    }

    /**
     * @test
     */
    public function guest_can_view_image_as_jpg(): void
    {
        $collectionCategory = Collection::categories()->inRandomOrder()->firstOrFail();

        $image = File::factory()->pendingAssignment()->create([
            'filename' => Str::random() . '.jpg',
            'mime_type' => 'image/jpeg',
        ]);

        $base64Image = 'data:image/jpg;base64,' . base64_encode(Storage::disk('local')->get('/test-data/image.jpg'));

        $image->uploadBase64EncodedFile($base64Image);

        $meta = $collectionCategory->meta;
        $meta['image_file_id'] = $image->id;
        $collectionCategory->meta = $meta;
        $collectionCategory->save();

        $response = $this->get("/core/v1/collections/categories/{$collectionCategory->id}/image.jpg");
        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'image/jpeg');
        $this->assertEquals(Storage::disk('local')->get('/test-data/image.jpg'), $response->content());
    }

    /**
     * @test
     */
    public function audit_created_when_image_viewed(): void
    {
        $this->fakeEvents();

        $collectionCategory = Collection::categories()->inRandomOrder()->firstOrFail();

        $image = File::factory()->pendingAssignment()->create([
            'filename' => Str::random() . '.svg',
            'mime_type' => 'image/svg+xml',
        ]);

        $base64Image = 'data:image/svg+xml;base64,' . base64_encode(Storage::disk('local')->get('/test-data/image.svg'));

        $image->uploadBase64EncodedFile($base64Image);

        $meta = $collectionCategory->meta;
        $meta['image_file_id'] = $image->id;
        $collectionCategory->meta = $meta;
        $collectionCategory->save();

        $this->get("/core/v1/collections/categories/{$collectionCategory->id}/image.svg");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($collectionCategory) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getModel()->id === $collectionCategory->id);
        });
    }

    /**
     * @test
     */
    public function default_image_returned_when_image_is_not_set(): void
    {
        $collectionCategory = Collection::categories()->inRandomOrder()->firstOrFail();

        $placeholder = Storage::disk('local')->get('/placeholders/collection_category.png');

        $response = $this->get("/core/v1/collections/categories/{$collectionCategory->id}/image.svg");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'image/png');

        $content = $response->content();
        $this->assertEquals($placeholder, $content);
    }

    /*
     * Update a specific category collection.
     */

    /**
     * @test
     */
    public function guest_cannot_update_one(): void
    {
        $category = Collection::categories()->inRandomOrder()->firstOrFail();

        $response = $this->json('PUT', "/core/v1/collections/categories/{$category->id}");

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
        $category = Collection::categories()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/categories/{$category->id}");

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
        $category = Collection::categories()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/categories/{$category->id}");

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
        $category = Collection::categories()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/categories/{$category->id}");

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
        $category = Collection::categories()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/categories/{$category->id}");

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
        $category = Collection::categories()->inRandomOrder()->firstOrFail();
        $taxonomy = Taxonomy::category()->children()->inRandomOrder()->firstOrFail();

        $image = File::factory()->pendingAssignment()->create([
            'filename' => Str::random() . '.svg',
            'mime_type' => 'image/svg+xml',
        ]);

        $base64Image = 'data:image/svg+xml;base64,' . base64_encode(Storage::disk('local')->get('/test-data/image.svg'));

        $image->uploadBase64EncodedFile($base64Image);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/categories/{$category->id}", [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'image_file_id' => $image->id,
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
            'order',
            'enabled',
            'homepage',
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
        $response->assertJsonFragment([
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'image_file_id' => $image->id,
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'sideboxes' => [],
        ]);
        $response->assertJsonFragment([
            'id' => $taxonomy->id,
        ]);

        $category = $category->fresh();
        $this->assertEquals($image->id, $category->meta['image_file_id']);

        $content = $this->get("/core/v1/collections/categories/{$category->id}/image.svg")->content();
        $this->assertEquals(Storage::disk('local')->get('/test-data/image.svg'), $content);
    }

    /**
     * @test
     */
    public function super_admin_cannot_update_one_without_an_image(): void
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $category = Collection::categories()->inRandomOrder()->firstOrFail();
        $category->enable()->save();
        $taxonomy = Taxonomy::category()->children()->inRandomOrder()->firstOrFail();

        $image = File::factory()->pendingAssignment()->create([
            'filename' => Str::random() . '.svg',
            'mime_type' => 'image/svg+xml',
        ]);

        $base64Image = 'data:image/svg+xml;base64,' . base64_encode(Storage::disk('local')->get('/test-data/image.svg'));

        $image->uploadBase64EncodedFile($base64Image);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/categories/{$category->id}", [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'subtitle' => 'Subtitle here',
            'order' => $category->order,
            'enabled' => true,
            'homepage' => false,
            'sideboxes' => [],
            'category_taxonomies' => [$taxonomy->id],
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $response = $this->json('PUT', "/core/v1/collections/categories/{$category->id}", [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'subtitle' => 'Subtitle here',
            'image_file_id' => null,
            'order' => $category->order,
            'enabled' => true,
            'homepage' => false,
            'sideboxes' => [],
            'category_taxonomies' => [$taxonomy->id],
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @test
     */
    public function super_admin_cannot_update_one_with_an_assigned_image(): void
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $category = Collection::categories()->inRandomOrder()->firstOrFail();
        $category->enable()->save();
        $taxonomy = Taxonomy::category()->children()->inRandomOrder()->firstOrFail();

        $image = File::factory()->create([
            'filename' => Str::random() . '.svg',
            'mime_type' => 'image/svg+xml',
        ]);

        $base64Image = 'data:image/svg+xml;base64,' . base64_encode(Storage::disk('local')->get('/test-data/image.svg'));

        $image->uploadBase64EncodedFile($base64Image);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/categories/{$category->id}", [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'subtitle' => 'Subtitle here',
            'image_file_id' => $image->id,
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'sideboxes' => [],
            'category_taxonomies' => [$taxonomy->id],
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @test
     */
    public function super_admin_updating_one_with_a_new_image_assigns_the_image(): void
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $category = Collection::categories()->inRandomOrder()->firstOrFail();
        $taxonomy = Taxonomy::category()->children()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        // SVG
        $image = File::factory()->pendingAssignment()->imageSvg()->create();

        $response = $this->json('PUT', "/core/v1/collections/categories/{$category->id}", [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'subtitle' => 'Subtitle here',
            'image_file_id' => $image->id,
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'sideboxes' => [],
            'category_taxonomies' => [$taxonomy->id],
        ]);

        $response->assertStatus(Response::HTTP_OK);

        $category = $category->fresh();
        $this->assertEquals($image->id, $category->meta['image_file_id']);

        $content = $this->get("/core/v1/collections/categories/{$category->id}/image.svg")->content();
        $this->assertEquals(Storage::disk('local')->get('/test-data/image.svg'), $content);

        $this->assertDatabaseHas($image->getTable(), [
            'id' => $image->id,
            'meta' => null,
        ]);

        // PNG
        $image = File::factory()->pendingAssignment()->imagePng()->create();

        $response = $this->json('PUT', "/core/v1/collections/categories/{$category->id}", [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'subtitle' => 'Subtitle here',
            'image_file_id' => $image->id,
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'sideboxes' => [],
            'category_taxonomies' => [$taxonomy->id],
        ]);

        $response->assertStatus(Response::HTTP_OK);

        $category = $category->fresh();
        $this->assertEquals($image->id, $category->meta['image_file_id']);

        $content = $this->get("/core/v1/collections/categories/{$category->id}/image.png")->content();
        $this->assertEquals(Storage::disk('local')->get('/test-data/image.png'), $content);

        $this->assertDatabaseHas($image->getTable(), [
            'id' => $image->id,
            'meta' => null,
        ]);

        // JPG
        $image = File::factory()->pendingAssignment()->imageJpg()->create();

        $response = $this->json('PUT', "/core/v1/collections/categories/{$category->id}", [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'subtitle' => 'Subtitle here',
            'image_file_id' => $image->id,
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'sideboxes' => [],
            'category_taxonomies' => [$taxonomy->id],
        ]);

        $response->assertStatus(Response::HTTP_OK);

        $category = $category->fresh();
        $this->assertEquals($image->id, $category->meta['image_file_id']);

        $content = $this->get("/core/v1/collections/categories/{$category->id}/image.jpg")->content();
        $this->assertEquals(Storage::disk('local')->get('/test-data/image.jpg'), $content);

        $this->assertDatabaseHas($image->getTable(), [
            'id' => $image->id,
            'meta' => null,
        ]);
    }

    /**
     * @test
     */
    public function super_admin_can_update_one_without_an_image_when_changing_order(): void
    {
        // Delete the existing seeded categories.
        $this->truncateCollectionCategories();

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $first = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'second',
            'name' => 'Second',
            'order' => 2,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'third',
            'name' => 'Third',
            'order' => 3,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/categories/{$third->id}", [
            'name' => 'Third',
            'intro' => 'Lorem ipsum',
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
    public function super_admin_can_update_status(): void
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $category = Collection::categories()->inRandomOrder()->firstOrFail();
        $category->enable()->save();
        $taxonomy = Taxonomy::category()->children()->inRandomOrder()->firstOrFail();

        $image = File::factory()->pendingAssignment()->create([
            'filename' => Str::random() . '.svg',
            'mime_type' => 'image/svg+xml',
        ]);

        $base64Image = 'data:image/svg+xml;base64,' . base64_encode(Storage::disk('local')->get('/test-data/image.svg'));

        $image->uploadBase64EncodedFile($base64Image);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/categories/{$category->id}", [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'subtitle' => 'Subtitle here',
            'image_file_id' => $image->id,
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
            'order',
            'enabled',
            'homepage',
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
        $response->assertJsonFragment([
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'order' => 1,
            'enabled' => false,
            'sideboxes' => [],
        ]);
        $response->assertJsonFragment([
            'id' => $taxonomy->id,
        ]);
    }

    /**
     * @test
     */
    public function super_admin_can_update_homepage(): void
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $category = Collection::categories()->inRandomOrder()->firstOrFail();
        $category->addToHomepage()->save();
        $taxonomy = Taxonomy::category()->children()->inRandomOrder()->firstOrFail();

        $image = File::factory()->pendingAssignment()->create([
            'filename' => Str::random() . '.svg',
            'mime_type' => 'image/svg+xml',
        ]);

        $base64Image = 'data:image/svg+xml;base64,' . base64_encode(Storage::disk('local')->get('/test-data/image.svg'));

        $image->uploadBase64EncodedFile($base64Image);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/categories/{$category->id}", [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'image_file_id' => $image->id,
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
            'image_file_id',
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
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'image_file_id' => $image->id,
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
        // Delete the existing seeded categories.
        $this->truncateCollectionCategories();

        $image = File::factory()->pendingAssignment()->create([
            'filename' => Str::random() . '.svg',
            'mime_type' => 'image/svg+xml',
        ]);

        $base64Image = 'data:image/svg+xml;base64,' . base64_encode(Storage::disk('local')->get('/test-data/image.svg'));

        $image->uploadBase64EncodedFile($base64Image);

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $first = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'second',
            'name' => 'Second',
            'order' => 2,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'third',
            'name' => 'Third',
            'order' => 3,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/categories/{$third->id}", [
            'name' => 'Third',
            'intro' => 'Lorem ipsum',
            'order' => 1,
            'image_file_id' => $image->id,
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
        // Delete the existing seeded categories.
        $this->truncateCollectionCategories();

        $image = File::factory()->pendingAssignment()->create([
            'filename' => Str::random() . '.svg',
            'mime_type' => 'image/svg+xml',
        ]);

        $base64Image = 'data:image/svg+xml;base64,' . base64_encode(Storage::disk('local')->get('/test-data/image.svg'));

        $image->uploadBase64EncodedFile($base64Image);

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $first = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'second',
            'name' => 'Second',
            'order' => 2,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'third',
            'name' => 'Third',
            'order' => 3,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/categories/{$first->id}", [
            'name' => 'First',
            'intro' => 'Lorem ipsum',
            'order' => 2,
            'image_file_id' => $image->id,
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
        // Delete the existing seeded categories.
        $this->truncateCollectionCategories();

        $image = File::factory()->pendingAssignment()->create([
            'filename' => Str::random() . '.svg',
            'mime_type' => 'image/svg+xml',
        ]);

        $base64Image = 'data:image/svg+xml;base64,' . base64_encode(Storage::disk('local')->get('/test-data/image.svg'));

        $image->uploadBase64EncodedFile($base64Image);

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $first = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'second',
            'name' => 'Second',
            'order' => 2,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'third',
            'name' => 'Third',
            'order' => 3,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/categories/{$first->id}", [
            'name' => 'First',
            'intro' => 'Lorem ipsum',
            'order' => 3,
            'image_file_id' => $image->id,
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
        // Delete the existing seeded categories.
        $this->truncateCollectionCategories();

        $image = File::factory()->pendingAssignment()->create([
            'filename' => Str::random() . '.svg',
            'mime_type' => 'image/svg+xml',
        ]);

        $base64Image = 'data:image/svg+xml;base64,' . base64_encode(Storage::disk('local')->get('/test-data/image.svg'));

        $image->uploadBase64EncodedFile($base64Image);

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $category = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/categories/{$category->id}", [
            'name' => 'First',
            'intro' => 'Lorem ipsum',
            'order' => 0,
            'image_file_id' => $image->id,
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
        // Delete the existing seeded categories.
        $this->truncateCollectionCategories();

        $image = File::factory()->pendingAssignment()->create([
            'filename' => Str::random() . '.svg',
            'mime_type' => 'image/svg+xml',
        ]);

        $base64Image = 'data:image/svg+xml;base64,' . base64_encode(Storage::disk('local')->get('/test-data/image.svg'));

        $image->uploadBase64EncodedFile($base64Image);

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $category = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/categories/{$category->id}", [
            'name' => 'First',
            'intro' => 'Lorem ipsum',
            'order' => 2,
            'image_file_id' => $image->id,
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
        $category = Collection::categories()->inRandomOrder()->firstOrFail();
        $taxonomy = Taxonomy::category()->children()->inRandomOrder()->firstOrFail();

        $image = File::factory()->pendingAssignment()->create([
            'filename' => Str::random() . '.svg',
            'mime_type' => 'image/svg+xml',
        ]);

        $base64Image = 'data:image/svg+xml;base64,' . base64_encode(Storage::disk('local')->get('/test-data/image.svg'));

        $image->uploadBase64EncodedFile($base64Image);

        Passport::actingAs($user);

        $this->json('PUT', "/core/v1/collections/categories/{$category->id}", [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'order' => 1,
            'image_file_id' => $image->id,
            'enabled' => true,
            'homepage' => false,
            'sideboxes' => [],
            'category_taxonomies' => [$taxonomy->id],
        ]);

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $category) {
            return ($event->getAction() === Audit::ACTION_UPDATE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $category->id);
        });
    }

    /*
     * Delete a specific category collection.
     */

    /**
     * @test
     */
    public function guest_cannot_delete_one(): void
    {
        $category = Collection::categories()->inRandomOrder()->firstOrFail();

        $response = $this->json('DELETE', "/core/v1/collections/categories/{$category->id}");

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
        $category = Collection::categories()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/categories/{$category->id}");

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
        $category = Collection::categories()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/categories/{$category->id}");

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
        $category = Collection::categories()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/categories/{$category->id}");

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
        $category = Collection::categories()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/categories/{$category->id}");

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
        $category = Collection::categories()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/categories/{$category->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing((new Collection())->getTable(), ['id' => $category->id]);
        $this->assertDatabaseMissing((new CollectionTaxonomy())->getTable(), ['collection_id' => $category->id]);
    }

    /*
     * Delete a specific category collection's image.
     */

    /**
     * @test
     */
    public function super_admin_cannot_delete_image(): void
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $category = Collection::categories()->inRandomOrder()->firstOrFail();
        $meta = $category->meta;
        $meta['image_file_id'] = File::factory()->create()->id;
        $category->meta = $meta;
        $category->save();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/collections/categories/{$category->id}", [
            'name' => 'Test Category',
            'intro' => 'Lorem ipsum',
            'order' => $category->order,
            'enabled' => true,
            'homepage' => false,
            'sideboxes' => [],
            'category_taxonomies' => $category->taxonomies()->pluck(table(Taxonomy::class, 'id')),
            'image_file_id' => null,
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $category = $category->fresh();
        $this->assertEquals($meta['image_file_id'], $category->meta['image_file_id']);
    }

    /**
     * @test
     */
    public function order_is_updated_when_deleted_at_beginning(): void
    {
        // Delete the existing seeded categories.
        $this->truncateCollectionCategories();

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $first = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'second',
            'name' => 'Second',
            'order' => 2,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'third',
            'name' => 'Third',
            'order' => 3,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/categories/{$first->id}");

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
        // Delete the existing seeded categories.
        $this->truncateCollectionCategories();

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $first = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'second',
            'name' => 'Second',
            'order' => 2,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'third',
            'name' => 'Third',
            'order' => 3,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/categories/{$second->id}");

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
        // Delete the existing seeded categories.
        $this->truncateCollectionCategories();

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $first = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'first',
            'name' => 'First',
            'order' => 1,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $second = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'second',
            'name' => 'Second',
            'order' => 2,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);
        $third = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'third',
            'name' => 'Third',
            'order' => 3,
            'enabled' => true,
            'homepage' => false,
            'meta' => [
                'intro' => 'Lorem ipsum',
                'sideboxes' => [],
            ],
        ]);

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/collections/categories/{$third->id}");

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
        $category = Collection::categories()->inRandomOrder()->firstOrFail();

        Passport::actingAs($user);

        $this->json('DELETE', "/core/v1/collections/categories/{$category->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $category) {
            return ($event->getAction() === Audit::ACTION_DELETE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $category->id);
        });
    }
}
