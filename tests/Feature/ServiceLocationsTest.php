<?php

namespace Tests\Feature;

use App\Events\EndpointHit;
use App\Models\Audit;
use App\Models\File;
use App\Models\HolidayOpeningHour;
use App\Models\Location;
use App\Models\RegularOpeningHour;
use App\Models\Service;
use App\Models\ServiceLocation;
use App\Models\UpdateRequest;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ServiceLocationsTest extends TestCase
{
    /*
     * List all the service locations.
     */

    public function test_guest_can_list_them()
    {
        $location = Location::factory()->create();
        $service = Service::factory()->create();
        $serviceLocation = $service->serviceLocations()->create(['location_id' => $location->id]);

        $response = $this->json('GET', '/core/v1/service-locations');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $serviceLocation->id,
            'service_id' => $serviceLocation->service_id,
            'location_id' => $serviceLocation->location_id,
            'has_image' => $serviceLocation->hasImage(),
            'name' => null,
            'is_open_now' => false,
            'regular_opening_hours' => [],
            'holiday_opening_hours' => [],
            'created_at' => $serviceLocation->created_at->format(CarbonImmutable::ISO8601),
        ]);
    }

    public function test_guest_can_list_them_for_service()
    {
        $serviceLocation = ServiceLocation::factory()->create();
        $anotherServiceLocation = ServiceLocation::factory()->create();

        $response = $this->json('GET', "/core/v1/service-locations?filter[service_id]={$serviceLocation->service_id}");
        $serviceLocation = $serviceLocation->fresh();

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $serviceLocation->id]);
        $response->assertJsonMissing(['id' => $anotherServiceLocation->id]);
    }

    public function test_guest_can_list_them_with_opening_hours()
    {
        Date::setTestNow(Date::now()->setTime(18, 0));

        $location = Location::factory()->create();
        $service = Service::factory()->create();
        $serviceLocation = $service->serviceLocations()->create(['location_id' => $location->id]);
        $regularOpeningHour = RegularOpeningHour::factory()->create([
            'service_location_id' => $serviceLocation->id,
            'weekday' => Date::now()->addDay()->day,
        ]);
        $holidayOpeningHour = HolidayOpeningHour::factory()->create([
            'service_location_id' => $serviceLocation->id,
            'starts_at' => Date::now()->addMonth(),
            'ends_at' => Date::now()->addMonths(2),
        ]);

        $response = $this->json('GET', '/core/v1/service-locations');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $serviceLocation->id,
            'service_id' => $serviceLocation->service_id,
            'location_id' => $serviceLocation->location_id,
            'has_image' => false,
            'name' => null,
            'is_open_now' => false,
            'regular_opening_hours' => [
                [
                    'frequency' => $regularOpeningHour->frequency,
                    'weekday' => $regularOpeningHour->weekday,
                    'opens_at' => $regularOpeningHour->opens_at->toString(),
                    'closes_at' => $regularOpeningHour->closes_at->toString(),
                ],
            ],
            'holiday_opening_hours' => [
                [
                    'is_closed' => $holidayOpeningHour->is_closed,
                    'starts_at' => $holidayOpeningHour->starts_at->toDateString(),
                    'ends_at' => $holidayOpeningHour->ends_at->toDateString(),
                    'opens_at' => $holidayOpeningHour->opens_at->toString(),
                    'closes_at' => $holidayOpeningHour->closes_at->toString(),
                ],
            ],
            'created_at' => $serviceLocation->created_at->format(CarbonImmutable::ISO8601),
        ]);
    }

    public function test_audit_created_when_listed()
    {
        $this->fakeEvents();

        $this->json('GET', '/core/v1/service-locations');

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) {
            return $event->getAction() === Audit::ACTION_READ;
        });
    }

    /*
     * Create a service location.
     */

    public function test_guest_cannot_create_one()
    {
        $response = $this->json('POST', '/core/v1/service-locations');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_create_one()
    {
        $service = Service::factory()->create();
        $user = User::factory()->create()->makeServiceWorker($service);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/service-locations');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_for_another_service_cannot_create_one()
    {
        $location = Location::factory()->create();
        $service = Service::factory()->create();
        $anotherService = Service::factory()->create();
        $user = User::factory()->create()->makeServiceAdmin($anotherService);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/service-locations', [
            'service_id' => $service->id,
            'location_id' => $location->id,
            'name' => null,
            'regular_opening_hours' => [],
            'holiday_opening_hours' => [],
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_service_admin_can_create_one()
    {
        $location = Location::factory()->create();
        $service = Service::factory()->create();
        $user = User::factory()->create()->makeServiceAdmin($service);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/service-locations', [
            'service_id' => $service->id,
            'location_id' => $location->id,
            'name' => null,
            'regular_opening_hours' => [],
            'holiday_opening_hours' => [],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonFragment([
            'service_id' => $service->id,
            'location_id' => $location->id,
            'has_image' => false,
            'name' => null,
            'regular_opening_hours' => [],
            'holiday_opening_hours' => [],
        ]);
    }

    public function test_service_admin_can_create_one_with_opening_hours()
    {
        $location = Location::factory()->create();
        $service = Service::factory()->create();
        $user = User::factory()->create()->makeServiceAdmin($service);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/service-locations', [
            'service_id' => $service->id,
            'location_id' => $location->id,
            'name' => null,
            'regular_opening_hours' => [
                [
                    'frequency' => RegularOpeningHour::FREQUENCY_WEEKLY,
                    'weekday' => RegularOpeningHour::WEEKDAY_FRIDAY,
                    'opens_at' => '09:00:00',
                    'closes_at' => '17:30:00',
                ],
            ],
            'holiday_opening_hours' => [
                [
                    'is_closed' => true,
                    'starts_at' => '2018-12-20',
                    'ends_at' => '2019-01-02',
                    'opens_at' => '00:00:00',
                    'closes_at' => '00:00:00',
                ],
            ],
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonFragment([
            'service_id' => $service->id,
            'location_id' => $location->id,
            'has_image' => false,
            'name' => null,
            'regular_opening_hours' => [
                [
                    'frequency' => RegularOpeningHour::FREQUENCY_WEEKLY,
                    'weekday' => RegularOpeningHour::WEEKDAY_FRIDAY,
                    'opens_at' => '09:00:00',
                    'closes_at' => '17:30:00',
                ],
            ],
            'holiday_opening_hours' => [
                [
                    'is_closed' => true,
                    'starts_at' => '2018-12-20',
                    'ends_at' => '2019-01-02',
                    'opens_at' => '00:00:00',
                    'closes_at' => '00:00:00',
                ],
            ],
        ]);
    }

    public function test_service_admin_can_create_one_with_an_image()
    {
        $location = Location::factory()->create();
        $service = Service::factory()->create();
        $user = User::factory()->create()->makeServiceAdmin($service);
        $image = Storage::disk('local')->get('/test-data/image.png');

        Passport::actingAs($user);

        $imageResponse = $this->json('POST', '/core/v1/files', [
            'is_private' => false,
            'mime_type' => 'image/png',
            'file' => 'data:image/png;base64,' . base64_encode($image),
        ]);

        $payload = [
            'service_id' => $service->id,
            'location_id' => $location->id,
            'name' => null,
            'regular_opening_hours' => [],
            'holiday_opening_hours' => [],
            'image_file_id' => $this->getResponseContent($imageResponse, 'data.id'),
        ];

        $response = $this->json('POST', '/core/v1/service-locations', $payload);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonFragment([
            'service_id' => $service->id,
            'location_id' => $location->id,
            'has_image' => true,
            'name' => null,
            'regular_opening_hours' => [],
            'holiday_opening_hours' => [],
        ]);

        $serviceLocationId = $this->getResponseContent($response, 'data.id');

        // Get the event image for the service location
        $response = $this->get("/core/v1/service-locations/$serviceLocationId/image.png");

        $this->assertEquals($image, $response->content());
    }

    public function test_audit_created_when_created()
    {
        $this->fakeEvents();

        $location = Location::factory()->create();
        $service = Service::factory()->create();
        $user = User::factory()->create()->makeServiceAdmin($service);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/service-locations', [
            'service_id' => $service->id,
            'location_id' => $location->id,
            'name' => null,
            'regular_opening_hours' => [],
            'holiday_opening_hours' => [],
        ]);

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $response) {
            return ($event->getAction() === Audit::ACTION_CREATE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $this->getResponseContent($response)['data']['id']);
        });
    }

    /*
     * Get a specific service location.
     */

    public function test_guest_can_view_one()
    {
        $location = Location::factory()->create();
        $service = Service::factory()->create();
        $serviceLocation = $service->serviceLocations()->create(['location_id' => $location->id]);

        $response = $this->json('GET', "/core/v1/service-locations/{$serviceLocation->id}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $serviceLocation->id,
            'service_id' => $serviceLocation->service_id,
            'location_id' => $serviceLocation->location_id,
            'has_image' => false,
            'name' => null,
            'is_open_now' => false,
            'regular_opening_hours' => [],
            'holiday_opening_hours' => [],
            'created_at' => $serviceLocation->created_at->format(CarbonImmutable::ISO8601),
        ]);
    }

    public function test_guest_can_view_one_with_opening_hours()
    {
        Date::setTestNow(Date::now()->setTime(18, 0));

        $location = Location::factory()->create();
        $service = Service::factory()->create();
        $serviceLocation = $service->serviceLocations()->create(['location_id' => $location->id]);
        $regularOpeningHour = RegularOpeningHour::factory()->create([
            'service_location_id' => $serviceLocation->id,
            'weekday' => Date::now()->addDay()->day,
        ]);
        $holidayOpeningHour = HolidayOpeningHour::factory()->create([
            'service_location_id' => $serviceLocation->id,
            'starts_at' => Date::now()->addMonth(),
            'ends_at' => Date::now()->addMonths(2),
        ]);

        $response = $this->json('GET', "/core/v1/service-locations/{$serviceLocation->id}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $serviceLocation->id,
            'service_id' => $serviceLocation->service_id,
            'location_id' => $serviceLocation->location_id,
            'has_image' => false,
            'name' => null,
            'is_open_now' => false,
            'regular_opening_hours' => [
                [
                    'frequency' => $regularOpeningHour->frequency,
                    'weekday' => $regularOpeningHour->weekday,
                    'opens_at' => $regularOpeningHour->opens_at->toString(),
                    'closes_at' => $regularOpeningHour->closes_at->toString(),
                ],
            ],
            'holiday_opening_hours' => [
                [
                    'is_closed' => $holidayOpeningHour->is_closed,
                    'starts_at' => $holidayOpeningHour->starts_at->toDateString(),
                    'ends_at' => $holidayOpeningHour->ends_at->toDateString(),
                    'opens_at' => $holidayOpeningHour->opens_at->toString(),
                    'closes_at' => $holidayOpeningHour->closes_at->toString(),
                ],
            ],
            'created_at' => $serviceLocation->created_at->format(CarbonImmutable::ISO8601),
        ]);
    }

    public function test_audit_created_when_viewed()
    {
        $this->fakeEvents();

        $location = Location::factory()->create();
        $service = Service::factory()->create();
        $serviceLocation = $service->serviceLocations()->create(['location_id' => $location->id]);

        $this->json('GET', "/core/v1/service-locations/{$serviceLocation->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($serviceLocation) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getModel()->id === $serviceLocation->id);
        });
    }

    /*
     * Update a specific service location.
     */

    public function test_guest_cannot_update_one()
    {
        $serviceLocation = ServiceLocation::factory()->create();

        $response = $this->json('PUT', "/core/v1/service-locations/{$serviceLocation->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_update_one()
    {
        $serviceLocation = ServiceLocation::factory()->create();
        $user = User::factory()->create()->makeServiceWorker($serviceLocation->service);

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/service-locations/{$serviceLocation->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_can_update_one()
    {
        $serviceLocation = ServiceLocation::factory()->create();
        $user = User::factory()->create()->makeServiceAdmin($serviceLocation->service);

        Passport::actingAs($user);

        $payload = [
            'name' => 'New Company Name',
            'regular_opening_hours' => [
                [
                    'frequency' => RegularOpeningHour::FREQUENCY_MONTHLY,
                    'day_of_month' => 10,
                    'opens_at' => '10:00:00',
                    'closes_at' => '14:00:00',
                ],
            ],
            'holiday_opening_hours' => [
                [
                    'is_closed' => true,
                    'starts_at' => '2018-01-01',
                    'ends_at' => '2018-01-01',
                    'opens_at' => '00:00:00',
                    'closes_at' => '00:00:00',
                ],
            ],
        ];
        $response = $this->json('PUT', "/core/v1/service-locations/{$serviceLocation->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['data' => $payload]);
        $data = $serviceLocation->updateRequests()->firstOrFail()->data;
        $this->assertEquals($data, $payload);
    }

    public function test_audit_created_when_updated()
    {
        $this->fakeEvents();

        $serviceLocation = ServiceLocation::factory()->create();
        $user = User::factory()->create()->makeServiceAdmin($serviceLocation->service);

        Passport::actingAs($user);

        $this->json('PUT', "/core/v1/service-locations/{$serviceLocation->id}", [
            'name' => 'New Company Name',
            'regular_opening_hours' => [],
            'holiday_opening_hours' => [],
        ]);

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $serviceLocation) {
            return ($event->getAction() === Audit::ACTION_UPDATE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $serviceLocation->id);
        });
    }

    public function test_only_partial_fields_can_be_updated()
    {
        $serviceLocation = ServiceLocation::factory()->create();
        $user = User::factory()->create()->makeServiceAdmin($serviceLocation->service);

        Passport::actingAs($user);

        $payload = [
            'name' => 'New Company Name',
        ];
        $response = $this->json('PUT', "/core/v1/service-locations/{$serviceLocation->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['data' => $payload]);
        $data = $serviceLocation->updateRequests()->firstOrFail()->data;
        $this->assertEquals($data, $payload);
    }

    public function test_fields_removed_for_existing_update_requests()
    {
        $serviceLocation = ServiceLocation::factory()->create();
        $user = User::factory()->create()->makeServiceAdmin($serviceLocation->service);

        Passport::actingAs($user);

        $responseOne = $this->json('PUT', "/core/v1/service-locations/{$serviceLocation->id}", [
            'name' => 'New Company Name',
        ]);
        $responseOne->assertStatus(Response::HTTP_OK);

        $responseTwo = $this->json('PUT', "/core/v1/service-locations/{$serviceLocation->id}", [
            'name' => 'New Company Name',
        ]);
        $responseTwo->assertStatus(Response::HTTP_OK);

        $updateRequestOne = UpdateRequest::withTrashed()->findOrFail($this->getResponseContent($responseOne)['id']);
        $updateRequestTwo = UpdateRequest::findOrFail($this->getResponseContent($responseTwo)['id']);

        $this->assertArrayNotHasKey('name', $updateRequestOne->data);
        $this->assertArrayHasKey('name', $updateRequestTwo->data);
        $this->assertSoftDeleted($updateRequestOne->getTable(), ['id' => $updateRequestOne->id]);
    }

    /*
     * Delete a specific service location.
     */

    public function test_guest_cannot_delete_one()
    {
        $serviceLocation = ServiceLocation::factory()->create();

        $response = $this->json('DELETE', "/core/v1/service-locations/{$serviceLocation->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_delete_one()
    {
        $serviceLocation = ServiceLocation::factory()->create();
        $user = User::factory()->create()->makeServiceWorker($serviceLocation->service);

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/service-locations/{$serviceLocation->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_cannot_delete_one()
    {
        $serviceLocation = ServiceLocation::factory()->create();
        $user = User::factory()->create()->makeServiceAdmin($serviceLocation->service);

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/service-locations/{$serviceLocation->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_cannot_delete_one()
    {
        $serviceLocation = ServiceLocation::factory()->create();
        $user = User::factory()->create()->makeOrganisationAdmin($serviceLocation->service->organisation);

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/service-locations/{$serviceLocation->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_global_admin_cannot_delete_one()
    {
        $serviceLocation = ServiceLocation::factory()->create();
        $user = User::factory()->create()->makeGlobalAdmin();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/service-locations/{$serviceLocation->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_super_admin_can_delete_one()
    {
        $serviceLocation = ServiceLocation::factory()->create();
        $user = User::factory()->create()->makeSuperAdmin();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/service-locations/{$serviceLocation->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing((new ServiceLocation())->getTable(), ['id' => $serviceLocation->id]);
    }

    public function test_audit_created_when_deleted()
    {
        $this->fakeEvents();

        $serviceLocation = ServiceLocation::factory()->create();
        $user = User::factory()->create()->makeSuperAdmin();

        Passport::actingAs($user);

        $this->json('DELETE', "/core/v1/service-locations/{$serviceLocation->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $serviceLocation) {
            return ($event->getAction() === Audit::ACTION_DELETE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $serviceLocation->id);
        });
    }

    /*
     * Get a specific service location's image.
     */

    public function test_guest_can_view_image()
    {
        $serviceLocation = ServiceLocation::factory()->create();

        $response = $this->get("/core/v1/service-locations/{$serviceLocation->id}/image.png");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'image/png');
    }

    public function test_audit_created_when_image_viewed()
    {
        $this->fakeEvents();

        $serviceLocation = ServiceLocation::factory()->create();

        $this->get("/core/v1/service-locations/{$serviceLocation->id}/image.png");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($serviceLocation) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getModel()->id === $serviceLocation->id);
        });
    }

    /*
     * Upload a specific service location's image.
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
            'file' => 'data:image/png;base64,' . base64_encode($image),
        ]);

        $response = $this->json('POST', '/core/v1/service-locations', [
            'service_id' => Service::factory()->create()->id,
            'location_id' => Location::factory()->create()->id,
            'name' => null,
            'regular_opening_hours' => [],
            'holiday_opening_hours' => [],
            'image_file_id' => $this->getResponseContent($imageResponse, 'data.id'),
        ]);
        $locationId = $this->getResponseContent($response, 'data.id');

        $response->assertStatus(Response::HTTP_CREATED);
        $this->assertDatabaseHas(table(ServiceLocation::class), [
            'id' => $locationId,
        ]);
        $this->assertDatabaseMissing(table(ServiceLocation::class), [
            'id' => $locationId,
            'image_file_id' => null,
        ]);
    }

    /*
     * Delete a specific service location's image.
     */

    public function test_organisation_admin_can_delete_image()
    {
        /**
         * @var \App\Models\User $user
         */
        $user = User::factory()->create();
        $user->makeGlobalAdmin();
        $serviceLocation = ServiceLocation::factory()->create([
            'image_file_id' => File::factory()->create()->id,
        ]);
        $payload = [
            'name' => null,
            'regular_opening_hours' => [],
            'holiday_opening_hours' => [],
            'image_file_id' => null,
        ];

        Passport::actingAs($user);

        $response = $this->json('PUT', "/core/v1/service-locations/{$serviceLocation->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseHas(table(UpdateRequest::class), ['updateable_id' => $serviceLocation->id]);
        $updateRequest = UpdateRequest::where('updateable_id', $serviceLocation->id)->firstOrFail();
        $this->assertEquals(null, $updateRequest->data['image_file_id']);
    }

    public function test_global_admin_can_update_one()
    {
        $serviceLocation = ServiceLocation::factory()->create();
        $user = User::factory()->create()->makeGlobalAdmin();

        Passport::actingAs($user);

        $payload = [
            'name' => 'New Company Name',
            'regular_opening_hours' => [
                [
                    'frequency' => RegularOpeningHour::FREQUENCY_MONTHLY,
                    'day_of_month' => 10,
                    'opens_at' => '10:00:00',
                    'closes_at' => '14:00:00',
                ],
            ],
            'holiday_opening_hours' => [
                [
                    'is_closed' => true,
                    'starts_at' => '2018-01-01',
                    'ends_at' => '2018-01-01',
                    'opens_at' => '00:00:00',
                    'closes_at' => '00:00:00',
                ],
            ],
        ];
        $response = $this->json('PUT', "/core/v1/service-locations/{$serviceLocation->id}", $payload);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['data' => $payload]);
        $this->assertDatabaseHas((new UpdateRequest())->getTable(), [
            'user_id' => $user->id,
            'updateable_type' => UpdateRequest::EXISTING_TYPE_SERVICE_LOCATION,
            'updateable_id' => $serviceLocation->id,
            'approved_at' => null,
        ]);
        $updateRequest = UpdateRequest::query()
            ->where('updateable_type', UpdateRequest::EXISTING_TYPE_SERVICE_LOCATION)
            ->where('updateable_id', $serviceLocation->id)
            ->firstOrFail();
        $this->assertEquals($updateRequest->data, $payload);

        $this->approveUpdateRequest($updateRequest->id);

        $this->assertDatabaseHas(table(ServiceLocation::class), ['name' => 'New Company Name']);

        $this->assertDatabaseHas(table(RegularOpeningHour::class), [
            'service_location_id' => $serviceLocation->id,
            'frequency' => RegularOpeningHour::FREQUENCY_MONTHLY,
            'day_of_month' => 10,
            'opens_at' => '10:00:00',
            'closes_at' => '14:00:00',
        ]);
    }
}
