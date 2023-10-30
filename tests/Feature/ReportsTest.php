<?php

namespace Tests\Feature;

use App\Events\EndpointHit;
use App\Models\Audit;
use App\Models\File;
use App\Models\Organisation;
use App\Models\Report;
use App\Models\ReportType;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\Event;
use Laravel\Passport\Passport;
use Tests\TestCase;

class ReportsTest extends TestCase
{
    /*
     * List all the reports.
     */

    /**
     * @test
     *
     */
    public function guest_cannot_list_them()
    {
        $response = $this->json('GET', '/core/v1/reports');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @test
     *
     */
    public function service_worker_cannot_list_them()
    {
        $service = Service::factory()->create();
        $user = User::factory()->create()->makeServiceWorker($service);

        Passport::actingAs($user);

        $response = $this->json('GET', '/core/v1/reports');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     *
     */
    public function service_admin_cannot_list_them()
    {
        $service = Service::factory()->create();
        $user = User::factory()->create()->makeServiceAdmin($service);

        Passport::actingAs($user);

        $response = $this->json('GET', '/core/v1/reports');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     *
     */
    public function organisation_admin_cannot_list_them()
    {
        $organisation = Organisation::factory()->create();
        $user = User::factory()->create()->makeOrganisationAdmin($organisation);

        Passport::actingAs($user);

        $response = $this->json('GET', '/core/v1/reports');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     *
     */
    public function global_admin_cannot_list_them()
    {
        $user = User::factory()->create()->makeGlobalAdmin();
        $report = Report::factory()->create();

        Passport::actingAs($user);

        $response = $this->json('GET', '/core/v1/reports');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     *
     */
    public function super_admin_can_list_them()
    {
        $user = User::factory()->create()->makeSuperAdmin();
        $report = Report::factory()->create();

        Passport::actingAs($user);

        $response = $this->json('GET', '/core/v1/reports');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $report->id,
            'report_type' => $report->reportType->name,
        ]);
    }

    /**
     * @test
     *
     */
    public function audit_created_when_listed()
    {
        $this->fakeEvents();

        $user = User::factory()->create()->makeSuperAdmin();

        Passport::actingAs($user);

        $this->json('GET', '/core/v1/reports');

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getUser()->id === $user->id);
        });
    }

    /*
     * Create a report.
     */

    /**
     * @test
     *
     */
    public function guest_cannot_create_one()
    {
        $response = $this->json('POST', '/core/v1/reports');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @test
     *
     */
    public function service_worker_cannot_create_one()
    {
        $service = Service::factory()->create();
        $user = User::factory()->create()->makeServiceWorker($service);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/reports');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     *
     */
    public function service_admin_cannot_create_one()
    {
        $service = Service::factory()->create();
        $user = User::factory()->create()->makeServiceAdmin($service);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/reports');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     *
     */
    public function organisation_admin_cannot_create_one()
    {
        $organisation = Organisation::factory()->create();
        $user = User::factory()->create()->makeOrganisationAdmin($organisation);

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/reports');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     *
     */
    public function global_admin_cannot_create_one()
    {
        $user = User::factory()->create()->makeGlobalAdmin();

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/reports', [
            'report_type' => ReportType::usersExport()->name,
        ]);

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     *
     */
    public function super_admin_can_create_one()
    {
        $user = User::factory()->create()->makeSuperAdmin();

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/reports', [
            'report_type' => ReportType::usersExport()->name,
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonFragment([
            'report_type' => ReportType::usersExport()->name,
        ]);
        $this->assertDatabaseHas((new Report())->getTable(), [
            'report_type_id' => ReportType::usersExport()->id,
        ]);
        $response->assertJsonStructure([
            'data' => [
                'id',
                'report_type',
                'starts_at',
                'ends_at',
                'created_at',
                'updated_at',
            ],
        ]);
    }

    /**
     * @test
     *
     */
    public function audit_created_when_created()
    {
        $this->fakeEvents();

        $user = User::factory()->create()->makeSuperAdmin();

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/reports', [
            'report_type' => ReportType::usersExport()->name,
        ]);

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $response) {
            return ($event->getAction() === Audit::ACTION_CREATE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $this->getResponseContent($response)['data']['id']);
        });
    }

    /**
     * @test
     *
     */
    public function super_admin_can_create_one_with_date_range()
    {
        $user = User::factory()->create()->makeSuperAdmin();

        Passport::actingAs($user);

        $response = $this->json('POST', '/core/v1/reports', [
            'report_type' => ReportType::referralsExport()->name,
            'starts_at' => Date::today()->startOfMonth()->toDateString(),
            'ends_at' => Date::today()->endOfMonth()->toDateString(),
        ]);

        $response->assertStatus(Response::HTTP_CREATED);
        $response->assertJsonFragment([
            'report_type' => ReportType::referralsExport()->name,
            'starts_at' => Date::today()->startOfMonth()->toDateString(),
            'ends_at' => Date::today()->endOfMonth()->toDateString(),
        ]);
        $this->assertDatabaseHas((new Report())->getTable(), [
            'report_type_id' => ReportType::referralsExport()->id,
            'starts_at' => Date::today()->startOfMonth()->toDateString(),
            'ends_at' => Date::today()->endOfMonth()->toDateString(),
        ]);
    }

    /*
     * Get a specific report.
     */

    /**
     * @test
     *
     */
    public function guest_cannot_view_one()
    {
        $report = Report::factory()->create();

        $response = $this->json('GET', "/core/v1/reports/{$report->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @test
     *
     */
    public function service_worker_cannot_view_one()
    {
        $service = Service::factory()->create();
        $user = User::factory()->create()->makeServiceWorker($service);
        $report = Report::factory()->create();

        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/reports/{$report->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     *
     */
    public function service_admin_cannot_view_one()
    {
        $service = Service::factory()->create();
        $user = User::factory()->create()->makeServiceAdmin($service);
        $report = Report::factory()->create();

        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/reports/{$report->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     *
     */
    public function organisation_admin_cannot_view_one()
    {
        $organisation = Organisation::factory()->create();
        $user = User::factory()->create()->makeOrganisationAdmin($organisation);
        $report = Report::factory()->create();

        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/reports/{$report->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     *
     */
    public function global_admin_cannot_view_one()
    {
        $user = User::factory()->create()->makeGlobalAdmin();
        $report = Report::factory()->create();

        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/reports/{$report->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     *
     */
    public function super_admin_can_view_one()
    {
        $user = User::factory()->create()->makeSuperAdmin();
        $report = Report::factory()->create();

        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/reports/{$report->id}");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $report->id,
            'report_type' => $report->reportType->name,
        ]);
    }

    /**
     * @test
     *
     */
    public function audit_created_when_viewed()
    {
        $this->fakeEvents();

        $user = User::factory()->create()->makeSuperAdmin();
        $report = Report::factory()->create();

        Passport::actingAs($user);

        $this->json('GET', "/core/v1/reports/{$report->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $report) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $report->id);
        });
    }

    /*
     * Delete a specific report.
     */

    /**
     * @test
     *
     */
    public function guest_cannot_delete_one()
    {
        $report = Report::factory()->create();

        $response = $this->json('DELETE', "/core/v1/reports/{$report->id}");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @test
     *
     */
    public function service_worker_cannot_delete_one()
    {
        $service = Service::factory()->create();
        $user = User::factory()->create()->makeServiceWorker($service);
        $report = Report::factory()->create();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/reports/{$report->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     *
     */
    public function service_admin_cannot_delete_one()
    {
        $service = Service::factory()->create();
        $user = User::factory()->create()->makeServiceAdmin($service);
        $report = Report::factory()->create();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/reports/{$report->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     *
     */
    public function organisation_admin_cannot_delete_one()
    {
        $organisation = Organisation::factory()->create();
        $user = User::factory()->create()->makeOrganisationAdmin($organisation);
        $report = Report::factory()->create();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/reports/{$report->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     *
     */
    public function global_admin_cannot_delete_one()
    {
        $user = User::factory()->create()->makeGlobalAdmin();
        $report = Report::factory()->create();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/reports/{$report->id}");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     *
     */
    public function super_admin_can_delete_one()
    {
        $user = User::factory()->create()->makeSuperAdmin();
        $report = Report::factory()->create();

        Passport::actingAs($user);

        $response = $this->json('DELETE', "/core/v1/reports/{$report->id}");

        $response->assertStatus(Response::HTTP_OK);
        $this->assertDatabaseMissing((new Report())->getTable(), ['id' => $report->id]);
        $this->assertDatabaseMissing((new File())->getTable(), ['id' => $report->file_id]);
    }

    /**
     * @test
     *
     */
    public function audit_created_when_deleted()
    {
        $this->fakeEvents();

        $user = User::factory()->create()->makeSuperAdmin();
        $report = Report::factory()->create();

        Passport::actingAs($user);

        $this->json('DELETE', "/core/v1/reports/{$report->id}");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $report) {
            return ($event->getAction() === Audit::ACTION_DELETE) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $report->id);
        });
    }

    /*
     * Download a specific report.
     */

    /**
     * @test
     *
     */
    public function guest_cannot_download_file()
    {
        $report = Report::factory()->create();

        $response = $this->json('GET', "/core/v1/reports/{$report->id}/download");

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    /**
     * @test
     *
     */
    public function service_worker_cannot_download_file()
    {
        $service = Service::factory()->create();
        $user = User::factory()->create()->makeServiceWorker($service);
        $report = Report::factory()->create();

        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/reports/{$report->id}/download");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     *
     */
    public function service_admin_cannot_download_file()
    {
        $service = Service::factory()->create();
        $user = User::factory()->create()->makeServiceAdmin($service);
        $report = Report::factory()->create();

        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/reports/{$report->id}/download");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     *
     */
    public function organisation_admin_cannot_download_file()
    {
        $organisation = Organisation::factory()->create();
        $user = User::factory()->create()->makeOrganisationAdmin($organisation);
        $report = Report::factory()->create();

        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/reports/{$report->id}/download");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     *
     */
    public function global_admin_cannot_download_file()
    {
        $user = User::factory()->create()->makeGlobalAdmin();
        $report = Report::generate(ReportType::usersExport());

        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/reports/{$report->id}/download");

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    /**
     * @test
     *
     */
    public function super_admin_can_download_file()
    {
        $user = User::factory()->create()->makeSuperAdmin();
        $report = Report::generate(ReportType::usersExport());

        Passport::actingAs($user);

        $response = $this->json('GET', "/core/v1/reports/{$report->id}/download");

        $response->assertStatus(Response::HTTP_OK);
        $response->assertHeader('Content-Type', 'text/csv; charset=UTF-8');
    }

    /**
     * @test
     *
     */
    public function audit_created_when_file_viewed()
    {
        $this->fakeEvents();

        $user = User::factory()->create()->makeSuperAdmin();
        $report = Report::generate(ReportType::usersExport());

        Passport::actingAs($user);

        $this->json('GET', "/core/v1/reports/{$report->id}/download");

        Event::assertDispatched(EndpointHit::class, function (EndpointHit $event) use ($user, $report) {
            return ($event->getAction() === Audit::ACTION_READ) &&
                ($event->getUser()->id === $user->id) &&
                ($event->getModel()->id === $report->id);
        });
    }
}
