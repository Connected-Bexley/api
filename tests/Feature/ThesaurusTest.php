<?php

namespace Tests\Feature;

use Tests\TestCase;
use App\Models\User;
use App\Models\Service;
use App\Models\Organisation;
use Tests\UsesElasticsearch;
use Illuminate\Http\Response;
use Laravel\Passport\Passport;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Storage;

class ThesaurusTest extends TestCase implements UsesElasticsearch
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
        // Reindex to prevent synonyms persisting.
        $synonyms = Storage::disk('local')->get('elasticsearch/thesaurus.csv');
        Storage::cloud()->put('elasticsearch/thesaurus.csv', $synonyms);
        $this->artisan('ck:reindex-elasticsearch');

        parent::tearDown();
    }

    /*
     * View the thesaurus.
     */

    public function test_guest_cannot_view_thesaurus()
    {
        $response = $this->json('GET', '/core/v1/thesaurus');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_view_thesaurus()
    {
        $service = Service::factory()->create();
        $user = User::factory()->create()->makeServiceWorker($service);

        Passport::actingAs($user);

        $response = $this->json('GET', '/core/v1/thesaurus');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_cannot_view_thesaurus()
    {
        $service = Service::factory()->create();
        $user = User::factory()->create()->makeServiceAdmin($service);

        Passport::actingAs($user);

        $response = $this->json('GET', '/core/v1/thesaurus');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_cannot_view_thesaurus()
    {
        $organisation = Organisation::factory()->create();
        $user = User::factory()->create()->makeOrganisationAdmin($organisation);

        Passport::actingAs($user);

        $response = $this->json('GET', '/core/v1/thesaurus');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_global_admin_can_view_thesaurus()
    {
        $user = User::factory()->create()->makeGlobalAdmin();

        Passport::actingAs($user);
        $response = $this->json('GET', '/core/v1/thesaurus');

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJson([
            'data' => [
                ['autism', 'autistic', 'asd'],
                ['not drinking', 'dehydration'],
                ['dehydration', 'thirsty', 'drought'],
            ],
        ]);
    }

    /*
     * Update the thesaurus.
     */

    public function test_guest_cannot_update_thesaurus()
    {
        $response = $this->json('PUT', '/core/v1/thesaurus');

        $response->assertStatus(Response::HTTP_UNAUTHORIZED);
    }

    public function test_service_worker_cannot_update_thesaurus()
    {
        $service = Service::factory()->create();
        $user = User::factory()->create()->makeServiceWorker($service);

        Passport::actingAs($user);

        $response = $this->json('PUT', '/core/v1/thesaurus');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_service_admin_cannot_update_thesaurus()
    {
        $service = Service::factory()->create();
        $user = User::factory()->create()->makeServiceAdmin($service);

        Passport::actingAs($user);

        $response = $this->json('PUT', '/core/v1/thesaurus');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_organisation_admin_cannot_update_thesaurus()
    {
        $organisation = Organisation::factory()->create();
        $user = User::factory()->create()->makeOrganisationAdmin($organisation);

        Passport::actingAs($user);

        $response = $this->json('PUT', '/core/v1/thesaurus');

        $response->assertStatus(Response::HTTP_FORBIDDEN);
    }

    public function test_global_admin_can_update_thesaurus()
    {
        $user = User::factory()->create()->makeGlobalAdmin();

        Passport::actingAs($user);
        $response = $this->json('PUT', '/core/v1/thesaurus', [
            'synonyms' => [
                ['persons', 'people'],
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJson([
            'data' => [
                ['persons', 'people'],
            ],
        ]);
    }

    public function test_thesaurus_works_with_search()
    {
        $service = Service::factory()->create([
            'name' => 'Helping People',
        ]);

        sleep(1);

        $user = User::factory()->create()->makeGlobalAdmin();

        Passport::actingAs($user);

        $updateResponse = $this->json('PUT', '/core/v1/thesaurus', [
            'synonyms' => [
                ['persons', 'people'],
            ],
        ]);

        $updateResponse->assertStatus(Response::HTTP_OK);

        sleep(1);

        $searchResponse = $this->json('POST', '/core/v1/search', [
            'query' => 'persons'
        ]);
        $searchResponse->assertJsonFragment([
            'id' => $service->id,
        ]);
    }

    /*
     * Multi-word synonyms.
     */

    public function test_invalid_multi_word_upload_fails()
    {
        $user = User::factory()->create()->makeGlobalAdmin();

        Passport::actingAs($user);
        $response = $this->json('PUT', '/core/v1/thesaurus', [
            'synonyms' => [
                ['multi word', 'another here'],
            ],
        ]);

        $response->assertStatus(Response::HTTP_UNPROCESSABLE_ENTITY);
    }

    public function test_simple_contraction_works_with_search()
    {
        $service = Service::factory()->create([
            'name' => 'People Not Drinking Enough',
        ]);

        sleep(1);

        $user = User::factory()->create()->makeGlobalAdmin();

        Passport::actingAs($user);
        $updateResponse = $this->json('PUT', '/core/v1/thesaurus', [
            'synonyms' => [
                ['not drinking', 'dehydration'],
            ],
        ]);

        $updateResponse->assertStatus(Response::HTTP_OK);

        sleep(1);

        // Using single word.
        $searchResponse = $this->json('POST', '/core/v1/search', [
            'query' => 'dehydration',
        ]);
        $searchResponse->assertJsonFragment([
            'id' => $service->id,
        ]);

        // Using multi-word.
        $searchResponse = $this->json('POST', '/core/v1/search', [
            'query' => 'not drinking',
        ]);
        $searchResponse->assertJsonFragment([
            'id' => $service->id,
        ]);
    }

    public function test_simple_contraction_works_with_further_synonyms_with_search()
    {
        $service = Service::factory()->create([
            'name' => 'People Not Drinking Enough',
        ]);

        sleep(1);

        $user = User::factory()->create()->makeGlobalAdmin();

        Passport::actingAs($user);
        $updateResponse = $this->json('PUT', '/core/v1/thesaurus', [
            'synonyms' => [
                ['not drinking', 'dehydration'],
                ['dehydration', 'thirsty', 'drought'],
            ],
        ]);

        $updateResponse->assertStatus(Response::HTTP_OK);

        sleep(1);

        $searchResponse = $this->json('POST', '/core/v1/search', [
            'query' => 'thirsty',
        ]);
        $searchResponse->assertJsonFragment([
            'id' => $service->id,
        ]);
    }
}
