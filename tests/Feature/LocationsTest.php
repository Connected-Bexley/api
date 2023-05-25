<?php

namespace Tests\Feature;

use App\Events\EndpointHit;
use App\Models\Audit;
use App\Models\File;
use App\Models\Location;
use App\Models\Organisation;
use App\Models\Service;
use App\Models\UpdateRequest;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\TestCase;

class LocationsTest extends TestCase
{
    /*
     * List all the locations.
     */

    public function test_guest_can_list_them()
    {
        $location = Location::factory()->create();

        $response = $this->json('GET', '/core/v1/locations');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCollection([
            'id',
            'has_image',
            'address_line_1',
            'address_line_2',
            'address_line_3',
            'city',
            'county',
            'postcode',
            'country',
            'lat',
            'lon',
            'accessibility_info',
            'has_wheelchair_access',
            'has_induction_loop',
            'has_accessible_toilet',
            'created_at',
            'updated_at',
        ]);
        $response->assertJsonFragment([
            'id' => $location->id,
            'has_image' => $location->hasImage(),
            'address_line_1' => $location->address_line_1,
            'address_line_2' => $location->address_line_2,
            'address_line_3' => $location->address_line_3,
            'city' => $location->city,
            'county' => $location->county,
            'postcode' => $location->postcode,
            'country' => $location->country,
            'lat' => $location->lat,
            'lon' => $location->lon,
            'accessibility_info' => $location->accessibility_info,
            'has_wheelchair_access' => $location->has_wheelchair_access,
            'has_induction_loop' => $location->has_induction_loop,
            'has_accessible_toilet' => $location->has_accessible_toilet,
            'created_at' => $location->created_at->format(CarbonImmutable::ISO8601),
            'updated_at' => $location->updated_at->format(CarbonImmutable::ISO8601),
        ]);
    }

    public function test_audit_created_when_listed()
    {
        $this->fakeEvents();

        $this->json('GET', '/core/v1/locations');

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) {
            return $event->getAction() === Audit::ACTION_READ;
        });
    }

    /*
     * Create a location.
     */

    public function test_guest_cannot_create_one()
    {
        $response = $this->json('POST', '/core/v1/locations');

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

        $response = $this->json('POST', '/core/v1/locations');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_can_create_one()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceAdmin($service);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/locations', [
            'address_line_1' => '30-34 Aire St',
            'address_line_2' => null,
            'address_line_3' => null,
            'city' => 'Leeds',
            'county' => 'West Yorkshire',
            'postcode' => 'LS1 4HT',
            'country' => 'England',
            'accessibility_info' => null,
            'has_wheelchair_access' => false,
            'has_induction_loop' => false,
            'has_accessible_toilet' => false,
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonFragment([
            'has_image' => false,
            'address_line_1' => '30-34 Aire St',
            'address_line_2' => null,
            'address_line_3' => null,
            'city' => 'Leeds',
            'county' => 'West Yorkshire',
            'postcode' => 'LS1 4HT',
            'country' => 'England',
            'accessibility_info' => null,
            'has_wheelchair_access' => false,
            'has_induction_loop' => false,
            'has_accessible_toilet' => false,
        ]);
    }

    public function test_invalid_address_error_returned_when_creating_one()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceAdmin($service);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/locations', [
            'address_line_1' => 'Test',
            'address_line_2' => null,
            'address_line_3' => null,
            'city' => 'Test',
            'county' => 'Test',
            'postcode' => 'xx12 3xx',
            'country' => 'United Kingdom',
            'accessibility_info' => null,
            'has_wheelchair_access' => false,
            'has_induction_loop' => false,
            'has_accessible_toilet' => false,
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
        $response->assertJsonFragment([
            'errors' => [
                'address_not_found' => ['Address not found: xx12 3xx, united kingdom'],
            ],
        ]);
    }

    public function test_audit_created_when_created()
    {
        $this->fakeEvents();

        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceAdmin($service);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/locations', [
            'address_line_1' => '30-34 Aire St',
            'address_line_2' => null,
            'address_line_3' => null,
            'city' => 'Leeds',
            'county' => 'West Yorkshire',
            'postcode' => 'LS1 4HT',
            'country' => 'England',
            'accessibility_info' => null,
            'has_wheelchair_access' => false,
            'has_induction_loop' => false,
            'has_accessible_toilet' => false,
        ]);

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $response) {
            return ($event->getAction() === Audit::ACTION_CREATE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $this->getResponseContent($response)['data']['id']);
        });
    }

    /*
     * Get a specific location.
     */

    public function test_guest_can_view_one()
    {
        $location = Location::factory()->create();

        $response = $this->json('GET', "/core/v1/locations/{$location->id}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $location->id,
            'has_image' => $location->hasImage(),
            'address_line_1' => $location->address_line_1,
            'address_line_2' => $location->address_line_2,
            'address_line_3' => $location->address_line_3,
            'city' => $location->city,
            'county' => $location->county,
            'postcode' => $location->postcode,
            'country' => $location->country,
            'lat' => $location->lat,
            'lon' => $location->lon,
            'accessibility_info' => $location->accessibility_info,
            'has_wheelchair_access' => $location->has_wheelchair_access,
            'has_induction_loop' => $location->has_induction_loop,
            'has_accessible_toilet' => $location->has_accessible_toilet,
            'created_at' => $location->created_at->format(CarbonImmutable::ISO8601),
            'updated_at' => $location->updated_at->format(CarbonImmutable::ISO8601),
        ]);
    }

    public function test_audit_created_when_viewed()
    {
        $this->fakeEvents();

        $location = Location::factory()->create();

        $this->json('GET', "/core/v1/locations/{$location->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($location) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getModel()->id === $location->id);
        });
    }

    /*
     * Update a specific location.
     */

    public function test_guest_cannot_update_one()
    {
        $location = Location::factory()->create();

        $response = $this->json('PUT', "/core/v1/locations/{$location->id}");

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
        $location = Location::factory()->create();

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/locations/{$location->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_can_request_to_update_one()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceAdmin($service);
        $location = Location::factory()->create();

        Passport::actingAs($user);

        $payload = [
            'address_line_1' => '30-34 Aire St',
            'address_line_2' => null,
            'address_line_3' => null,
            'city' => 'Leeds',
            'county' => 'West Yorkshire',
            'postcode' => 'LS1 4HT',
            'country' => 'England',
            'accessibility_info' => null,
            'has_wheelchair_access' => false,
            'has_induction_loop' => false,
            'has_accessible_toilet' => false,
        ];
        $response = $this->json('PUT', "/core/v1/locations/{$location->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['data' => $payload]);
        $this->assertDatabaseHas((new UpdateRequest())->getTable(), [
            'user_id' => $user->id,
            'updateable_type' => UpdateRequest::EXISTING_TYPE_LOCATION,
            'updateable_id' => $location->id,
        ]);
        $data = UpdateRequest::query()
            ->where('updateable_type', UpdateRequest::EXISTING_TYPE_LOCATION)
            ->where('updateable_id', $location->id)
            ->firstOrFail()
            ->data;
        $this->assertEquals($data, $payload);
    }

    public function test_audit_created_when_updated()
    {
        $this->fakeEvents();

        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceAdmin($service);
        $location = Location::factory()->create();

        Passport::actingAs($user);

        $this->json('PUT', "/core/v1/locations/{$location->id}", [
            'address_line_1' => '30-34 Aire St',
            'address_line_2' => null,
            'address_line_3' => null,
            'city' => 'Leeds',
            'county' => 'West Yorkshire',
            'postcode' => 'LS1 4HT',
            'country' => 'England',
            'accessibility_info' => null,
            'has_wheelchair_access' => false,
            'has_induction_loop' => false,
            'has_accessible_toilet' => false,
        ]);

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $location) {
            return ($event->getAction() === Audit::ACTION_UPDATE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $location->id);
        });
    }

    public function test_only_partial_fields_can_be_updated()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceAdmin($service);
        $location = Location::factory()->create();

        Passport::actingAs($user);

        $payload = [
            'address_line_1' => '30-34 Aire St',
        ];
        $response = $this->json('PUT', "/core/v1/locations/{$location->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['data' => $payload]);
        $this->assertDatabaseHas((new UpdateRequest())->getTable(), [
            'user_id' => $user->id,
            'updateable_type' => UpdateRequest::EXISTING_TYPE_LOCATION,
            'updateable_id' => $location->id,
        ]);
        $data = UpdateRequest::query()
            ->where('updateable_type', UpdateRequest::EXISTING_TYPE_LOCATION)
            ->where('updateable_id', $location->id)
            ->firstOrFail()
            ->data;
        $this->assertEquals($data, $payload);
    }

    public function test_fields_removed_for_existing_update_requests()
    {
        /**
         * @var \App\Models\Service $service
         * @var \App\Models\User $user
         */
        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeServiceAdmin($service);
        $location = Location::factory()->create();

        Passport::actingAs($user);

        $responseOne = $this->json('PUT', "/core/v1/locations/{$location->id}", [
            'address_line_1' => '1 Old Street',
        ]);
        $responseOne->assertStatus(Response::HTTP_OK);

        $responseTwo = $this->json('PUT', "/core/v1/locations/{$location->id}", [
            'address_line_1' => '2 New Street',
            'address_line_2' => 'Floor 3',
        ]);
        $responseTwo->assertStatus(Response::HTTP_OK);

        $updateRequestOne = UpdateRequest::withTrashed()->findOrFail($this->getResponseContent($responseOne)['id']);
        $updateRequestTwo = UpdateRequest::findOrFail($this->getResponseContent($responseTwo)['id']);

        $this->assertArrayNotHasKey('address_line_1', $updateRequestOne->data);
        $this->assertArrayHasKey('address_line_1', $updateRequestTwo->data);
        $this->assertArrayHasKey('address_line_2', $updateRequestTwo->data);
        $this->assertSoftDeleted($updateRequestOne->getTable(), ['id' => $updateRequestOne->id]);
    }

    /*
     * Delete a specific location.
     */

    public function test_guest_cannot_delete_one()
    {
        $location = Location::factory()->create();

        $response = $this->json('DELETE', "/core/v1/locations/{$location->id}");

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
        $location = Location::factory()->create();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/locations/{$location->id}");

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
        $location = Location::factory()->create();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/locations/{$location->id}");

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
        $location = Location::factory()->create();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/locations/{$location->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_global_admin_can_delete_one()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeGlobalAdmin();
        $location = Location::factory()->create();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/locations/{$location->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing((new Location())->getTable(), ['id' => $location->id]);
    }

    public function test_audit_created_when_deleted()
    {
        $this->fakeEvents();

        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeGlobalAdmin();
        $location = Location::factory()->create();

        Passport::actingAs($user);

        $this->json('DELETE', "/core/v1/locations/{$location->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $location) {
            return ($event->getAction() === Audit::ACTION_DELETE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $location->id);
        });
    }

    /*
     * Get a specific location's image.
     */

    public function test_guest_can_view_image()
    {
        $location = Location::factory()->create();

        $response = $this->get("/core/v1/locations/{$location->id}/image.png");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'image/png');
    }

    public function test_audit_created_when_image_viewed()
    {
        $this->fakeEvents();

        $location = Location::factory()->create();

        $this->get("/core/v1/locations/{$location->id}/image.png");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($location) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getModel()->id === $location->id);
        });
    }

    /*
     * Upload a specific location's image.
     */

    public function test_organisation_admin_can_upload_image()
    {
        /** @var \App\Models\User $user */
        $user = User::factory()->create()->makeGlobalAdmin();
        $image = Storage::disk('local')->get('/test-data/image.png');

        Passport::actingAs($user);

        $imageResponse = $this->json('POST', '/core/v1/files', [
            'is_private' => false,
            'mime_type' => 'image/png',
            'file' => 'data:image/png;base64,'.base64_encode($image),
        ]);

        $response = $this->json('POST', '/core/v1/locations', [
            'address_line_1' => '30-34 Aire St',
            'address_line_2' => null,
            'address_line_3' => null,
            'city' => 'Leeds',
            'county' => 'West Yorkshire',
            'postcode' => 'LS1 4HT',
            'country' => 'England',
            'accessibility_info' => null,
            'has_wheelchair_access' => false,
            'has_induction_loop' => false,
            'has_accessible_toilet' => false,
            'image_file_id' => $this->getResponseContent($imageResponse, 'data.id'),
        ]);
        $locationId = $this->getResponseContent($response, 'data.id');

        $response->assertStatus(Response::HTTP_CREATED);
        $this->assertDatabaseHas(table(Location::class), [
            'id' => $locationId,
        ]);
        $this->assertDatabaseMissing(table(Location::class), [
            'id' => $locationId,
            'image_file_id' => null,
        ]);
    }

    /*
     * Delete a specific location's image.
     */

    public function test_organisation_admin_can_delete_image()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeGlobalAdmin();
        $location = Location::factory()->create([
            'image_file_id' => File::factory()->create()->id,
        ]);
        $payload = [
            'address_line_1' => $location->address_line_1,
            'address_line_2' => $location->address_line_2,
            'address_line_3' => $location->address_line_3,
            'city' => $location->city,
            'county' => $location->county,
            'postcode' => $location->postcode,
            'country' => $location->country,
            'accessibility_info' => $location->accessibility_info,
            'has_wheelchair_access' => $location->has_wheelchair_access,
            'has_induction_loop' => $location->has_induction_loop,
            'has_accessible_toilet' => $location->has_accessible_toilet,
            'image_file_id' => null,
        ];

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/locations/{$location->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas(table(UpdateRequest::class), ['updateable_id' => $location->id]);
        $updateRequest = UpdateRequest::where('updateable_id', $location->id)->firstOrFail();
        $this->assertEquals(null, $updateRequest->data['image_file_id']);
    }

    public function test_global_admin_can_update_one_with_auto_approval()
    {
        $service = Service::factory()->create();
        $user = User::factory()->create();
        $user->makeGlobalAdmin();
        $location = Location::factory()->create();

        Passport::actingAs($user);

        $payload = [
            'address_line_1' => '30-34 Aire St',
            'address_line_2' => null,
            'address_line_3' => null,
            'city' => 'Leeds',
            'county' => 'West Yorkshire',
            'postcode' => 'LS1 4HT',
            'country' => 'England',
            'accessibility_info' => null,
            'has_wheelchair_access' => false,
            'has_induction_loop' => false,
            'has_accessible_toilet' => false,
        ];
        $response = $this->json('PUT', "/core/v1/locations/{$location->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['data' => $payload]);
        $this->assertDatabaseHas((new UpdateRequest())->getTable(), [
            'user_id' => $user->id,
            'updateable_type' => UpdateRequest::EXISTING_TYPE_LOCATION,
            'updateable_id' => $location->id,
        ]);
        $data = UpdateRequest::query()
            ->where('updateable_type', UpdateRequest::EXISTING_TYPE_LOCATION)
            ->where('updateable_id', $location->id)
            ->firstOrFail()
            ->data;
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
}
