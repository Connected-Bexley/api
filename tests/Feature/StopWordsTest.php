<?php

namespace Tests\Feature;

use App\Models\Organisation;
use App\Models\Service;
use App\Models\User;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Laravel\Passport\Passport;
use Tests\TestCase;

class StopWordsTest extends TestCase
{
    /**
     * Clean up the testing environment before the next test.
     *
     * @return void
     *
     * @throws \Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function tearDown(): void
    {
        // Reindex to prevent stop words persisting.
        $stopWords = Storage::disk('local')->get('elasticsearch/stop-words.csv');
        Storage::cloud()->put('elasticsearch/stop-words.csv', $stopWords);
        $this->artisan('ck:reindex-elasticsearch');

        parent::tearDown();
    }

    /*
     * View the stop words.
     */

    public function test_guest_cannot_view_stop_words()
    {
        $response = $this->json('GET', '/core/v1/stop-words');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_view_stop_words()
    {
        Passport::actingAs(
            User::factory()->create()->makeServiceWorker(
                Service::factory()->create()
            )
        );

        $response = $this->json('GET', '/core/v1/stop-words');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_cannot_view_stop_words()
    {
        Passport::actingAs(
            User::factory()->create()->makeServiceAdmin(
                Service::factory()->create()
            )
        );

        $response = $this->json('GET', '/core/v1/stop-words');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_cannot_view_stop_words()
    {
        Passport::actingAs(
            User::factory()->create()->makeOrganisationAdmin(
                Organisation::factory()->create()
            )
        );

        $response = $this->json('GET', '/core/v1/stop-words');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_global_admin_can_view_stop_words()
    {
        Passport::actingAs(
            User::factory()->create()->makeGlobalAdmin()
        );
        $csv = csv_to_array(
            Storage::disk('local')->get('elasticsearch/stop-words.csv')
        );
        $stopWords = array_map(function (array $stopWord) {
            return $stopWord[0];
        }, $csv);

        $response = $this->json('GET', '/core/v1/stop-words');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJson(['data' => $stopWords]);
    }

    /*
     * Update the stop words.
     */

    public function test_guest_cannot_update_stop_words()
    {
        $response = $this->json('PUT', '/core/v1/stop-words');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_update_stop_words()
    {
        Passport::actingAs(
            User::factory()->create()->makeServiceWorker(
                Service::factory()->create()
            )
        );

        $response = $this->json('PUT', '/core/v1/stop-words');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_cannot_update_stop_words()
    {
        Passport::actingAs(
            User::factory()->create()->makeServiceAdmin(
                Service::factory()->create()
            )
        );

        $response = $this->json('PUT', '/core/v1/stop-words');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_cannot_update_stop_words()
    {
        Passport::actingAs(
            User::factory()->create()->makeOrganisationAdmin(
                Organisation::factory()->create()
            )
        );

        $response = $this->json('PUT', '/core/v1/stop-words');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_global_admin_can_update_stop_words()
    {
        $user = User::factory()->create()->makeGlobalAdmin();

        Passport::actingAs($user);
        $response = $this->json('PUT', '/core/v1/stop-words', [
            'stop_words' => ['persons', 'people'],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJson([
            'data' => ['persons', 'people'],
        ]);
    }
}
