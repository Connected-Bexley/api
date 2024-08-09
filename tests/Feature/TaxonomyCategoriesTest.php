<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Audit;
use App\Models\Service;
use App\Models\Taxonomy;
use App\Events\EndpointHit;
use Carbon\CarbonImmutable;
use App\Models\Organisation;
use Illuminate\Http\Response;
use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Event;
use Illuminate\Database\Eloquent\Collection;

class TaxonomyCategoriesTest extends TestCase
{
    /**
     * Root category for tests
     *
     * @var \App\Models\Taxonomy
     **/
    protected $testCategoryRoot;

    protected function setUp(): void
    {
        parent::setUp();

        $this->testCategoryRoot = Taxonomy::factory()->create([
            'name' => 'Test Root Category Taxonomy',
            'parent_id' => function () {
                return Taxonomy::category()->id;
            },
        ]);
    }

    /*
     * List all the category taxonomies.
     */

    /**
     * @test
     */
    public function guest_can_list_them(): void
    {
        $response = $this->json('GET', '/core/v1/taxonomies/categories');

        $randomTaxonomy = null;
        Taxonomy::chunk(200, function (Collection $taxonomies) use (&$randomTaxonomy) {
            foreach ($taxonomies as $taxonomy) {
                if ($taxonomy->children()->count() === 0) {
                    $randomTaxonomy = $taxonomy;

                    return false;
                }
            }
        });

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            [
                'id' => $randomTaxonomy->id,
                'parent_id' => $randomTaxonomy->parent_id,
                'slug' => $randomTaxonomy->slug,
                'name' => $randomTaxonomy->name,
                'order' => $randomTaxonomy->order,
                'children' => [],
                'created_at' => $randomTaxonomy->created_at->format(CarbonImmutable::ISO8601),
                'updated_at' => $randomTaxonomy->updated_at->format(CarbonImmutable::ISO8601),
            ],
        ]);
    }

    /**
     * @test
     */
    public function audit_created_when_listed(): void
    {
        $this->fakeEvents();

        $this->json('GET', '/core/v1/taxonomies/categories');

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) {
            return $event->getAction() === Audit::ACTION_READ;
        });
    }

    /*
     * Create a category taxonomy.
     */

    /**
     * @test
     */
    public function guest_cannot_create_one(): void
    {
        $response = $this->json('POST', '/core/v1/taxonomies/categories');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @test
     */
    public function service_worker_cannot_create_one(): void
    {
        $service = Service::factory()->create();
        $user = User::factory()->create()->makeServiceWorker($service);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/taxonomies/categories');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function service_admin_cannot_create_one(): void
    {
        $service = Service::factory()->create();
        $user = User::factory()->create()->makeServiceAdmin($service);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/taxonomies/categories');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function organisation_admin_cannot_create_one(): void
    {
        $organisation = Organisation::factory()->create();
        $user = User::factory()->create()->makeOrganisationAdmin($organisation);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/taxonomies/categories');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function global_admin_cannot_create_one(): void
    {
        $user = User::factory()->create()->makeGlobalAdmin();

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/taxonomies/categories');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function super_admin_can_create_one(): void
    {
        $user = User::factory()->create()->makeSuperAdmin();
        $siblingCount = Taxonomy::category()->children()->count();
        $payload = [
            'parent_id' => null,
            'name' => 'PHPUnit Taxonomy Category Test',
            'order' => $siblingCount + 1,
        ];

        Passport::actingAs($user);
        $response = $this->json('POST', '/core/v1/taxonomies/categories', $payload);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonFragment($payload);
    }

    /**
     * @test
     */
    public function order_is_updated_when_created_at_beginning(): void
    {
        $user = User::factory()->create()->makeSuperAdmin();

        $topLevelCategories = [];
        for ($i = 1; $i < 6; $i++) {
            $topLevelCategories[] = Taxonomy::factory()->create([
                'parent_id' => $this->testCategoryRoot->id,
                'order' => $i,
            ]);
        }

        $payload = [
            'parent_id' => $this->testCategoryRoot->id,
            'name' => 'PHPUnit Taxonomy Category Test',
            'order' => 1,
        ];

        Passport::actingAs($user);
        $response = $this->json('POST', '/core/v1/taxonomies/categories', $payload);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonFragment($payload);
        foreach ($topLevelCategories as $category) {
            $this->assertDatabaseHas(
                (new Taxonomy())->getTable(),
                ['id' => $category->id, 'order' => $category->order + 1]
            );
        }
    }

    /**
     * @test
     */
    public function order_is_updated_when_created_at_middle(): void
    {
        $user = User::factory()->create()->makeSuperAdmin();

        $topLevelCategories = [];
        for ($i = 1; $i < 6; $i++) {
            $topLevelCategories[] = Taxonomy::factory()->create([
                'parent_id' => $this->testCategoryRoot->id,
                'order' => $i,
            ]);
        }

        $payload = [
            'parent_id' => $this->testCategoryRoot->id,
            'name' => 'PHPUnit Taxonomy Category Test',
            'order' => 2,
        ];

        Passport::actingAs($user);
        $response = $this->json('POST', '/core/v1/taxonomies/categories', $payload);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonFragment($payload);
        foreach ($topLevelCategories as $category) {
            if ($category->order < 2) {
                $this->assertDatabaseHas(
                    (new Taxonomy())->getTable(),
                    ['id' => $category->id, 'order' => $category->order]
                );
            } else {
                $this->assertDatabaseHas(
                    (new Taxonomy())->getTable(),
                    ['id' => $category->id, 'order' => $category->order + 1]
                );
            }
        }
    }

    /**
     * @test
     */
    public function order_is_updated_when_created_at_end(): void
    {
        $user = User::factory()->create()->makeSuperAdmin();

        $topLevelCategories = [];
        for ($i = 1; $i < 6; $i++) {
            $topLevelCategories[] = Taxonomy::factory()->create([
                'parent_id' => $this->testCategoryRoot->id,
                'order' => $i,
            ]);
        }

        $payload = [
            'parent_id' => $this->testCategoryRoot->id,
            'name' => 'PHPUnit Taxonomy Category Test',
            'order' => count($topLevelCategories) + 1,
        ];

        Passport::actingAs($user);
        $response = $this->json('POST', '/core/v1/taxonomies/categories', $payload);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonFragment($payload);
        foreach ($topLevelCategories as $category) {
            $this->assertDatabaseHas(
                (new Taxonomy())->getTable(),
                ['id' => $category->id, 'order' => $category->order]
            );
        }
    }

    /**
     * @test
     */
    public function order_cannot_be_less_than_1_when_created(): void
    {
        $user = User::factory()->create()->makeSuperAdmin();
        $payload = [
            'parent_id' => null,
            'name' => 'PHPUnit Taxonomy Category Test',
            'order' => 0,
        ];

        Passport::actingAs($user);
        $response = $this->json('POST', '/core/v1/taxonomies/categories', $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @test
     */
    public function order_cannot_be_greater_than_count_plus_1_when_created(): void
    {
        $user = User::factory()->create()->makeSuperAdmin();
        $siblingCount = Taxonomy::category()->children()->count();
        $payload = [
            'parent_id' => null,
            'name' => 'PHPUnit Taxonomy Category Test',
            'order' => $siblingCount + 2,
        ];

        Passport::actingAs($user);
        $response = $this->json('POST', '/core/v1/taxonomies/categories', $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @test
     */
    public function createTaxonomyCategoryWithUniqueSlugAsSuperAdmin201(): void
    {
        $user = User::factory()->create()->makeSuperAdmin();
        $siblingCount = Taxonomy::category()->children()->count();
        $payload = [
            'parent_id' => null,
            'name' => 'Taxonomy Slug Test',
            'order' => $siblingCount + 1,
        ];

        Passport::actingAs($user);
        $response = $this->json('POST', '/core/v1/taxonomies/categories', $payload);

        $response->assertStatus(Response::HTTP_CREATED);
        $this->assertDatabaseHas('taxonomies', [
            'id' => $response->json('data.id'),
            'name' => 'Taxonomy Slug Test',
            'slug' => 'taxonomy-slug-test',
        ]);

        $payload['order']++;

        $response = $this->json('POST', '/core/v1/taxonomies/categories', $payload);

        $response->assertStatus(Response::HTTP_CREATED);
        $this->assertDatabaseHas('taxonomies', [
            'id' => $response->json('data.id'),
            'name' => 'Taxonomy Slug Test',
            'slug' => 'taxonomy-slug-test-1',
        ]);
    }

    /**
     * @test
     */
    public function audit_created_when_created(): void
    {
        $this->fakeEvents();

        $user = User::factory()->create()->makeSuperAdmin();
        $siblingCount = Taxonomy::category()->children()->count();

        Passport::actingAs($user);
        $response = $this->json('POST', '/core/v1/taxonomies/categories', [
            'parent_id' => null,
            'name' => 'PHPUnit Taxonomy Category Test',
            'order' => $siblingCount + 1,
        ]);

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $response) {
            return ($event->getAction() === Audit::ACTION_CREATE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $this->getResponseContent($response)['data']['id']);
        });
    }

    /*
     * Get a specific category taxonomy
     */

    /**
     * @test
     */
    public function guest_can_view_one(): void
    {
        $randomTaxonomy = null;
        Taxonomy::chunk(200, function (Collection $taxonomies) use (&$randomTaxonomy) {
            foreach ($taxonomies as $taxonomy) {
                if ($taxonomy->children()->count() === 0) {
                    $randomTaxonomy = $taxonomy;

                    return false;
                }
            }
        });

        $response = $this->json('GET', "/core/v1/taxonomies/categories/{$randomTaxonomy->id}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            [
                'id' => $randomTaxonomy->id,
                'parent_id' => $randomTaxonomy->parent_id,
                'slug' => $randomTaxonomy->slug,
                'name' => $randomTaxonomy->name,
                'order' => $randomTaxonomy->order,
                'children' => [],
                'created_at' => $randomTaxonomy->created_at->format(CarbonImmutable::ISO8601),
                'updated_at' => $randomTaxonomy->updated_at->format(CarbonImmutable::ISO8601),
            ],
        ]);
    }

    /**
     * @test
     */
    public function guest_can_view_one_by_slug(): void
    {
        $randomTaxonomy = null;
        Taxonomy::chunk(200, function (Collection $taxonomies) use (&$randomTaxonomy) {
            foreach ($taxonomies as $taxonomy) {
                if ($taxonomy->children()->count() === 0) {
                    $randomTaxonomy = $taxonomy;

                    return false;
                }
            }
        });

        $response = $this->json('GET', "/core/v1/taxonomies/categories/{$randomTaxonomy->slug}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            [
                'id' => $randomTaxonomy->id,
                'parent_id' => $randomTaxonomy->parent_id,
                'slug' => $randomTaxonomy->slug,
                'name' => $randomTaxonomy->name,
                'order' => $randomTaxonomy->order,
                'children' => [],
                'created_at' => $randomTaxonomy->created_at->format(CarbonImmutable::ISO8601),
                'updated_at' => $randomTaxonomy->updated_at->format(CarbonImmutable::ISO8601),
            ],
        ]);
    }

    /**
     * @test
     */
    public function audit_created_when_viewed(): void
    {
        $this->fakeEvents();

        $randomTaxonomy = null;
        Taxonomy::chunk(200, function (Collection $taxonomies) use (&$randomTaxonomy) {
            foreach ($taxonomies as $taxonomy) {
                if ($taxonomy->children()->count() === 0) {
                    $randomTaxonomy = $taxonomy;

                    return false;
                }
            }
        });

        $this->json('GET', "/core/v1/taxonomies/categories/{$randomTaxonomy->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($randomTaxonomy) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getModel()->id === $randomTaxonomy->id);
        });
    }

    /*
     * Update a specific category taxonomy.
     */

    /**
     * @test
     */
    public function guest_cannot_update_one(): void
    {
        $category = $this->getRandomCategoryWithoutChildren();

        $response = $this->json('PUT', "/core/v1/taxonomies/categories/{$category->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @test
     */
    public function service_worker_cannot_update_one(): void
    {
        $service = Service::factory()->create();
        $user = User::factory()->create()->makeServiceWorker($service);
        $category = $this->getRandomCategoryWithoutChildren();

        Passport::actingAs($user);
        $response = $this->json('PUT', "/core/v1/taxonomies/categories/{$category->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function service_admin_cannot_update_one(): void
    {
        $service = Service::factory()->create();
        $user = User::factory()->create()->makeServiceAdmin($service);
        $category = $this->getRandomCategoryWithoutChildren();

        Passport::actingAs($user);
        $response = $this->json('PUT', "/core/v1/taxonomies/categories/{$category->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function organisation_admin_cannot_update_one(): void
    {
        $organisation = Organisation::factory()->create();
        $user = User::factory()->create()->makeOrganisationAdmin($organisation);
        $category = $this->getRandomCategoryWithoutChildren();

        Passport::actingAs($user);
        $response = $this->json('PUT', "/core/v1/taxonomies/categories/{$category->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function global_admin_cannot_update_one(): void
    {
        $user = User::factory()->create()->makeGlobalAdmin();
        $category = $this->getRandomCategoryWithoutChildren();

        Passport::actingAs($user);
        $response = $this->json('PUT', "/core/v1/taxonomies/categories/{$category->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function super_admin_can_update_one(): void
    {
        $user = User::factory()->create()->makeSuperAdmin();
        $category = Taxonomy::factory()->create([
            'parent_id' => $this->testCategoryRoot->id,
            'order' => 1,
        ]);
        $payload = [
            'parent_id' => $this->testCategoryRoot->id,
            'name' => 'PHPUnit Test Category',
            'order' => 1,
        ];

        Passport::actingAs($user);
        $response = $this->json('PUT', "/core/v1/taxonomies/categories/{$category->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment($payload);
    }

    /**
     * @test
     */
    public function order_is_updated_when_updated_to_beginning(): void
    {
        $user = User::factory()->create()->makeSuperAdmin();
        Passport::actingAs($user);

        $parentCategory = $this->createTopLevelCategory();
        $categoryOne = $parentCategory->children()->create([
            'slug' => 'one',
            'name' => 'One',
            'order' => 1,
            'depth' => 1,
        ]);
        $categoryTwo = $parentCategory->children()->create([
            'slug' => 'two',
            'name' => 'Two',
            'order' => 2,
            'depth' => 1,
        ]);
        $categoryThree = $parentCategory->children()->create([
            'slug' => 'three',
            'name' => 'Three',
            'order' => 3,
            'depth' => 1,
        ]);

        $response = $this->json('PUT', "/core/v1/taxonomies/categories/{$categoryTwo->id}", [
            'parent_id' => $categoryTwo->parent_id,
            'name' => $categoryTwo->name,
            'order' => 1,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas((new Taxonomy())->getTable(), ['id' => $categoryOne->id, 'order' => 2]);
        $this->assertDatabaseHas((new Taxonomy())->getTable(), ['id' => $categoryTwo->id, 'order' => 1]);
        $this->assertDatabaseHas((new Taxonomy())->getTable(), ['id' => $categoryThree->id, 'order' => 3]);
    }

    /**
     * @test
     */
    public function order_is_updated_when_updated_to_middle(): void
    {
        $user = User::factory()->create()->makeSuperAdmin();
        Passport::actingAs($user);

        $parentCategory = $this->createTopLevelCategory();
        $categoryOne = $parentCategory->children()->create([
            'slug' => 'one',
            'name' => 'One',
            'order' => 1,
            'depth' => 1,
        ]);
        $categoryTwo = $parentCategory->children()->create([
            'slug' => 'two',
            'name' => 'Two',
            'order' => 2,
            'depth' => 1,
        ]);
        $categoryThree = $parentCategory->children()->create([
            'slug' => 'three',
            'name' => 'Three',
            'order' => 3,
            'depth' => 1,
        ]);

        $response = $this->json('PUT', "/core/v1/taxonomies/categories/{$categoryOne->id}", [
            'parent_id' => $categoryOne->parent_id,
            'name' => $categoryOne->name,
            'order' => 2,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas((new Taxonomy())->getTable(), ['id' => $categoryOne->id, 'order' => 2]);
        $this->assertDatabaseHas((new Taxonomy())->getTable(), ['id' => $categoryTwo->id, 'order' => 1]);
        $this->assertDatabaseHas((new Taxonomy())->getTable(), ['id' => $categoryThree->id, 'order' => 3]);
    }

    /**
     * @test
     */
    public function order_is_updated_when_updated_to_end(): void
    {
        $user = User::factory()->create()->makeSuperAdmin();
        Passport::actingAs($user);

        $parentCategory = $this->createTopLevelCategory();
        $categoryOne = $parentCategory->children()->create([
            'slug' => 'one',
            'name' => 'One',
            'order' => 1,
            'depth' => 1,
        ]);
        $categoryTwo = $parentCategory->children()->create([
            'slug' => 'two',
            'name' => 'Two',
            'order' => 2,
            'depth' => 1,
        ]);
        $categoryThree = $parentCategory->children()->create([
            'slug' => 'three',
            'name' => 'Three',
            'order' => 3,
            'depth' => 1,
        ]);

        $response = $this->json('PUT', "/core/v1/taxonomies/categories/{$categoryTwo->id}", [
            'parent_id' => $categoryTwo->parent_id,
            'name' => $categoryTwo->name,
            'order' => 3,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas((new Taxonomy())->getTable(), ['id' => $categoryOne->id, 'order' => 1]);
        $this->assertDatabaseHas((new Taxonomy())->getTable(), ['id' => $categoryTwo->id, 'order' => 3]);
        $this->assertDatabaseHas((new Taxonomy())->getTable(), ['id' => $categoryThree->id, 'order' => 2]);
    }

    /**
     * @test
     */
    public function order_is_updated_when_updated_to_beginning_of_another_parent(): void
    {
        $user = User::factory()->create()->makeSuperAdmin();
        Passport::actingAs($user);

        $oldParentCategory = $this->createTopLevelCategory();
        $oldCategoryOne = $oldParentCategory->children()->create([
            'slug' => 'one',
            'name' => 'One',
            'order' => 1,
            'depth' => 1,
        ]);
        $oldCategoryTwo = $oldParentCategory->children()->create([
            'slug' => 'two',
            'name' => 'Two',
            'order' => 2,
            'depth' => 1,
        ]);
        $oldCategoryThree = $oldParentCategory->children()->create([
            'slug' => 'three',
            'name' => 'Three',
            'order' => 3,
            'depth' => 1,
        ]);

        $newParentCategory = $this->createTopLevelCategory(1);
        $newCategoryOne = $newParentCategory->children()->create([
            'slug' => 'one-1',
            'name' => 'One',
            'order' => 1,
            'depth' => 1,
        ]);
        $newCategoryTwo = $newParentCategory->children()->create([
            'slug' => 'two-1',
            'name' => 'Two',
            'order' => 2,
            'depth' => 1,
        ]);
        $newCategoryThree = $newParentCategory->children()->create([
            'slug' => 'three-1',
            'name' => 'Three',
            'order' => 3,
            'depth' => 1,
        ]);

        $response = $this->json('PUT', "/core/v1/taxonomies/categories/{$oldCategoryTwo->id}", [
            'parent_id' => $newParentCategory->id,
            'name' => $oldCategoryTwo->name,
            'order' => 1,
        ]);

        $response->assertStatus(Response::HTTP_OK);

        /*
         * Old parent.
         */
        $this->assertDatabaseHas((new Taxonomy())->getTable(), [
            'parent_id' => $oldParentCategory->id,
            'id' => $oldCategoryOne->id,
            'order' => 1,
        ]);
        $this->assertDatabaseHas((new Taxonomy())->getTable(), [
            'parent_id' => $oldParentCategory->id,
            'id' => $oldCategoryThree->id,
            'order' => 2,
        ]);

        /*
         * New parent.
         */
        $this->assertDatabaseHas((new Taxonomy())->getTable(), [
            'parent_id' => $newParentCategory->id,
            'id' => $oldCategoryTwo->id,
            'order' => 1,
        ]);
        $this->assertDatabaseHas((new Taxonomy())->getTable(), [
            'parent_id' => $newParentCategory->id,
            'id' => $newCategoryOne->id,
            'order' => 2,
        ]);
        $this->assertDatabaseHas((new Taxonomy())->getTable(), [
            'parent_id' => $newParentCategory->id,
            'id' => $newCategoryTwo->id,
            'order' => 3,
        ]);
        $this->assertDatabaseHas((new Taxonomy())->getTable(), [
            'parent_id' => $newParentCategory->id,
            'id' => $newCategoryThree->id,
            'order' => 4,
        ]);
    }

    /**
     * @test
     */
    public function order_is_updated_when_updated_to_middle_of_another_parent(): void
    {
        $user = User::factory()->create()->makeSuperAdmin();
        Passport::actingAs($user);

        $oldParentCategory = $this->createTopLevelCategory();
        $oldCategoryOne = $oldParentCategory->children()->create([
            'slug' => 'one',
            'name' => 'One',
            'order' => 1,
            'depth' => 1,
        ]);
        $oldCategoryTwo = $oldParentCategory->children()->create([
            'slug' => 'two',
            'name' => 'Two',
            'order' => 2,
            'depth' => 1,
        ]);
        $oldCategoryThree = $oldParentCategory->children()->create([
            'slug' => 'three',
            'name' => 'Three',
            'order' => 3,
            'depth' => 1,
        ]);

        $newParentCategory = $this->createTopLevelCategory(1);
        $newCategoryOne = $newParentCategory->children()->create([
            'slug' => 'one-1',
            'name' => 'One',
            'order' => 1,
            'depth' => 1,
        ]);
        $newCategoryTwo = $newParentCategory->children()->create([
            'slug' => 'two-1',
            'name' => 'Two',
            'order' => 2,
            'depth' => 1,
        ]);
        $newCategoryThree = $newParentCategory->children()->create([
            'slug' => 'three-1',
            'name' => 'Three',
            'order' => 3,
            'depth' => 1,
        ]);

        $response = $this->json('PUT', "/core/v1/taxonomies/categories/{$oldCategoryOne->id}", [
            'parent_id' => $newParentCategory->id,
            'name' => $oldCategoryOne->name,
            'order' => 2,
        ]);

        $response->assertStatus(Response::HTTP_OK);

        /*
         * Old parent.
         */
        $this->assertDatabaseHas((new Taxonomy())->getTable(), [
            'parent_id' => $oldParentCategory->id,
            'id' => $oldCategoryTwo->id,
            'order' => 1,
        ]);
        $this->assertDatabaseHas((new Taxonomy())->getTable(), [
            'parent_id' => $oldParentCategory->id,
            'id' => $oldCategoryThree->id,
            'order' => 2,
        ]);

        /*
         * New parent.
         */
        $this->assertDatabaseHas((new Taxonomy())->getTable(), [
            'parent_id' => $newParentCategory->id,
            'id' => $newCategoryOne->id,
            'order' => 1,
        ]);
        $this->assertDatabaseHas((new Taxonomy())->getTable(), [
            'parent_id' => $newParentCategory->id,
            'id' => $oldCategoryOne->id,
            'order' => 2,
        ]);
        $this->assertDatabaseHas((new Taxonomy())->getTable(), [
            'parent_id' => $newParentCategory->id,
            'id' => $newCategoryTwo->id,
            'order' => 3,
        ]);
        $this->assertDatabaseHas((new Taxonomy())->getTable(), [
            'parent_id' => $newParentCategory->id,
            'id' => $newCategoryThree->id,
            'order' => 4,
        ]);
    }

    /**
     * @test
     */
    public function order_is_updated_when_updated_to_end_of_another_parent(): void
    {
        $user = User::factory()->create()->makeSuperAdmin();
        Passport::actingAs($user);

        $oldParentCategory = $this->createTopLevelCategory();
        $oldCategoryOne = $oldParentCategory->children()->create([
            'slug' => 'one',
            'name' => 'One',
            'order' => 1,
            'depth' => 1,
        ]);
        $oldCategoryTwo = $oldParentCategory->children()->create([
            'slug' => 'two',
            'name' => 'Two',
            'order' => 2,
            'depth' => 1,
        ]);
        $oldCategoryThree = $oldParentCategory->children()->create([
            'slug' => 'three',
            'name' => 'Three',
            'order' => 3,
            'depth' => 1,
        ]);

        $newParentCategory = $this->createTopLevelCategory(1);
        $newCategoryOne = $newParentCategory->children()->create([
            'slug' => 'one-1',
            'name' => 'One',
            'order' => 1,
            'depth' => 1,
        ]);
        $newCategoryTwo = $newParentCategory->children()->create([
            'slug' => 'two-2',
            'name' => 'Two',
            'order' => 2,
            'depth' => 1,
        ]);
        $newCategoryThree = $newParentCategory->children()->create([
            'slug' => 'three-3',
            'name' => 'Three',
            'order' => 3,
            'depth' => 1,
        ]);

        $response = $this->json('PUT', "/core/v1/taxonomies/categories/{$oldCategoryTwo->id}", [
            'parent_id' => $newParentCategory->id,
            'name' => $oldCategoryTwo->name,
            'order' => 4,
        ]);

        $response->assertStatus(Response::HTTP_OK);

        /*
         * Old parent.
         */
        $this->assertDatabaseHas((new Taxonomy())->getTable(), [
            'parent_id' => $oldParentCategory->id,
            'id' => $oldCategoryOne->id,
            'order' => 1,
        ]);
        $this->assertDatabaseHas((new Taxonomy())->getTable(), [
            'parent_id' => $oldParentCategory->id,
            'id' => $oldCategoryThree->id,
            'order' => 2,
        ]);

        /*
         * New parent.
         */
        $this->assertDatabaseHas((new Taxonomy())->getTable(), [
            'parent_id' => $newParentCategory->id,
            'id' => $newCategoryOne->id,
            'order' => 1,
        ]);
        $this->assertDatabaseHas((new Taxonomy())->getTable(), [
            'parent_id' => $newParentCategory->id,
            'id' => $newCategoryTwo->id,
            'order' => 2,
        ]);
        $this->assertDatabaseHas((new Taxonomy())->getTable(), [
            'parent_id' => $newParentCategory->id,
            'id' => $newCategoryThree->id,
            'order' => 3,
        ]);
        $this->assertDatabaseHas((new Taxonomy())->getTable(), [
            'parent_id' => $newParentCategory->id,
            'id' => $oldCategoryTwo->id,
            'order' => 4,
        ]);
    }

    /**
     * @test
     */
    public function order_cannot_be_less_than_1_when_updated(): void
    {
        $user = User::factory()->create()->makeSuperAdmin();
        Passport::actingAs($user);

        $category = $this->createTopLevelCategory();

        $response = $this->json('PUT', "/core/v1/taxonomies/categories/{$category->id}", [
            'parent_id' => $category->parent_id,
            'name' => $category->name,
            'order' => 0,
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @test
     */
    public function order_cannot_be_greater_than_count_plus_1_when_updated(): void
    {
        $user = User::factory()->create()->makeSuperAdmin();
        Passport::actingAs($user);

        $category = $this->createTopLevelCategory();
        $siblingCount = Taxonomy::where('parent_id', $category->parent_id)->count();

        $response = $this->json('PUT', "/core/v1/taxonomies/categories/{$category->id}", [
            'parent_id' => $category->parent_id,
            'name' => $category->name,
            'order' => $siblingCount + 1,
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @test
     */
    public function updateTaxonomyCategoryWithUniqueSlugAsSuperAdmin200(): void
    {
        $user = User::factory()->create()->makeSuperAdmin();
        $category1 = Taxonomy::factory()->create([
            'parent_id' => $this->testCategoryRoot->id,
            'name' => 'Taxonomy Slug Test',
            'slug' => 'taxonomy-slug-test',
            'order' => 1,
        ]);
        $category2 = Taxonomy::factory()->create([
            'parent_id' => $this->testCategoryRoot->id,
            'name' => 'Other Taxonomy',
            'slug' => 'other-taxonomy',
            'order' => 2,
        ]);
        $payload = [
            'parent_id' => $this->testCategoryRoot->id,
            'name' => 'Taxonomy Slug Test',
            'order' => 2,
        ];

        $this->assertDatabaseHas('taxonomies', [
            'id' => $category1->id,
            'name' => 'Taxonomy Slug Test',
            'slug' => 'taxonomy-slug-test',
        ]);

        Passport::actingAs($user);
        $response = $this->json('PUT', "/core/v1/taxonomies/categories/{$category2->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas('taxonomies', [
            'id' => $category2->id,
            'name' => 'Taxonomy Slug Test',
            'slug' => 'taxonomy-slug-test-1',
        ]);
    }

    /**
     * @test
     */
    public function audit_created_when_updated(): void
    {
        $this->fakeEvents();

        $user = User::factory()->create()->makeSuperAdmin();
        $category = Taxonomy::factory()->create([
            'parent_id' => $this->testCategoryRoot->id,
            'order' => 1,
        ]);

        Passport::actingAs($user);
        $this->json('PUT', "/core/v1/taxonomies/categories/{$category->id}", [
            'parent_id' => $category->parent_id,
            'name' => 'PHPUnit Test Category',
            'order' => $category->order,
        ]);

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $category) {
            return ($event->getAction() === Audit::ACTION_UPDATE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $category->id);
        });
    }

    /*
     * Delete a specific category taxonomy.
     */

    /**
     * @test
     */
    public function guest_cannot_delete_one(): void
    {
        $category = $this->getRandomCategoryWithoutChildren();

        $response = $this->json('DELETE', "/core/v1/taxonomies/categories/{$category->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @test
     */
    public function service_worker_cannot_delete_one(): void
    {
        $service = Service::factory()->create();
        $user = User::factory()->create()->makeServiceWorker($service);
        $category = $this->getRandomCategoryWithoutChildren();

        Passport::actingAs($user);
        $response = $this->json('DELETE', "/core/v1/taxonomies/categories/{$category->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function service_admin_cannot_delete_one(): void
    {
        $service = Service::factory()->create();
        $user = User::factory()->create()->makeServiceAdmin($service);
        $category = $this->getRandomCategoryWithoutChildren();

        Passport::actingAs($user);
        $response = $this->json('DELETE', "/core/v1/taxonomies/categories/{$category->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function organisation_admin_cannot_delete_one(): void
    {
        $organisation = Organisation::factory()->create();
        $user = User::factory()->create()->makeOrganisationAdmin($organisation);
        $category = $this->getRandomCategoryWithoutChildren();

        Passport::actingAs($user);
        $response = $this->json('DELETE', "/core/v1/taxonomies/categories/{$category->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function global_admin_cannot_delete_one(): void
    {
        $user = User::factory()->create()->makeGlobalAdmin();
        $category = $this->getRandomCategoryWithoutChildren();

        Passport::actingAs($user);
        $response = $this->json('DELETE', "/core/v1/taxonomies/categories/{$category->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function super_admin_can_delete_one(): void
    {
        $user = User::factory()->create()->makeSuperAdmin();
        $category = $this->getRandomCategoryWithChildren();

        Passport::actingAs($user);
        $response = $this->json('DELETE', "/core/v1/taxonomies/categories/{$category->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing((new Taxonomy())->getTable(), ['id' => $category->id]);
    }

    /**
     * @test
     */
    public function audit_created_when_deleted(): void
    {
        $this->fakeEvents();

        $user = User::factory()->create()->makeSuperAdmin();
        $category = $this->getRandomCategoryWithChildren();

        Passport::actingAs($user);
        $this->json('DELETE', "/core/v1/taxonomies/categories/{$category->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $category) {
            return ($event->getAction() === Audit::ACTION_DELETE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $category->id);
        });
    }

    /*
     * Helpers.
     */

    protected function getRandomCategoryWithoutChildren(): Taxonomy
    {
        $randomTaxonomy = null;

        Taxonomy::chunk(200, function (Collection $taxonomies) use (&$randomTaxonomy) {
            foreach ($taxonomies as $taxonomy) {
                if ($taxonomy->children()->count() === 0) {
                    $randomTaxonomy = $taxonomy;

                    return false;
                }
            }
        });

        return $randomTaxonomy;
    }

    protected function getRandomCategoryWithChildren(): Taxonomy
    {
        return Taxonomy::category()->children()->inRandomOrder()->firstOrFail();
    }

    protected function createTopLevelCategory(int $index = null): Taxonomy
    {
        $topLevelCount = Taxonomy::category()->children()->count();

        return Taxonomy::category()->children()->create([
            'slug' => $index === null ? 'phpunit-category' : "phpunit-category={$index}",
            'name' => 'PHPUnit Category',
            'order' => $topLevelCount + 1,
            'depth' => 1,
        ]);
    }
}
