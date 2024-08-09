<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\File;
use App\Models\Role;
use App\Models\User;
use App\Models\Audit;
use App\Models\Service;
use App\Models\Taxonomy;
use App\Models\UserRole;
use App\Events\EndpointHit;
use App\Models\SocialMedia;
use Carbon\CarbonImmutable;
use App\Models\Organisation;
use App\Models\UpdateRequest;
use Illuminate\Http\Response;
use Laravel\Passport\Passport;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Collection;
use App\Models\OrganisationTaxonomy;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;

class OrganisationsTest extends TestCase
{
    /**
     * Create spreadsheets of organisations
     *
     * @return null
     **/
    public function createOrganisationSpreadsheets(Collection $organisations)
    {
        $headers = [
            'id',
            'name',
            'description',
            'url',
            'email',
            'phone',
        ];

        $organisations = $organisations->map(function ($organisation) {
            $organisationAttributes = $organisation->getAttributes();
            $organisationAttributes['id'] = $organisation->id ?: uuid();

            return $organisationAttributes;
        });

        $spreadsheet = \Tests\Integration\SpreadsheetParserTest::createSpreadsheets($organisations->all(), $headers);
        \Tests\Integration\SpreadsheetParserTest::writeSpreadsheetsToDisk($spreadsheet, 'test.xlsx', 'test.xls');
    }

    /*
     * List all the organisations.
     */

    /**
     * @test
     */
    public function guest_can_list_them(): void
    {
        $organisation = Organisation::factory()->withPngLogo()->create();

        $response = $this->json('GET', '/core/v1/organisations');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            [
                'id' => $organisation->id,
                'has_logo' => $organisation->hasLogo(),
                'slug' => $organisation->slug,
                'name' => $organisation->name,
                'description' => $organisation->description,
                'image' => [
                    'id' => $organisation->logoFile->id,
                    'mime_type' => $organisation->logoFile->mime_type,
                    'alt_text' => $organisation->logoFile->altText,
                    'url' => $organisation->logoFile->url(),
                ],
                'url' => $organisation->url,
                'email' => $organisation->email,
                'phone' => $organisation->phone,
                'social_medias' => [],
                'category_taxonomies' => [],
                'created_at' => $organisation->created_at->format(CarbonImmutable::ISO8601),
                'updated_at' => $organisation->updated_at->format(CarbonImmutable::ISO8601),
            ],
        ]);
    }

    /**
     * @test
     */
    public function audit_created_when_listed(): void
    {
        $this->fakeEvents();

        $this->json('GET', '/core/v1/organisations');

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) {
            return $event->getAction() === Audit::ACTION_READ;
        });
    }

    /**
     * @test
     */
    public function guest_can_sort_by_name(): void
    {
        $organisationOne = Organisation::factory()->create([
            'name' => 'Organisation A',
        ]);
        $organisationTwo = Organisation::factory()->create([
            'name' => 'Organisation B',
        ]);

        $response = $this->json('GET', '/core/v1/organisations?sort=-name');

        $data = $this->getResponseContent($response);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertEquals($organisationOne->id, $data['data'][1]['id']);
        $this->assertEquals($organisationTwo->id, $data['data'][0]['id']);
    }

    /*
     * Create an organisation.
     */

    /**
     * @test
     */
    public function guest_cannot_create_one(): void
    {
        $response = $this->json('POST', '/core/v1/organisations');

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

        $response = $this->json('POST', '/core/v1/organisations');

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

        $response = $this->json('POST', '/core/v1/organisations');

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

        $response = $this->json('POST', '/core/v1/organisations');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function global_admin_can_create_one_with_update_request(): void
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeGlobalAdmin();
        $payload = [
            'slug' => 'test-org',
            'name' => 'Test Org',
            'description' => 'Test description',
            'url' => 'http://test-org.example.com',
            'email' => 'info@test-org.example.com',
            'phone' => '07700000000',
            'social_medias' => [
                [
                    'type' => SocialMedia::TYPE_INSTAGRAM,
                    'url' => 'https://www.instagram.com/ayupdigital',
                ],
            ],
            'category_taxonomies' => [],
        ];

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/organisations', $payload);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['data' => $payload]);

        $this->assertDatabaseHas((new UpdateRequest())->getTable(), [
            'user_id' => $user->id,
            'updateable_type' => UpdateRequest::NEW_TYPE_ORGANISATION_GLOBAL_ADMIN,
            'updateable_id' => null,
        ]);

        $updateRequestId = $response->json()['id'];

        $this->assertDatabaseMissing('organisations', [
            'slug' => 'test-org',
        ]);

        $data = UpdateRequest::query()
            ->where('updateable_type', UpdateRequest::NEW_TYPE_ORGANISATION_GLOBAL_ADMIN)
            ->where('updateable_id', null)
            ->firstOrFail()->data;
        $this->assertEquals($data, $payload);

        $this->approveUpdateRequest($updateRequestId);

        $this->assertDatabaseHas('organisations', [
            'slug' => 'test-org',
        ]);
    }

    /**
     * @test
     */
    public function createOrganisationWithUniqueSlugAsGlobalAdmin200(): void
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeGlobalAdmin();
        $payload = [
            'slug' => 'test-org',
            'name' => 'Test Org',
            'description' => 'Test description',
            'url' => 'http://test-org.example.com',
            'email' => 'info@test-org.example.com',
            'phone' => '07700000000',
            'social_medias' => [
                [
                    'type' => SocialMedia::TYPE_INSTAGRAM,
                    'url' => 'https://www.instagram.com/ayupdigital',
                ],
            ],
            'category_taxonomies' => [],
        ];

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/organisations', $payload);

        $response->assertStatus(Response::HTTP_OK);

        $updateRequest1 = UpdateRequest::find($response->json('id'));

        $this->assertEquals($payload, $updateRequest1->data);

        $this->assertEquals(UpdateRequest::NEW_TYPE_ORGANISATION_GLOBAL_ADMIN, $updateRequest1->updateable_type);

        $response = $this->json('POST', '/core/v1/organisations', $payload);

        $response->assertStatus(Response::HTTP_OK);

        $updateRequest2 = UpdateRequest::find($response->json('id'));

        $this->assertEquals($payload, $updateRequest2->data);

        $this->assertEquals(UpdateRequest::NEW_TYPE_ORGANISATION_GLOBAL_ADMIN, $updateRequest2->updateable_type);

        $response = $this->json('POST', '/core/v1/organisations', $payload);

        $response->assertStatus(Response::HTTP_OK);

        $updateRequest3 = UpdateRequest::find($response->json('id'));

        $this->assertEquals($payload, $updateRequest3->data);

        $this->assertEquals(UpdateRequest::NEW_TYPE_ORGANISATION_GLOBAL_ADMIN, $updateRequest3->updateable_type);

        $this->assertDatabaseMissing('organisations', [
            'slug' => 'test-org',
        ]);

        $updateRequest1 = $this->approveUpdateRequest($updateRequest1->id);

        $this->assertDatabaseHas('organisations', [
            'id' => $updateRequest1['updateable_id'],
            'slug' => 'test-org',
        ]);

        $updateRequest2 = $this->approveUpdateRequest($updateRequest2->id);

        $this->assertDatabaseHas('organisations', [
            'id' => $updateRequest2['updateable_id'],
            'slug' => 'test-org-1',
        ]);

        $updateRequest3 = $this->approveUpdateRequest($updateRequest3->id);

        $this->assertDatabaseHas('organisations', [
            'id' => $updateRequest3['updateable_id'],
            'slug' => 'test-org-2',
        ]);
    }

    /**
     * @test
     */
    public function createOrganisationWithUniqueSlugAsSuperAdmin200(): void
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeSuperAdmin();
        $payload = [
            'slug' => 'test-org',
            'name' => 'Test Org',
            'description' => 'Test description',
            'url' => 'http://test-org.example.com',
            'email' => 'info@test-org.example.com',
            'phone' => '07700000000',
            'social_medias' => [
                [
                    'type' => SocialMedia::TYPE_INSTAGRAM,
                    'url' => 'https://www.instagram.com/ayupdigital',
                ],
            ],
            'category_taxonomies' => [],
        ];

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/organisations', $payload);

        $response->assertStatus(Response::HTTP_CREATED);

        $this->assertDatabaseHas('organisations', [
            'id' => $response->json('data.id'),
            'slug' => 'test-org',
        ]);

        $response = $this->json('POST', '/core/v1/organisations', $payload);

        $response->assertStatus(Response::HTTP_CREATED);

        $this->assertDatabaseHas('organisations', [
            'id' => $response->json('data.id'),
            'slug' => 'test-org-1',
        ]);

        $response = $this->json('POST', '/core/v1/organisations', $payload);

        $response->assertStatus(Response::HTTP_CREATED);

        $this->assertDatabaseHas('organisations', [
            'id' => $response->json('data.id'),
            'slug' => 'test-org-2',
        ]);
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
        $payload = [
            'slug' => 'test-org',
            'name' => 'Test Org',
            'description' => 'Test description',
            'url' => 'http://test-org.example.com',
            'email' => 'info@test-org.example.com',
            'phone' => '07700000000',
            'social_medias' => [
                [
                    'type' => SocialMedia::TYPE_INSTAGRAM,
                    'url' => 'https://www.instagram.com/ayupdigital',
                ],
            ],
            'category_taxonomies' => [],
        ];

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/organisations', $payload);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonFragment($payload);
    }

    /**
     * @test
     */
    public function global_admin_can_create_one_with_single_form_of_contact(): void
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeGlobalAdmin();
        $payload = [
            'slug' => 'test-org',
            'name' => 'Test Org',
            'description' => 'Test description',
            'url' => 'http://test-org.example.com',
            'email' => 'info@test-org.example.com',
            'phone' => null,
            'category_taxonomies' => [],
        ];

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/organisations', $payload);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment($payload);
    }

    /**
     * @test
     */
    public function global_admin_can_create_one_without_contact_details(): void
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeGlobalAdmin();
        $payload = [
            'slug' => 'test-org',
            'name' => 'Test Org',
            'description' => 'Test description',
            'url' => null,
            'email' => null,
            'phone' => null,
            'category_taxonomies' => [],
        ];

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/organisations', $payload);

        $response->assertStatus(Response::HTTP_OK);

        $response->assertJsonFragment($payload);
    }

    /**
     * @test
     */
    public function global_admin_cannot_create_one_with_non_numeric_phone(): void
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeGlobalAdmin();
        $payload = [
            'slug' => 'test-org',
            'name' => 'Test Org',
            'description' => 'Test description',
            'url' => null,
            'email' => null,
            'phone' => 'Tel 01234 567890',
            'category_taxonomies' => [],
        ];

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/organisations', $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @test
     */
    public function global_admin_can_create_one_with_taxonomies(): void
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeGlobalAdmin();

        $taxonomy = Taxonomy::factory()->lgaStandards()->create();

        $payload = [
            'slug' => 'test-org',
            'name' => 'Test Org',
            'description' => 'Test description',
            'url' => 'http://test-org.example.com',
            'email' => 'info@test-org.example.com',
            'phone' => null,
            'category_taxonomies' => [$taxonomy->id],
        ];

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/organisations', $payload);

        $response->assertStatus(Response::HTTP_OK);

        $response->assertJsonFragment($payload);

        $this->approveUpdateRequest($response->json()['id']);

        $organisation = Organisation::where('slug', 'test-org')->firstOrFail();
        $this->assertDatabaseHas(table(OrganisationTaxonomy::class), [
            'organisation_id' => $organisation->id,
            'taxonomy_id' => $taxonomy->id,
        ]);
        $this->assertDatabaseHas(table(OrganisationTaxonomy::class), [
            'organisation_id' => $organisation->id,
            'taxonomy_id' => $taxonomy->parent_id,
        ]);
    }

    /**
     * @test
     */
    public function global_admin_can_create_one_with_logo(): void
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeGlobalAdmin();
        Passport::actingAs($user);

        // PNG
        $image = File::factory()->imagePng()->pendingAssignment()->create();

        $payload = [
            'slug' => 'test-org-one',
            'name' => 'Test Org One',
            'description' => 'Test description',
            'url' => 'http://test-org-1.example.com',
            'email' => 'info@test-org-1.example.com',
            'phone' => null,
            'category_taxonomies' => [],
            'logo_file_id' => $image->id,
        ];

        $response = $this->json('POST', '/core/v1/organisations', $payload);

        $response->assertStatus(Response::HTTP_OK);

        $response->assertJsonFragment($payload);

        $updateRequest = UpdateRequest::find($response->json('id'));

        $this->assertEquals($updateRequest->data, $payload);

        $this->approveUpdateRequest($updateRequest->id);

        $organisation = Organisation::where('slug', 'test-org-one')->firstOrFail();
        $this->assertEquals($image->id, $organisation->logo_file_id);

        // Get the image for the organisation
        $content = $this->get("/core/v1/organisations/$organisation->id/logo.png")->content();

        $this->assertEquals(Storage::disk('local')->get('/test-data/image.png'), $content);

        // JPG
        $image = File::factory()->imageJpg()->pendingAssignment()->create();

        $payload = [
            'slug' => 'test-org-two',
            'name' => 'Test Org Two',
            'description' => 'Test description',
            'url' => 'http://test-org-2.example.com',
            'email' => 'info@test-org-2.example.com',
            'phone' => null,
            'category_taxonomies' => [],
            'logo_file_id' => $image->id,
        ];

        $response = $this->json('POST', '/core/v1/organisations', $payload);

        $response->assertStatus(Response::HTTP_OK);

        $response->assertJsonFragment($payload);

        $updateRequest = UpdateRequest::find($response->json('id'));

        $this->assertEquals($updateRequest->data, $payload);

        $this->approveUpdateRequest($updateRequest->id);

        $organisation = Organisation::where('slug', 'test-org-two')->firstOrFail();
        $this->assertEquals($image->id, $organisation->logo_file_id);

        // Get the image for the organisation
        $content = $this->get("/core/v1/organisations/$organisation->id/logo.jpg")->content();

        $this->assertEquals(Storage::disk('local')->get('/test-data/image.jpg'), $content);
    }

    /**
     * @test
     */
    public function all_global_admins_added_as_organisation_admin_when_one_is_created(): void
    {
        /**
         * @var \App\Models\User $user
         */
        $globalAdmin1 = User::factory()->create()->makeGlobalAdmin();
        $globalAdmin2 = User::factory()->create()->makeGlobalAdmin();
        $payload = [
            'slug' => 'test-org',
            'name' => 'Test Org',
            'description' => 'Test description',
            'url' => 'http://test-org.example.com',
            'email' => 'info@test-org.example.com',
            'phone' => '07700000000',
            'social_medias' => [
                [
                    'type' => SocialMedia::TYPE_INSTAGRAM,
                    'url' => 'https://www.instagram.com/ayupdigital',
                ],
            ],
            'category_taxonomies' => [],
        ];

        Passport::actingAs($globalAdmin1);

        $response = $this->json('POST', '/core/v1/organisations', $payload);

        $response->assertStatus(Response::HTTP_OK);

        $this->approveUpdateRequest($response->json()['id']);

        $organisation = Organisation::where('slug', 'test-org')->firstOrFail();

        $this->assertDatabaseHas((new UserRole)->getTable(), [
            'user_id' => $globalAdmin1->id,
            'role_id' => Role::organisationAdmin()->id,
            'organisation_id' => $organisation->id,
        ]);

        $this->assertDatabaseHas((new UserRole)->getTable(), [
            'user_id' => $globalAdmin2->id,
            'role_id' => Role::organisationAdmin()->id,
            'organisation_id' => $organisation->id,
        ]);

        $this->assertTrue($globalAdmin1->isOrganisationAdmin($organisation));
        $this->assertTrue($globalAdmin2->isOrganisationAdmin($organisation));
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

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/organisations', [
            'slug' => 'test-org',
            'name' => 'Test Org',
            'description' => 'Test description',
            'url' => 'http://test-org.example.com',
            'email' => 'info@test-org.example.com',
            'phone' => '07700000000',
            'social_medias' => [
                [
                    'type' => SocialMedia::TYPE_INSTAGRAM,
                    'url' => 'https://www.instagram.com/ayupdigital',
                ],
            ],
            'category_taxonomies' => [],
        ]);

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $response) {
            return ($event->getAction() === Audit::ACTION_CREATE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $this->getResponseContent($response)['data']['id']);
        });
    }

    /*
     * Get a specific organisation.
     */

    /**
     * @test
     */
    public function guest_can_view_one(): void
    {
        $organisation = Organisation::factory()->withPngLogo()->create();

        $organisation->socialMedias()->create([
            'type' => SocialMedia::TYPE_INSTAGRAM,
            'url' => 'https://www.instagram.com/ayupdigital',
        ]);

        $response = $this->json('GET', "/core/v1/organisations/{$organisation->id}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            [
                'id' => $organisation->id,
                'has_logo' => $organisation->hasLogo(),
                'slug' => $organisation->slug,
                'name' => $organisation->name,
                'description' => $organisation->description,
                'image' => [
                    'id' => $organisation->logoFile->id,
                    'mime_type' => $organisation->logoFile->mime_type,
                    'alt_text' => $organisation->logoFile->altText,
                    'url' => $organisation->logoFile->url(),
                ],
                'url' => $organisation->url,
                'email' => $organisation->email,
                'phone' => $organisation->phone,
                'social_medias' => [
                    [
                        'type' => SocialMedia::TYPE_INSTAGRAM,
                        'url' => 'https://www.instagram.com/ayupdigital',
                    ],
                ],
                'category_taxonomies' => [],
                'created_at' => $organisation->created_at->format(CarbonImmutable::ISO8601),
                'updated_at' => $organisation->updated_at->format(CarbonImmutable::ISO8601),
            ],
        ]);
    }

    /**
     * @test
     */
    public function guest_can_view_one_by_slug(): void
    {
        $organisation = Organisation::factory()->create();

        $response = $this->json('GET', "/core/v1/organisations/{$organisation->slug}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            [
                'id' => $organisation->id,
                'has_logo' => $organisation->hasLogo(),
                'slug' => $organisation->slug,
                'name' => $organisation->name,
                'description' => $organisation->description,
                'image' => null,
                'url' => $organisation->url,
                'email' => $organisation->email,
                'phone' => $organisation->phone,
                'social_medias' => [],
                'category_taxonomies' => [],
                'created_at' => $organisation->created_at->format(CarbonImmutable::ISO8601),
                'updated_at' => $organisation->updated_at->format(CarbonImmutable::ISO8601),
            ],
        ]);
    }

    /**
     * @test
     */
    public function audit_created_when_viewed(): void
    {
        $this->fakeEvents();

        $organisation = Organisation::factory()->create();

        $this->json('GET', "/core/v1/organisations/{$organisation->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($organisation) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getModel()->id === $organisation->id);
        });
    }

    /*
     * Update a specific organisation.
     */

    /**
     * @test
     */
    public function guest_cannot_update_one(): void
    {
        $organisation = Organisation::factory()->create();

        $response = $this->json('PUT', "/core/v1/organisations/{$organisation->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @test
     */
    public function service_worker_cannot_update_one(): void
    {
        $service = Service::factory()->create();
        $user = User::factory()->create()->makeServiceWorker($service);
        $organisation = Organisation::factory()->create();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/organisations/{$organisation->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function service_admin_cannot_update_one(): void
    {
        $service = Service::factory()->create();
        $user = User::factory()->create()->makeServiceAdmin($service);
        $organisation = Organisation::factory()->create();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/organisations/{$organisation->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function organisation_admin_can_update_one(): void
    {
        $organisation = Organisation::factory()->create();
        $user = User::factory()->create()->makeOrganisationAdmin($organisation);
        $payload = [
            'slug' => 'test-org',
            'name' => 'Test Org',
            'description' => 'Test description',
            'url' => 'http://test-org.example.com',
            'email' => 'info@test-org.example.com',
            'phone' => '07700000000',
        ];

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/organisations/{$organisation->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['data' => $payload]);
        $this->assertDatabaseHas((new UpdateRequest())->getTable(), [
            'user_id' => $user->id,
            'updateable_type' => UpdateRequest::EXISTING_TYPE_ORGANISATION,
            'updateable_id' => $organisation->id,
        ]);
        $data = UpdateRequest::query()
            ->where('updateable_type', UpdateRequest::EXISTING_TYPE_ORGANISATION)
            ->where('updateable_id', $organisation->id)
            ->firstOrFail()->data;
        $this->assertEquals($data, $payload);
    }

    /**
     * @test
     */
    public function global_admin_can_update_one(): void
    {
        $organisation = Organisation::factory()->create();
        $user = User::factory()->create()->makeGlobalAdmin();
        $payload = [
            'slug' => 'test-org',
            'name' => 'Test Org',
            'description' => 'Test description',
            'url' => 'http://test-org.example.com',
            'email' => 'info@test-org.example.com',
            'phone' => '07700000000',
            'category_taxonomies' => [],
        ];

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/organisations/{$organisation->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['data' => $payload]);
        $response->assertJsonFragment(['message' => __('updates.pending', ['appname' => config('app.name')])]);

        $this->assertDatabaseHas((new UpdateRequest())->getTable(), [
            'user_id' => $user->id,
            'updateable_type' => UpdateRequest::EXISTING_TYPE_ORGANISATION,
            'updateable_id' => $organisation->id,
            'approved_at' => null,
        ]);
    }

    /**
     * @test
     */
    public function super_admin_can_update_one_with_auto_approval(): void
    {
        $organisation = Organisation::factory()->create();
        $user = User::factory()->create()->makeSuperAdmin();
        $payload = [
            'slug' => 'test-org',
            'name' => 'Test Org',
            'description' => 'Test description',
            'url' => 'http://test-org.example.com',
            'email' => 'info@test-org.example.com',
            'phone' => '07700000000',
            'category_taxonomies' => [],
        ];

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/organisations/{$organisation->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['data' => $payload]);
        $response->assertJsonFragment(['message' => __('updates.pre-approved')]);

        $this->assertDatabaseHas((new UpdateRequest())->getTable(), [
            'user_id' => $user->id,
            'updateable_type' => UpdateRequest::EXISTING_TYPE_ORGANISATION,
            'updateable_id' => $organisation->id,
        ]);

        $data = UpdateRequest::query()
            ->where('updateable_type', UpdateRequest::EXISTING_TYPE_ORGANISATION)
            ->where('updateable_id', $organisation->id)
            ->firstOrFail()->data;
        $this->assertEquals($data, $payload);

        // Simulate frontend check by making call with UpdateRequest ID.
        $updateRequestId = json_decode($response->getContent())->id;
        Passport::actingAs($user);

        $updateRequestCheckResponse = $this->get(
            route(
                'core.v1.update-requests.show',
                ['update_request' => $updateRequestId]
            )
        );

        $updateRequestCheckResponse->assertSuccessful();
        $updateRequestResponseData = json_decode($updateRequestCheckResponse->getContent());

        // Update request should already have been approved.
        $this->assertNotNull($updateRequestResponseData->approved_at);
    }

    /**
     * @test
     */
    public function organisation_admin_can_add_social_media_to_one(): void
    {
        $organisation = Organisation::factory()->create();
        $user = User::factory()->create()->makeOrganisationAdmin($organisation);
        $payload = [
            'social_medias' => [
                [
                    'type' => SocialMedia::TYPE_INSTAGRAM,
                    'url' => 'https://www.instagram.com/ayupdigital',
                ],
            ],
        ];

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/organisations/{$organisation->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['data' => $payload]);
        $this->assertDatabaseHas((new UpdateRequest())->getTable(), [
            'user_id' => $user->id,
            'updateable_type' => UpdateRequest::EXISTING_TYPE_ORGANISATION,
            'updateable_id' => $organisation->id,
        ]);
        $updateRequest = UpdateRequest::query()
            ->where('updateable_type', UpdateRequest::EXISTING_TYPE_ORGANISATION)
            ->where('updateable_id', $organisation->id)
            ->firstOrFail();
        $this->assertEquals($updateRequest->data, $payload);
        $updateRequest->apply(User::factory()->create()->makeGlobalAdmin());
        $organisation->refresh();
        $this->assertCount(1, $organisation->socialMedias);
        $this->assertInstanceOf(SocialMedia::class, $organisation->socialMedias->first());
        $this->assertEquals('https://www.instagram.com/ayupdigital', $organisation->socialMedias->first()->url);
    }

    /**
     * @test
     */
    public function organisation_admin_can_remove_social_media_from_one(): void
    {
        $organisation = Organisation::factory()->socialMedia()->create();
        $user = User::factory()->create()->makeOrganisationAdmin($organisation);
        $payload = [
            'social_medias' => [],
        ];

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/organisations/{$organisation->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['data' => $payload]);
        $this->assertDatabaseHas((new UpdateRequest())->getTable(), [
            'user_id' => $user->id,
            'updateable_type' => UpdateRequest::EXISTING_TYPE_ORGANISATION,
            'updateable_id' => $organisation->id,
        ]);
        $updateRequest = UpdateRequest::query()
            ->where('updateable_type', UpdateRequest::EXISTING_TYPE_ORGANISATION)
            ->where('updateable_id', $organisation->id)
            ->firstOrFail();
        $this->assertEquals($updateRequest->data, $payload);
        $updateRequest->apply(User::factory()->create()->makeGlobalAdmin());
        $organisation->refresh();
        $this->assertCount(0, $organisation->socialMedias);
    }

    /**
     * @test
     */
    public function organisation_admin_can_add_additional_social_media_to_one(): void
    {
        $organisation = Organisation::factory()->create();
        $organisation->socialMedias()->create([
            'type' => SocialMedia::TYPE_TWITTER,
            'url' => 'https://twitter.com/ayupdigital/',
        ]);
        $user = User::factory()->create()->makeOrganisationAdmin($organisation);
        $payload = [
            'social_medias' => [
                [
                    'type' => SocialMedia::TYPE_TWITTER,
                    'url' => 'https://twitter.com/ayupdigital/',
                ],
                [
                    'type' => SocialMedia::TYPE_INSTAGRAM,
                    'url' => 'https://www.instagram.com/ayupdigital',
                ],
            ],
        ];

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/organisations/{$organisation->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['data' => $payload]);
        $this->assertDatabaseHas((new UpdateRequest())->getTable(), [
            'user_id' => $user->id,
            'updateable_type' => UpdateRequest::EXISTING_TYPE_ORGANISATION,
            'updateable_id' => $organisation->id,
        ]);
        $updateRequest = UpdateRequest::query()
            ->where('updateable_type', UpdateRequest::EXISTING_TYPE_ORGANISATION)
            ->where('updateable_id', $organisation->id)
            ->firstOrFail();
        $this->assertEquals($updateRequest->data, $payload);
        $updateRequest->apply(User::factory()->create()->makeGlobalAdmin());
        $organisation->refresh();
        $this->assertCount(2, $organisation->socialMedias);
    }

    /**
     * @test
     */
    public function organisation_admin_can_update_with_no_form_of_contact(): void
    {
        $organisation = Organisation::factory()->create();
        $user = User::factory()->create()->makeOrganisationAdmin($organisation);
        $payload = [
            'slug' => 'test-org',
            'name' => 'Test Org',
            'description' => 'Test description',
            'url' => null,
            'email' => null,
            'phone' => null,
        ];

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/organisations/{$organisation->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['data' => $payload]);
        $this->assertDatabaseHas((new UpdateRequest())->getTable(), [
            'user_id' => $user->id,
            'updateable_type' => UpdateRequest::EXISTING_TYPE_ORGANISATION,
            'updateable_id' => $organisation->id,
        ]);
        $data = UpdateRequest::query()
            ->where('updateable_type', UpdateRequest::EXISTING_TYPE_ORGANISATION)
            ->where('updateable_id', $organisation->id)
            ->firstOrFail()->data;
        $this->assertEquals($data, $payload);
    }

    /**
     * @test
     */
    public function global_admin_can_update_without_contact_details(): void
    {
        $organisation = Organisation::factory()->create([
            'url' => 'http://test-org.example.com',
            'email' => 'info@test-org.example.com',
            'phone' => '01234567890',
        ]);
        $user = User::factory()->create()->makeGlobalAdmin($organisation);

        Passport::actingAs($user);

        $payload = [
            'slug' => 'test-org',
            'name' => 'Test Org',
            'description' => 'Test description',
            'url' => null,
            'email' => null,
            'phone' => null,
        ];

        $response = $this->json('PUT', "/core/v1/organisations/{$organisation->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);

        $response->assertJsonFragment(['data' => $payload]);
    }

    /**
     * @test
     */
    public function global_admin_cannot_update_with_non_numeric_phone(): void
    {
        $organisation = Organisation::factory()->create([
            'url' => 'http://test-org.example.com',
            'email' => 'info@test-org.example.com',
            'phone' => '01234567890',
        ]);
        $user = User::factory()->create()->makeGlobalAdmin($organisation);

        Passport::actingAs($user);

        $payload = [
            'slug' => 'test-org',
            'name' => 'Test Org',
            'description' => 'Test description',
            'url' => null,
            'email' => null,
            'phone' => 'Tel 01234 567890',
        ];

        $response = $this->json('PUT', "/core/v1/organisations/{$organisation->id}", $payload);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    /**
     * @test
     */
    public function organisation_admin_can_update_organisation_taxonomies(): void
    {
        $organisation = Organisation::factory()->create();
        $taxonomy1 = Taxonomy::factory()->create();
        $taxonomy2 = Taxonomy::factory()->create();
        $organisation->syncTaxonomyRelationships(collect([$taxonomy1]));
        $user = User::factory()->create()->makeOrganisationAdmin($organisation);
        $payload = [
            'slug' => 'test-org',
            'name' => 'Test Org',
            'description' => 'Test description',
            'url' => 'http://test-org.example.com',
            'email' => 'info@test-org.example.com',
            'phone' => null,
            'category_taxonomies' => [$taxonomy1->id, $taxonomy2->id],
        ];

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/organisations/{$organisation->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['data' => $payload]);
        $response->assertJsonFragment(['message' => __('updates.pending', ['appname' => config('app.name')])]);
        $this->assertDatabaseHas((new UpdateRequest())->getTable(), [
            'user_id' => $user->id,
            'updateable_type' => UpdateRequest::EXISTING_TYPE_ORGANISATION,
            'updateable_id' => $organisation->id,
        ]);

        $data = UpdateRequest::query()
            ->where('updateable_type', UpdateRequest::EXISTING_TYPE_ORGANISATION)
            ->where('updateable_id', $organisation->id)
            ->firstOrFail()->data;
        $this->assertEquals($data, $payload);

        $this->assertDatabaseHas(table(OrganisationTaxonomy::class), [
            'organisation_id' => $organisation->id,
            'taxonomy_id' => $taxonomy1->id,
        ]);
        $this->assertDatabaseMissing(table(OrganisationTaxonomy::class), [
            'organisation_id' => $organisation->id,
            'taxonomy_id' => $taxonomy2->id,
        ]);
    }

    /**
     * @test
     */
    public function updateOrganisationWithUniqueSlugAsOrganisationAdmin200(): void
    {
        $organisation1 = Organisation::factory()->create([
            'slug' => 'test-org',
        ]);
        $organisation2 = Organisation::factory()->create([
            'slug' => 'other-org',
        ]);
        $organisation3 = Organisation::factory()->create([
            'slug' => 'yet-another-org',
        ]);
        $user = User::factory()->create()->makeOrganisationAdmin($organisation2);
        $user->makeOrganisationAdmin($organisation3);
        $payload = [
            'slug' => 'test-org',
        ];

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/organisations/{$organisation2->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);

        $updateRequest1 = UpdateRequest::find($response->json('id'));

        $this->assertEquals('test-org', $updateRequest1->data['slug']);
        $this->assertEquals(UpdateRequest::EXISTING_TYPE_ORGANISATION, $updateRequest1->updateable_type);

        $response = $this->json('PUT', "/core/v1/organisations/{$organisation3->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);

        $updateRequest2 = UpdateRequest::find($response->json('id'));

        $this->assertEquals('test-org', $updateRequest2->data['slug']);
        $this->assertEquals(UpdateRequest::EXISTING_TYPE_ORGANISATION, $updateRequest2->updateable_type);

        $this->approveUpdateRequest($updateRequest1->id);

        // The organisation is updated
        $this->assertDatabaseHas('organisations', [
            'id' => $organisation2->id,
            'slug' => 'test-org-1',
        ]);

        $this->approveUpdateRequest($updateRequest2->id);

        // The organisation is updated
        $this->assertDatabaseHas('organisations', [
            'id' => $organisation3->id,
            'slug' => 'test-org-2',
        ]);
    }

    /**
     * @test
     */
    public function updateOrganisationWithUniqueSlugAsSuperAdmin200(): void
    {
        $organisation1 = Organisation::factory()->create([
            'slug' => 'test-org',
        ]);
        $organisation2 = Organisation::factory()->create([
            'slug' => 'other-org',
        ]);
        $organisation3 = Organisation::factory()->create([
            'slug' => 'yet-another-org',
        ]);
        $user = User::factory()->create()->makeSuperAdmin();

        $payload = [
            'slug' => 'test-org',
        ];

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/organisations/{$organisation2->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);

        // The organisation is updated
        $this->assertDatabaseHas('organisations', [
            'id' => $organisation2->id,
            'slug' => 'test-org-1',
        ]);

        $response = $this->json('PUT', "/core/v1/organisations/{$organisation3->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);

        // The organisation is updated
        $this->assertDatabaseHas('organisations', [
            'id' => $organisation3->id,
            'slug' => 'test-org-2',
        ]);
    }

    /**
     * @test
     */
    public function global_admin_can_update_organisation_taxonomies(): void
    {
        $organisation = Organisation::factory()->create();
        $taxonomy1 = Taxonomy::factory()->create();
        $taxonomy2 = Taxonomy::factory()->create();
        $taxonomy3 = Taxonomy::factory()->create();
        $organisation->syncTaxonomyRelationships(collect([$taxonomy1]));

        $this->assertDatabaseHas(table(OrganisationTaxonomy::class), [
            'organisation_id' => $organisation->id,
            'taxonomy_id' => $taxonomy1->id,
        ]);

        $user = User::factory()->create()->makeGlobalAdmin();
        $payload = [
            'slug' => 'test-org',
            'name' => 'Test Org',
            'description' => 'Test description',
            'url' => 'http://test-org.example.com',
            'email' => 'info@test-org.example.com',
            'phone' => null,
            'category_taxonomies' => [$taxonomy2->id, $taxonomy3->id],
        ];

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/organisations/{$organisation->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);

        $response->assertJsonFragment(['data' => $payload]);
        $response->assertJsonFragment(['message' => __('updates.pending', ['appname' => config('app.name')])]);

        $this->approveUpdateRequest($response->json()['id']);

        $this->assertDatabaseHas(table(OrganisationTaxonomy::class), [
            'organisation_id' => $organisation->id,
            'taxonomy_id' => $taxonomy2->id,
        ]);
        $this->assertDatabaseHas(table(OrganisationTaxonomy::class), [
            'organisation_id' => $organisation->id,
            'taxonomy_id' => $taxonomy3->id,
        ]);
        $this->assertDatabaseHas(table(OrganisationTaxonomy::class), [
            'organisation_id' => $organisation->id,
            'taxonomy_id' => $taxonomy2->parent_id,
        ]);
        $this->assertDatabaseMissing(table(OrganisationTaxonomy::class), [
            'organisation_id' => $organisation->id,
            'taxonomy_id' => $taxonomy1->id,
        ]);

        $responsePayload = $payload;
        $responsePayload['category_taxonomies'] = [
            [
                'id' => $taxonomy2->parent->id,
                'parent_id' => $taxonomy2->parent->parent_id,
                'slug' => $taxonomy2->parent->slug,
                'name' => $taxonomy2->parent->name,
                'created_at' => $taxonomy2->parent->created_at->format(CarbonImmutable::ISO8601),
                'updated_at' => $taxonomy2->parent->updated_at->format(CarbonImmutable::ISO8601),
            ],
            [
                'id' => $taxonomy2->id,
                'parent_id' => $taxonomy2->parent_id,
                'slug' => $taxonomy2->slug,
                'name' => $taxonomy2->name,
                'created_at' => $taxonomy2->created_at->format(CarbonImmutable::ISO8601),
                'updated_at' => $taxonomy2->updated_at->format(CarbonImmutable::ISO8601),
            ],
            [
                'id' => $taxonomy3->id,
                'parent_id' => $taxonomy3->parent_id,
                'slug' => $taxonomy3->slug,
                'name' => $taxonomy3->name,
                'created_at' => $taxonomy3->created_at->format(CarbonImmutable::ISO8601),
                'updated_at' => $taxonomy3->updated_at->format(CarbonImmutable::ISO8601),
            ],
        ];
        $response = $this->json('GET', "/core/v1/organisations/{$organisation->id}");
        $response->assertJsonFragment($responsePayload);
    }

    /**
     * @test
     */
    public function only_partial_fields_can_be_updated(): void
    {
        $organisation = Organisation::factory()->create();
        $user = User::factory()->create()->makeOrganisationAdmin($organisation);
        $payload = [
            'slug' => 'test-org',
        ];

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/organisations/{$organisation->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['data' => $payload]);
        $this->assertDatabaseHas((new UpdateRequest())->getTable(), [
            'user_id' => $user->id,
            'updateable_type' => UpdateRequest::EXISTING_TYPE_ORGANISATION,
            'updateable_id' => $organisation->id,
        ]);
        $data = UpdateRequest::query()
            ->where('updateable_type', UpdateRequest::EXISTING_TYPE_ORGANISATION)
            ->where('updateable_id', $organisation->id)
            ->firstOrFail()->data;
        $this->assertEquals($data, $payload);
    }

    /**
     * @test
     */
    public function organisationAdminCanUpdateLogo(): void
    {
        $organisation = Organisation::factory()->withPngLogo()->create();
        $user = User::factory()->create()->makeOrganisationAdmin($organisation);

        Passport::actingAs($user);

        // PNG
        $image = File::factory()->imagePng()->pendingAssignment()->create();
        $payload = [
            'logo_file_id' => $image->id,
        ];

        $response = $this->json('PUT', "/core/v1/organisations/{$organisation->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);

        $response->assertJsonFragment(['data' => $payload]);

        $updateRequest = UpdateRequest::find($response->json('id'));

        $this->assertEquals($updateRequest->data, $payload);

        $this->approveUpdateRequest($updateRequest->id);

        // Get the image for the organisation logo
        $content = $this->get("/core/v1/organisations/$organisation->id/logo.png")->content();

        $this->assertEquals(Storage::disk('local')->get('/test-data/image.png'), $content);

        // JPG
        $image = File::factory()->imageJpg()->pendingAssignment()->create();
        $payload = [
            'logo_file_id' => $image->id,
        ];

        $response = $this->json('PUT', "/core/v1/organisations/{$organisation->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);

        $response->assertJsonFragment(['data' => $payload]);

        $updateRequest = UpdateRequest::find($response->json('id'));

        $this->assertEquals($updateRequest->data, $payload);

        $this->approveUpdateRequest($updateRequest->id);

        // Get the image for the organisation logo
        $content = $this->get("/core/v1/organisations/$organisation->id/logo.jpg")->content();

        $this->assertEquals(Storage::disk('local')->get('/test-data/image.jpg'), $content);

        // SVG
        $image = File::factory()->imageSvg()->pendingAssignment()->create();
        $payload = [
            'logo_file_id' => $image->id,
        ];

        $response = $this->json('PUT', "/core/v1/organisations/{$organisation->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);

        $response->assertJsonFragment(['data' => $payload]);

        $updateRequest = UpdateRequest::find($response->json('id'));

        $this->assertEquals($updateRequest->data, $payload);

        $this->approveUpdateRequest($updateRequest->id);

        // Get the image for the organisation logo
        $content = $this->get("/core/v1/organisations/$organisation->id/logo.svg")->content();

        $this->assertEquals(Storage::disk('local')->get('/test-data/image.svg'), $content);
    }

    /**
     * @test
     */
    public function audit_created_when_updated(): void
    {
        $this->fakeEvents();

        $organisation = Organisation::factory()->create();
        $user = User::factory()->create()->makeOrganisationAdmin($organisation);

        Passport::actingAs($user);

        $this->json('PUT', "/core/v1/organisations/{$organisation->id}", [
            'slug' => 'test-org',
            'name' => 'Test Org',
            'description' => 'Test description',
            'url' => 'http://test-org.example.com',
            'email' => 'info@test-org.example.com',
            'phone' => '07700000000',
        ]);

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $organisation) {
            return ($event->getAction() === Audit::ACTION_UPDATE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $organisation->id);
        });
    }

    /**
     * @test
     */
    public function fields_removed_for_existing_update_requests(): void
    {
        $organisation = Organisation::factory()->create();
        $user = User::factory()->create()->makeOrganisationAdmin($organisation);

        Passport::actingAs($user);

        $responseOne = $this->json('PUT', "/core/v1/organisations/{$organisation->id}", [
            'name' => 'Random 1',
        ]);
        $responseOne->assertStatus(Response::HTTP_OK);

        $responseTwo = $this->json('PUT', "/core/v1/organisations/{$organisation->id}", [
            'name' => 'Random 2',
            'slug' => 'random-1',
        ]);
        $responseTwo->assertStatus(Response::HTTP_OK);

        $updateRequestOne = UpdateRequest::withTrashed()->findOrFail($this->getResponseContent($responseOne)['id']);
        $updateRequestTwo = UpdateRequest::findOrFail($this->getResponseContent($responseTwo)['id']);

        $this->assertArrayNotHasKey('name', $updateRequestOne->data);
        $this->assertArrayHasKey('name', $updateRequestTwo->data);
        $this->assertArrayHasKey('slug', $updateRequestTwo->data);
        $this->assertSoftDeleted($updateRequestOne->getTable(), ['id' => $updateRequestOne->id]);
    }

    /*
     * Delete a specific organisation.
     */

    /**
     * @test
     */
    public function guest_cannot_delete_one(): void
    {
        $organisation = Organisation::factory()->create();

        $response = $this->json('DELETE', "/core/v1/organisations/{$organisation->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @test
     */
    public function service_worker_cannot_delete_one(): void
    {
        $service = Service::factory()->create();
        $user = User::factory()->create()->makeServiceWorker($service);
        $organisation = Organisation::factory()->create();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/organisations/{$organisation->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function service_admin_cannot_delete_one(): void
    {
        $service = Service::factory()->create();
        $user = User::factory()->create()->makeServiceAdmin($service);
        $organisation = Organisation::factory()->create();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/organisations/{$organisation->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function organisation_admin_cannot_delete_one(): void
    {
        $organisation = Organisation::factory()->create();
        $user = User::factory()->create()->makeOrganisationAdmin($organisation);

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/organisations/{$organisation->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function global_admin_cannot_delete_one(): void
    {
        $organisation = Organisation::factory()->create();
        $user = User::factory()->create()->makeGlobalAdmin();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/organisations/{$organisation->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function super_admin_can_delete_one(): void
    {
        $organisation = Organisation::factory()->create();
        $user = User::factory()->create()->makeSuperAdmin();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/organisations/{$organisation->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing((new Organisation())->getTable(), ['id' => $organisation->id]);
    }

    /**
     * @test
     */
    public function related_social_media_are_deleted_when_deleting_one(): void
    {
        $organisation = Organisation::factory()->create();
        $organisation->socialMedias()->create([
            'type' => SocialMedia::TYPE_TWITTER,
            'url' => 'https://twitter.com/ayupdigital/',
        ]);
        $user = User::factory()->create()->makeSuperAdmin();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/organisations/{$organisation->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing((new SocialMedia())->getTable(), ['url' => 'https://twitter.com/ayupdigital/']);
    }

    /**
     * @test
     */
    public function deleteOrganisationWithUpdateRequestsAsSuperAdmin200(): void
    {
        $organisation = Organisation::factory()->create();

        $user = User::factory()->create()->makeGlobalAdmin($organisation);

        Passport::actingAs($user);

        $payload = [
            'slug' => 'test-org',
            'name' => 'Test Org',
            'description' => 'Test description',
            'url' => null,
            'email' => null,
            'phone' => null,
        ];

        $response = $this->json('PUT', "/core/v1/organisations/{$organisation->id}", $payload);

        $updateRequest = UpdateRequest::findOrFail($response->json('id'));
        $this->assertEquals($updateRequest->data, $payload);

        Passport::actingAs(User::factory()->create()->makeSuperAdmin());

        $response = $this->json('DELETE', "/core/v1/organisations/{$organisation->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing((new Organisation())->getTable(), ['id' => $organisation->id]);
        $this->assertDatabaseMissing('update_requests', ['id' => $updateRequest->id, 'deleted_at' => null]);
    }

    /**
     * @test
     */
    public function deleteOrganisationWithTaxonomiesAsSuperAdmin200(): void
    {
        $organisation = Organisation::factory()->create();
        $taxonomy = Taxonomy::factory()->create();
        $organisation->syncTaxonomyRelationships(collect([$taxonomy]));
        $user = User::factory()->create()->makeSuperAdmin();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/organisations/{$organisation->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing((new Organisation())->getTable(), ['id' => $organisation->id]);
        $this->assertDatabaseMissing((new OrganisationTaxonomy())->getTable(), [
            'organisation_id' => $organisation->id,
            'taxonomy_id' => $taxonomy->id,
        ]);
    }

    /**
     * @test
     */
    public function audit_created_when_deleted(): void
    {
        $this->fakeEvents();

        $organisation = Organisation::factory()->create();
        $user = User::factory()->create()->makeSuperAdmin();

        Passport::actingAs($user);

        $this->json('DELETE', "/core/v1/organisations/{$organisation->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $organisation) {
            return ($event->getAction() === Audit::ACTION_DELETE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $organisation->id);
        });
    }

    /*
     * Get a specific organisation's logo.
     */

    /**
     * @test
     */
    public function guest_can_view_logo(): void
    {
        $organisation = Organisation::factory()->create();

        $response = $this->get("/core/v1/organisations/{$organisation->id}/logo.png");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'image/png');
    }

    /**
     * @test
     */
    public function audit_created_when_logo_viewed(): void
    {
        $this->fakeEvents();

        $organisation = Organisation::factory()->create();

        $this->get("/core/v1/organisations/{$organisation->id}/logo.png");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($organisation) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getModel()->id === $organisation->id);
        });
    }

    /*
     * Upload a specific organisation's logo.
     */

    /**
     * @test
     */
    public function global_admin_can_upload_logo(): void
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeGlobalAdmin();
        $image = Storage::disk('local')->get('/test-data/image.png');

        Passport::actingAs($user);

        $imageResponse = $this->json('POST', '/core/v1/files', [
            'is_private' => false,
            'mime_type' => 'image/png',
            'alt_text' => 'image description',
            'file' => 'data:image/png;base64,' . base64_encode($image),
        ]);
        $imageResponse->assertStatus(Response::HTTP_CREATED);

        $response = $this->json('POST', '/core/v1/organisations', [
            'slug' => 'test-org',
            'name' => 'Test Org',
            'description' => 'Test description',
            'url' => 'http://test-org.example.com',
            'email' => 'info@test-org.example.com',
            'phone' => '07700000000',
            'category_taxonomies' => [],
            'logo_file_id' => $this->getResponseContent($imageResponse, 'data.id'),
        ]);

        $response->assertStatus(Response::HTTP_OK);

        $this->approveUpdateRequest($response->json()['id']);

        $this->assertDatabaseHas(table(Organisation::class), [
            'slug' => 'test-org',
        ]);
        $this->assertDatabaseMissing(table(Organisation::class), [
            'slug' => 'test-org',
            'logo_file_id' => null,
        ]);
    }

    /*
     * Delete a specific organisation's logo.
     */

    /**
     * @test
     */
    public function organisation_admin_can_delete_logo(): void
    {
        /**
         * @var \App\Models\User $user
         */
        $organisation = Organisation::factory()->create([
            'logo_file_id' => File::factory()->create()->id,
        ]);
        $user = User::factory()->create()->makeOrganisationAdmin($organisation);
        $payload = [
            'slug' => $organisation->slug,
            'name' => $organisation->name,
            'description' => $organisation->description,
            'url' => $organisation->url,
            'email' => $organisation->email,
            'phone' => $organisation->phone,
            'logo_file_id' => null,
        ];

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/organisations/{$organisation->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas(table(UpdateRequest::class), ['updateable_id' => $organisation->id]);
        $updateRequest = UpdateRequest::where('updateable_id', $organisation->id)->firstOrFail();
        $this->assertEquals(null, $updateRequest->data['logo_file_id']);
    }

    /**
     * Bulk import organisations
     */
    /**
     * @test
     */
    public function guest_cannot_bulk_import(): void
    {
        Storage::fake('local');

        $organisations = Organisation::factory()->count(2)->make();

        $this->createOrganisationSpreadsheets($organisations);

        $data = [
            'spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xls'))),
        ];
        $response = $this->json('POST', '/core/v1/organisations/import', $data);

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @test
     */
    public function service_worker_cannot_bulk_import(): void
    {
        Storage::fake('local');

        $organisations = Organisation::factory()->count(2)->make();

        $this->createOrganisationSpreadsheets($organisations);

        $data = [
            'spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xls'))),
        ];

        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceWorker($service);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/organisations/import', $data);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function service_admin_cannot_bulk_import(): void
    {
        Storage::fake('local');

        $organisations = Organisation::factory()->count(2)->make();

        $this->createOrganisationSpreadsheets($organisations);

        $data = [
            'spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xls'))),
        ];

        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceAdmin($service);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/organisations/import', $data);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function organisation_admin_cannot_bulk_import(): void
    {
        Storage::fake('local');

        $organisations = Organisation::factory()->count(2)->make();

        $this->createOrganisationSpreadsheets($organisations);

        $data = [
            'spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xls'))),
        ];
        $organisation = Organisation::factory()->create();
        $user = User::factory()->create();
        $user->makeOrganisationAdmin($organisation);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/organisations/import', $data);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function global_admin_cannot_bulk_import(): void
    {
        Storage::fake('local');

        $organisations = Organisation::factory()->count(2)->make();

        $this->createOrganisationSpreadsheets($organisations);

        $data = [
            'spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xls'))),
        ];

        $user = User::factory()->create();
        $user->makeGlobalAdmin();
        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/organisations/import', $data);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     */
    public function super_admin_can_bulk_import(): void
    {
        Storage::fake('local');

        $organisations = Organisation::factory()->count(2)->make();

        $this->createOrganisationSpreadsheets($organisations);

        $data = [
            'spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xls'))),
        ];

        $user = User::factory()->create();
        $user->makeSuperAdmin();
        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/organisations/import', $data);

        $response->assertStatus(Response::HTTP_CREATED);
    }

    /**
     * @test
     */
    public function super_admin_can_bulk_import_with_minimal_fields(): void
    {
        Storage::fake('local');

        $organisations = Organisation::factory()->count(2)->make([
            'phone' => '',
        ]);

        $this->createOrganisationSpreadsheets($organisations);

        $data = [
            'spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xls'))),
        ];

        $user = User::factory()->create();
        $user->makeSuperAdmin();
        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/organisations/import', $data);

        $response->assertStatus(Response::HTTP_CREATED);

        $response->assertJson([
            'data' => [
                'imported_row_count' => 2,
            ],
        ]);
    }

    /**
     * @test
     */
    public function super_admin_can_view_bulk_imported_organisation(): void
    {
        Storage::fake('local');

        $organisations = Organisation::factory()->count(2)->make();

        $this->createOrganisationSpreadsheets($organisations);

        $data = [
            'spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xls'))),
        ];

        $user = User::factory()->create();
        $user->makeSuperAdmin();
        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/organisations/import', $data);

        $response->assertStatus(Response::HTTP_CREATED);

        $response = $this->json('GET', '/core/v1/organisations?filter=[has_permission]=true');

        $response->assertStatus(Response::HTTP_OK);

        $response->assertJsonFragment([
            'name' => $organisations->get(0)->name,
        ]);

        $response->assertJsonFragment([
            'name' => $organisations->get(1)->name,
        ]);
    }

    /**
     * @test
     */
    public function global_admin_can_view_bulk_imported_organisation(): void
    {
        Storage::fake('local');

        $organisations = Organisation::factory()->count(2)->make();

        $this->createOrganisationSpreadsheets($organisations);

        $data = [
            'spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xls'))),
        ];

        $super = User::factory()->create();
        $super->makeSuperAdmin();
        $global = User::factory()->create();
        $global->makeGlobalAdmin();
        Passport::actingAs($super);

        $response = $this->json('POST', '/core/v1/organisations/import', $data);

        $response->assertStatus(Response::HTTP_CREATED);

        Passport::actingAs($global);

        $response = $this->json('GET', '/core/v1/organisations?filter=[has_permission]=true&page=1&sort=name');

        $response->assertStatus(Response::HTTP_OK);

        $response->assertJsonFragment([
            'name' => $organisations->get(0)->name,
        ]);

        $response->assertJsonFragment([
            'name' => $organisations->get(1)->name,
        ]);

        $organisation1Id = Organisation::where('name', $organisations->get(0)->name)->value('id');
        $organisation2Id = Organisation::where('name', $organisations->get(1)->name)->value('id');

        $this->assertDatabaseHas((new UserRole())->getTable(), [
            'user_id' => $global->id,
            'role_id' => Role::organisationAdmin()->id,
            'service_id' => null,
            'organisation_id' => $organisation1Id,
        ]);

        $this->assertDatabaseHas((new UserRole())->getTable(), [
            'user_id' => $global->id,
            'role_id' => Role::organisationAdmin()->id,
            'service_id' => null,
            'organisation_id' => $organisation2Id,
        ]);
    }

    /**
     * @test
     */
    public function validate_file_import_type(): void
    {
        Storage::fake('local');

        $invalidFieldTypes = [
            ['spreadsheet' => 'This is a string'],
            ['spreadsheet' => 1],
            ['spreadsheet' => ['foo' => 'bar']],
            ['spreadsheet' => UploadedFile::fake()->create('dummy.doc', 3000)],
            ['spreadsheet' => UploadedFile::fake()->create('dummy.txt', 3000)],
            ['spreadsheet' => UploadedFile::fake()->create('dummy.csv', 3000)],
        ];

        $user = User::factory()->create();
        $user->makeSuperAdmin();
        Passport::actingAs($user);

        foreach ($invalidFieldTypes as $data) {
            $response = $this->json('POST', '/core/v1/organisations/import', $data);
            $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        $organisations = Organisation::factory()->count(2)->make();

        $this->createOrganisationSpreadsheets($organisations);

        $response = $this->json('POST', '/core/v1/organisations/import', ['spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xls')))]);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJson([
            'data' => [
                'imported_row_count' => 2,
            ],
        ]);

        $organisations = Organisation::factory()->count(2)->make();

        $this->createOrganisationSpreadsheets($organisations);

        $response = $this->json('POST', '/core/v1/organisations/import', ['spreadsheet' => 'data:application/octet-stream;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xls')))]);
        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJson([
            'data' => [
                'imported_row_count' => 2,
            ],
        ]);

        $organisations = Organisation::factory()->count(2)->make();

        $this->createOrganisationSpreadsheets($organisations);

        $response = $this->json('POST', '/core/v1/organisations/import', ['spreadsheet' => 'data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xlsx')))]);
        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJson([
            'data' => [
                'imported_row_count' => 2,
            ],
        ]);
    }

    /**
     * @test
     */
    public function validate_file_import_fields(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $user->makeSuperAdmin();
        Passport::actingAs($user);

        $organisation1 = Organisation::factory()->make([
            'name' => '',
            'url' => 'www.foo.com',
            'email' => 'foo.com',
        ]);

        $organisation2 = Organisation::factory()->make([
            'email' => '',
            'phone' => '',
        ]);

        $organisation3 = Organisation::factory()->make([
            'id' => Organisation::factory()->create()->id,
        ]);

        $this->createOrganisationSpreadsheets(collect([$organisation1, $organisation2, $organisation3]));

        $response = $this->json('POST', '/core/v1/organisations/import', ['spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xls')))]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJson([
            'data' => [
                'errors' => [
                    'spreadsheet' => [
                        [
                            'row' => [],
                            'errors' => [
                                'name' => [],
                                'url' => [],
                                'email' => [],
                            ],
                        ],
                        [
                            'row' => [],
                            'errors' => [
                                'email' => [],
                                'phone' => [],
                            ],
                        ],
                        [
                            'row' => [],
                            'errors' => [
                                'id' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ]);

        $response = $this->json('POST', '/core/v1/organisations/import', ['spreadsheet' => 'data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xlsx')))]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJson([
            'data' => [
                'errors' => [
                    'spreadsheet' => [
                        [
                            'row' => [],
                            'errors' => [
                                'name' => [],
                                'url' => [],
                                'email' => [],
                            ],
                        ],
                        [
                            'row' => [],
                            'errors' => [
                                'email' => [],
                                'phone' => [],
                            ],
                        ],
                        [
                            'row' => [],
                            'errors' => [
                                'id' => [],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
    }

    /**
     * @test
     */
    public function organisations_file_import_100rows(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $user->makeSuperAdmin();
        Passport::actingAs($user);

        $organisations = Organisation::factory()->count(100)->make();

        $this->createOrganisationSpreadsheets($organisations);

        $response = $this->json('POST', '/core/v1/organisations/import', ['spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xls')))]);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJson([
            'data' => [
                'imported_row_count' => 100,
            ],
        ]);

        $organisations = Organisation::factory()->count(100)->make();

        $this->createOrganisationSpreadsheets($organisations);

        $response = $this->json('POST', '/core/v1/organisations/import', ['spreadsheet' => 'data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xlsx')))]);
        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJson([
            'data' => [
                'imported_row_count' => 100,
            ],
        ]);
    }

    /**
     * @group slow
     */
    /**
     * @test
     */
    public function organisations_file_import_5krows(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $user->makeSuperAdmin();
        Passport::actingAs($user);

        $organisations = Organisation::factory()->count(5000)->make();

        $this->createOrganisationSpreadsheets($organisations);

        $response = $this->json('POST', '/core/v1/organisations/import', ['spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xls')))]);
        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJson([
            'data' => [
                'imported_row_count' => 5000,
            ],
        ]);

        $organisations = Organisation::factory()->count(5000)->make();

        $this->createOrganisationSpreadsheets($organisations);

        $response = $this->json('POST', '/core/v1/organisations/import', ['spreadsheet' => 'data:application/vnd.openxmlformats-officedocument.spreadsheetml.sheet;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xls')))]);
        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJson([
            'data' => [
                'imported_row_count' => 5000,
            ],
        ]);
    }

    /**
     * @test
     */
    public function duplicate_import_organisation_ids_are_detected(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $user->makeSuperAdmin();
        Passport::actingAs($user);

        $organisation = Organisation::factory()->create();

        $uuid = uuid();

        $organisations = collect([
            $organisation,
            Organisation::factory()->make([
                'id' => $uuid,
            ]),
            Organisation::factory()->make([
                'id' => $uuid,
            ]),
        ]);

        $this->createOrganisationSpreadsheets($organisations);

        $response = $this->json('POST', '/core/v1/organisations/import', [
            'spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xls'))),
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $headers = [
            'id',
            'name',
            'description',
            'url',
            'email',
            'phone',
        ];

        $response->assertJsonFragment([
            'row' => collect($organisation->getAttributes())->only($headers)->put('index', 2)->all(),
        ]);

        $response->assertJson([
            'data' => [
                'errors' => [
                    'spreadsheet' => [
                        [
                            'row' => [],
                            'errors' => [
                                'id' => [
                                    'The id has already been taken.',
                                ],
                            ],
                        ],
                        [
                            'row' => [],
                            'errors' => [
                                'id' => [
                                    'The ID is used elsewhere in the spreadsheet.',
                                ],
                            ],
                        ],
                    ],
                ],
            ],
        ]);
        $response->assertJsonFragment(collect($organisation->getAttributes())->only($headers)->all());
    }

    /**
     * @test
     */
    public function duplicate_import_organisation_names_are_detected(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $user->makeSuperAdmin();
        Passport::actingAs($user);

        $organisation1 = Organisation::factory()->create(['name' => 'Current Organisation']);
        $organisation2 = Organisation::factory()->create(['name' => 'Current  Organisation']);
        $organisation3 = Organisation::factory()->create(['name' => 'Current "Organisation"']);
        $organisation4 = Organisation::factory()->create(['name' => 'Current.Organisation']);
        $organisation5 = Organisation::factory()->create(['name' => 'Current, Organisation']);
        $organisation6 = Organisation::factory()->create(['name' => 'Current-Organisation']);

        $organisations = collect([
            Organisation::factory()->make(['name' => 'Current Organisation']),
            Organisation::factory()->make(['name' => 'New Organisation']),
            Organisation::factory()->make(['name' => 'New Organisation']),
        ]);

        $this->createOrganisationSpreadsheets($organisations);

        $response = $this->json('POST', '/core/v1/organisations/import', [
            'spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xls'))),
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $headers = [
            'id',
            'name',
            'description',
            'url',
            'email',
            'phone',
        ];

        $response->assertJsonFragment([
            'originals' => [
                collect($organisation1->getAttributes())->only($headers)->all(),
                collect($organisation2->getAttributes())->only($headers)->all(),
                collect($organisation3->getAttributes())->only($headers)->all(),
                collect($organisation4->getAttributes())->only($headers)->all(),
                collect($organisation5->getAttributes())->only($headers)->all(),
                collect($organisation6->getAttributes())->only($headers)->all(),
            ],
        ]);
        $this->assertEquals($organisations->get(0)->email, $response->json('data.duplicates')[0]['row']['email']);

        $response->assertJsonFragment([
            'email' => $organisations->get(2)->email,
        ]);
        $response->assertJsonFragment([
            'email' => $organisations->get(1)->email,
        ]);
        $response->assertJsonStructure([
            'data' => [
                'duplicates' => [
                    [
                        'row' => [
                            'index',
                            'id',
                            'name',
                            'description',
                            'url',
                            'email',
                            'phone',
                        ],
                        'originals' => [
                            [
                                'id',
                                'name',
                                'description',
                                'url',
                                'email',
                                'phone',
                            ],
                        ],
                    ],
                ],
                'imported_row_count',
            ],
        ]);
    }

    /**
     * @test
     */
    public function possible_duplicate_import_organisations_can_be_ignored(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $user->makeSuperAdmin();
        Passport::actingAs($user);

        $organisation1 = Organisation::factory()->create(['name' => 'Current Organisation']);
        $organisation2 = Organisation::factory()->create(['name' => 'Current  Organisation']);
        $organisation3 = Organisation::factory()->create(['name' => 'Current "Organisation"']);
        $organisation4 = Organisation::factory()->create(['name' => 'Current.Organisation']);
        $organisation5 = Organisation::factory()->create(['name' => 'Current, Organisation']);
        $organisation6 = Organisation::factory()->create(['name' => 'Current-Organisation']);
        $organisations = collect([
            Organisation::factory()->make(['name' => 'Current Organisation']),
            Organisation::factory()->make(['name' => 'New Organisation']),
        ]);

        $this->createOrganisationSpreadsheets($organisations);

        $response = $this->json('POST', '/core/v1/organisations/import', [
            'spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xls'))),
            'ignore_duplicates' => [
                $organisation1->id,
                $organisation2->id,
                $organisation3->id,
                $organisation4->id,
                $organisation5->id,
                $organisation6->id,
            ],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);

        $this->assertDatabaseHas('organisations', [
            'email' => $organisation1->email,
        ]);
        $this->assertDatabaseHas('organisations', [
            'email' => $organisation2->email,
        ]);
        $this->assertDatabaseHas('organisations', [
            'email' => $organisation3->email,
        ]);
        $this->assertDatabaseHas('organisations', [
            'email' => $organisation4->email,
        ]);
        $this->assertDatabaseHas('organisations', [
            'email' => $organisation5->email,
        ]);
        $this->assertDatabaseHas('organisations', [
            'email' => $organisation6->email,
        ]);
    }

    /**
     * @test
     */
    public function duplicate_rows_in_import_are_detected(): void
    {
        Storage::fake('local');

        $user = User::factory()->create();
        $user->makeSuperAdmin();
        Passport::actingAs($user);

        $organisation = Organisation::factory()->create([
            'name' => 'Current Organisation',
            'description' => 'Original Organisation',
        ]);

        $organisations = collect([
            Organisation::factory()->make([
                'name' => 'Current Organisation',
                'description' => 'Import Organisation 1',
            ]),
            Organisation::factory()->make([
                'name' => 'Current Organisation',
                'description' => 'Import Organisation 2',
            ]),
        ]);

        $this->createOrganisationSpreadsheets($organisations);

        $response = $this->json('POST', '/core/v1/organisations/import', [
            'spreadsheet' => 'data:application/vnd.ms-excel;base64,' . base64_encode(file_get_contents(Storage::disk('local')->path('test.xls'))),
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);

        $headers = [
            'id',
            'name',
            'description',
            'url',
            'email',
            'phone',
        ];

        $response->assertJson([
            'data' => [
                'imported_row_count' => 0,
            ],
        ]);

        $this->assertEquals($organisations->get(0)->email, $response->json('data.duplicates')[0]['row']['email']);
        $response->assertJsonCount(2, 'data.duplicates.*.originals.*');
        $response->assertJsonFragment(collect($organisations->get(1)->getAttributes())->only($headers)->put('id', null)->all());
        $response->assertJsonFragment(collect($organisation->getAttributes())->only($headers)->all());
    }
}
