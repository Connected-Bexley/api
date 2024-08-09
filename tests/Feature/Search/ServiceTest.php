<?php

namespace Tests\Feature\Search;

use Carbon\Carbon;
use Tests\TestCase;
use App\Models\Service;
use App\Models\Taxonomy;
use App\Models\Collection;
use App\Models\Organisation;
use Tests\UsesElasticsearch;
use Illuminate\Http\Response;
use App\Models\ServiceLocation;
use Illuminate\Support\Facades\DB;

class ServiceTest extends TestCase implements UsesElasticsearch
{
    /**
     * Setup the test environment.
     */
    protected function setUp(): void
    {
        parent::setUp();

        $this->truncateTaxonomies();
        $this->truncateCollectionCategories();
        $this->truncateCollectionPersonas();
    }

    /*
     * Perform a search for services.
     */

    public function test_guest_can_search(): void
    {
        $service = Service::factory()->create(['name' => 'This is a test']);
        $serviceLocation = ServiceLocation::factory()->create([
            'service_id' => $service->id,
        ]);

        sleep(1);

        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'This is a test',
        ]);

        $response->assertStatus(Response::HTTP_OK);

        $response->assertJsonStructure([
            'data' => [
                [
                    'id',
                    'organisation_id',
                    'has_logo',
                    'slug',
                    'name',
                    'type',
                    'status',
                    'intro',
                    'description',
                    'wait_time',
                    'is_free',
                    'fees_text',
                    'fees_url',
                    'testimonial',
                    'video_embed',
                    'url',
                    'contact_name',
                    'contact_phone',
                    'contact_email',
                    'show_referral_disclaimer',
                    'referral_method',
                    'referral_button_text',
                    'referral_email',
                    'referral_url',
                    'useful_infos',
                    'offerings',
                    'gallery_items',
                    'tags',
                    'category_taxonomies',
                    'eligibility_types' => [
                        'custom' => [
                            'age_group',
                            'disability',
                            'ethnicity',
                            'gender',
                            'income',
                            'language',
                            'housing',
                            'other',
                        ],
                        'taxonomies',
                    ],
                    'score',
                    'ends_at',
                    'last_modified_at',
                    'created_at',
                    'updated_at',
                    'service_locations' => [
                        [
                            'location',
                        ],
                    ],
                    'cqc_location_id',
                ],
            ],
            'links',
            'meta',
        ]);
    }

    public function test_query_matches_service_name(): void
    {
        $service = Service::factory()->create([
            'name' => 'hunt remind voice',
        ]);
        Service::factory()->count(5)->create();

        sleep(1);

        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'hunt remind voice',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $service->id,
        ]);
        $this->assertEquals($service->id, $this->getResponseContent($response)['data'][0]['id']);

        // Fuzzy match
        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'voice hunted',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $service->id,
        ]);
        $this->assertEquals($service->id, $this->getResponseContent($response)['data'][0]['id']);
    }

    public function test_query_matches_service_description(): void
    {
        $service = Service::factory()->create(['description' => $this->textContainingPhrases(['rods game sleeps'], 500)]);
        Service::factory()->count(5)->create();

        sleep(1);

        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'rods game sleeps',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $service->id,
        ]);
        $this->assertEquals($service->id, $this->getResponseContent($response)['data'][0]['id']);

        // Fuzzy match
        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'sleep games',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $service->id,
        ]);
        $this->assertEquals($service->id, $this->getResponseContent($response)['data'][0]['id']);
    }

    public function test_query_matches_taxonomy_name(): void
    {
        $service1 = Service::factory()->create();
        $taxonomy = Taxonomy::category()->children()->create([
            'slug' => 'singer-tonic-brings',
            'name' => 'Singer Tonic Brings',
            'order' => 1,
            'depth' => 1,
        ]);
        $service1->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy->id]);

        $service2 = Service::factory()->create();
        $taxonomy = Taxonomy::category()->children()->create([
            'slug' => 'inches-translated-brief',
            'name' => 'Inches Translated Brief',
            'order' => 1,
            'depth' => 1,
        ]);
        $service2->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy->id]);

        sleep(1);

        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'singer tonic brings',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $service1->id]);
        $this->assertEquals($service1->id, $this->getResponseContent($response)['data'][0]['id']);

        // Fuzzy
        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'bring a song',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $service1->id]);
        $this->assertEquals($service1->id, $this->getResponseContent($response)['data'][0]['id']);
    }

    public function test_query_matches_partial_taxonomy_name(): void
    {
        $service1 = Service::factory()->create();
        $taxonomy = Taxonomy::category()->children()->create([
            'slug' => 'blocks-grain-taps',
            'name' => 'Blocks Grain Taps',
            'order' => 1,
            'depth' => 1,
        ]);
        $service1->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy->id]);

        $service2 = Service::factory()->create();
        $taxonomy = Taxonomy::category()->children()->create([
            'slug' => 'cool-extra-reveal',
            'name' => 'Cool Extra Reveal',
            'order' => 1,
            'depth' => 1,
        ]);
        $service2->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy->id]);

        sleep(1);

        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'grain',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $service1->id]);
        $this->assertEquals($service1->id, $this->getResponseContent($response)['data'][0]['id']);

        // Fuzzy
        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'block',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $service1->id]);
        $this->assertEquals($service1->id, $this->getResponseContent($response)['data'][0]['id']);
    }

    public function test_query_matches_organisation_name(): void
    {
        $organisation1 = Organisation::factory()->create(['name' => 'Riches Resist Envy']);
        $organisation2 = Organisation::factory()->create();
        $service = Service::factory()->create(['organisation_id' => $organisation1->id]);
        Service::factory()->count(5)->create(['organisation_id' => $organisation2->id]);

        sleep(1);

        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'riches resist envy',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $service->id,
        ]);
        $this->assertEquals($service->id, $this->getResponseContent($response)['data'][0]['id']);

        // Fuzzy
        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'envy rich',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $service->id]);
        $this->assertEquals($service->id, $this->getResponseContent($response)['data'][0]['id']);
    }

    public function test_query_ranks_service_name_equivalent_to_organisation_name(): void
    {
        $organisation = Organisation::factory()->create(['name' => 'Hammer Play Swim']);
        $serviceWithRelevantOrganisationName = Service::factory()->create([
            'name' => 'Relevant Organisation',
            'organisation_id' => $organisation->id,
        ]);
        $serviceWithRelevantServiceName = Service::factory()->create([
            'name' => 'Hammer Play Swim',
        ]);
        $serviceWithRelevantIntro = Service::factory()->create([
            'name' => 'Relevant Intro',
            'intro' => 'Hammer Play Swim',
        ]);
        $serviceWithRelevantDescription = Service::factory()->create([
            'name' => 'Relevant Description',
            'description' => 'Hammer Play Swim',
        ]);
        $serviceWithRelevantTaxonomy = Service::factory()->create([
            'name' => 'Relevant Taxonomy',
        ]);
        $taxonomy = Taxonomy::category()->children()->create([
            'slug' => 'hammer-play-swim',
            'name' => 'Hammer Play Swim',
            'order' => 1,
            'depth' => 1,
        ]);
        $serviceWithRelevantTaxonomy->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy->id]);

        sleep(1);

        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'hammer play swim',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(5, 'data');
        $data = $this->getResponseContent($response)['data'];

        $this->assertTrue(in_array($serviceWithRelevantServiceName->id, [$data[0]['id'], $data[1]['id']]));
        $this->assertTrue(in_array($serviceWithRelevantOrganisationName->id, [$data[0]['id'], $data[1]['id']]));
    }

    public function test_query_ranks_perfect_match_above_fuzzy_match(): void
    {
        $service1 = Service::factory()->create(['name' => 'Could Avoid Plans']);
        $service2 = Service::factory()->create(['name' => 'Plan Avoids']);

        sleep(1);

        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'could avoid plans',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment([
            'id' => $service1->id,
        ]);

        $response->assertJsonFragment([
            'id' => $service2->id,
        ]);

        $data = $this->getResponseContent($response)['data'];
        $this->assertEquals($service1->id, $data[0]['id']);
        $this->assertEquals($service2->id, $data[1]['id']);
    }

    public function test_query_matches_service_intro(): void
    {
        $service = Service::factory()->create([
            'intro' => $this->textContainingPhrases(['glaze mild chats']),
        ]);

        Service::factory()->count(5)->create();

        sleep(1);

        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'glaze mild chats',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $service->id,
        ]);
        $this->assertEquals($service->id, $this->getResponseContent($response)['data'][0]['id']);

        // Fuzzy
        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'chat mild',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $service->id,
        ]);
        $this->assertEquals($service->id, $this->getResponseContent($response)['data'][0]['id']);
    }

    public function test_query_matches_single_word_from_service_description(): void
    {
        $service = Service::factory()->create([
            'description' => $this->textContainingPhrases(['accent'], 500),
        ]);

        sleep(1);

        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'accent',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $service->id]);
    }

    public function test_query_matches_multiple_words_from_service_description(): void
    {
        $service = Service::factory()->create([
            'description' => $this->textContainingPhrases(['hurls star humans']),
        ]);

        Service::factory()->create();

        sleep(1);

        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'hurls star humans',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $service->id]);
        $this->assertEquals($service->id, $this->getResponseContent($response)['data'][0]['id']);

        // Fuzzy
        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'human stars',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $service->id]);
        $this->assertEquals($service->id, $this->getResponseContent($response)['data'][0]['id']);
    }

    public function test_filter_by_wait_time_works(): void
    {
        $oneMonthWaitTimeService = Service::factory()->create(['wait_time' => Service::WAIT_TIME_MONTH]);
        $twoWeeksWaitTimeService = Service::factory()->create(['wait_time' => Service::WAIT_TIME_TWO_WEEKS]);
        $oneWeekWaitTimeService = Service::factory()->create(['wait_time' => Service::WAIT_TIME_ONE_WEEK]);

        sleep(1);

        $response = $this->json('POST', '/core/v1/search', [
            'wait_time' => Service::WAIT_TIME_TWO_WEEKS,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $oneWeekWaitTimeService->id]);
        $response->assertJsonFragment(['id' => $twoWeeksWaitTimeService->id]);
        $response->assertJsonMissing(['id' => $oneMonthWaitTimeService->id]);
    }

    public function test_filter_by_is_free_works(): void
    {
        $paidService = Service::factory()->create(['is_free' => false]);
        $freeService = Service::factory()->create(['is_free' => true]);

        sleep(1);

        $response = $this->json('POST', '/core/v1/search', [
            'is_free' => true,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $freeService->id]);
        $response->assertJsonMissing(['id' => $paidService->id]);
    }

    public function test_order_by_location_works(): void
    {
        $service = Service::factory()->create();
        $serviceLocation = ServiceLocation::factory()->create(['service_id' => $service->id]);
        DB::table('locations')->where('id', $serviceLocation->location->id)->update(['lat' => 19.955, 'lon' => 19.955]);
        $service->save();

        $service2 = Service::factory()->create();
        $serviceLocation2 = ServiceLocation::factory()->create(['service_id' => $service2->id]);
        DB::table('locations')->where('id', $serviceLocation2->location->id)->update(['lat' => 20, 'lon' => 20]);
        $service2->save();

        $service3 = Service::factory()->create();
        $serviceLocation3 = ServiceLocation::factory()->create(['service_id' => $service3->id]);
        DB::table('locations')->where('id', $serviceLocation3->location->id)->update(['lat' => 20.05, 'lon' => 20.05]);
        $service3->save();

        sleep(1);

        $response = $this->json('POST', '/core/v1/search', [
            'order' => 'distance',
            'location' => [
                'lat' => 20,
                'lon' => 20,
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $service2->id]);
        $hits = json_decode($response->getContent(), true)['data'];
        $this->assertCount(3, $hits);
        $this->assertEquals($service2->id, $hits[0]['id']);
        $this->assertEquals($service->id, $hits[1]['id']);
        $this->assertEquals($service3->id, $hits[2]['id']);
    }

    public function test_query_and_filter_works(): void
    {
        $service = Service::factory()->create(['name' => 'Crass Tall Films']);
        $collection = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'self-help',
            'name' => 'Self Help',
            'meta' => [],
            'order' => 1,
        ]);
        $taxonomy = Taxonomy::category()->children()->create([
            'slug' => 'collection',
            'name' => 'Collection',
            'order' => 1,
            'depth' => 1,
        ]);
        $collectionTaxonomy = $collection->collectionTaxonomies()->create(['taxonomy_id' => $taxonomy->id]);
        $service->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy->id]);
        $service->save();

        $differentService = Service::factory()->create(['name' => 'Crass Tall Films']);
        $differentCollection = Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'refugees',
            'name' => 'Refugees',
            'meta' => [],
            'order' => 1,
        ]);
        $differentTaxonomy = Taxonomy::category()->children()->create([
            'slug' => 'persona',
            'name' => 'Persona',
            'order' => 2,
            'depth' => 1,
        ]);
        $differentCollection->collectionTaxonomies()->create(['taxonomy_id' => $differentTaxonomy->id]);
        $differentService->serviceTaxonomies()->create(['taxonomy_id' => $differentTaxonomy->id]);
        $differentService->save();

        sleep(1);

        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'crass tall films',
            'category' => 'self-help',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $service->id]);
        $response->assertJsonMissing(['id' => $differentService->id]);
    }

    public function test_query_and_filter_works_when_query_does_not_match(): void
    {
        $service = Service::factory()->create(['name' => 'Richer Value Term']);
        $collection = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'self-help',
            'name' => 'Self Help',
            'meta' => [],
            'order' => 1,
        ]);
        $taxonomy = Taxonomy::category()->children()->create([
            'slug' => 'collection',
            'name' => 'Collection',
            'order' => 1,
            'depth' => 1,
        ]);
        $collectionTaxonomy = $collection->collectionTaxonomies()->create(['taxonomy_id' => $taxonomy->id]);
        $service->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy->id]);
        $service->save();

        $differentService = Service::factory()->create(['name' => 'Richer Value Term']);
        $differentCollection = Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'refugees',
            'name' => 'Refugees',
            'meta' => [],
            'order' => 1,
        ]);
        $differentTaxonomy = Taxonomy::category()->children()->create([
            'slug' => 'persona',
            'name' => 'Persona',
            'order' => 2,
            'depth' => 1,
        ]);
        $differentCollection->collectionTaxonomies()->create(['taxonomy_id' => $differentTaxonomy->id]);
        $differentService->serviceTaxonomies()->create(['taxonomy_id' => $differentTaxonomy->id]);
        $differentService->save();

        sleep(1);

        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'affair uses loud',
            'category' => 'self-help',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(0, 'data');
    }

    public function test_only_active_services_returned(): void
    {
        $activeService = Service::factory()->create([
            'name' => 'Sobs Short Hosts',
            'status' => Service::STATUS_ACTIVE,
        ]);
        $inactiveService = Service::factory()->create([
            'name' => 'Sobs Short Hosts',
            'status' => Service::STATUS_INACTIVE,
        ]);

        sleep(1);

        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'Sobs Short Hosts',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $activeService->id]);
        $response->assertJsonMissing(['id' => $inactiveService->id]);
    }

    public function test_order_by_location_return_services_less_than_limited_miles_away(): void
    {
        $service1 = Service::factory()->create();
        $serviceLocation = ServiceLocation::factory()->create(['service_id' => $service1->id]);
        DB::table('locations')->where('id', $serviceLocation->location->id)->update(['lat' => 0, 'lon' => 0]);
        $service1->save();

        $service2 = Service::factory()->create();
        $serviceLocation2 = ServiceLocation::factory()->create(['service_id' => $service2->id]);
        DB::table('locations')->where('id', $serviceLocation2->location->id)->update(['lat' => 45, 'lon' => 90]);
        $service2->save();

        $service3 = Service::factory()->create();
        $serviceLocation3 = ServiceLocation::factory()->create(['service_id' => $service3->id]);
        DB::table('locations')->where('id', $serviceLocation3->location->id)->update(['lat' => 90, 'lon' => 180]);
        $service3->save();

        sleep(1);

        $response = $this->json('POST', '/core/v1/search', [
            'order' => 'distance',
            'location' => [
                'lat' => 45,
                'lon' => 90,
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $service2->id]);
        $response->assertJsonMissing(['id' => $service1->id]);
        $response->assertJsonMissing(['id' => $service3->id]);
    }

    public function test_order_by_location_return_services_less_than_1_mile_away(): void
    {
        // > 1 mile
        $service1 = Service::factory()->create();
        $serviceLocation = ServiceLocation::factory()->create(['service_id' => $service1->id]);
        DB::table('locations')->where('id', $serviceLocation->location->id)->update(['lat' => 51.469954129107016, 'lon' => -0.3973609967291171]);
        $service1->save();

        // < 1 mile
        $service2 = Service::factory()->create();
        $serviceLocation2 = ServiceLocation::factory()->create(['service_id' => $service2->id]);
        DB::table('locations')->where('id', $serviceLocation2->location->id)->update(['lat' => 51.46813624630186, 'lon' => -0.38543053111827796]);
        $service2->save();

        // > 1 mile
        $service3 = Service::factory()->create();
        $serviceLocation3 = ServiceLocation::factory()->create(['service_id' => $service3->id]);
        DB::table('locations')->where('id', $serviceLocation3->location->id)->update(['lat' => 51.47591520714541, 'lon' => -0.41139431461981674]);
        $service3->save();

        sleep(1);

        $response = $this->json('POST', '/core/v1/search', [
            'order' => 'distance',
            'distance' => 1,
            'location' => [
                'lat' => 51.46843366223185,
                'lon' => -0.3674811879751439,
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $service2->id]);
        $response->assertJsonMissing(['id' => $service1->id]);
        $response->assertJsonMissing(['id' => $service3->id]);
    }

    public function test_order_by_relevance_with_location_return_services_less_than_limited_miles_away(): void
    {
        $service1 = Service::factory()->create();
        $serviceLocation = ServiceLocation::factory()->create(['service_id' => $service1->id]);
        DB::table('locations')->where('id', $serviceLocation->location->id)->update(['lat' => 0, 'lon' => 0]);
        $service1->save();

        $service2 = Service::factory()->create(['name' => 'Copper Pipe Lace']);
        $serviceLocation2 = ServiceLocation::factory()->create(['service_id' => $service2->id]);
        DB::table('locations')->where('id', $serviceLocation2->location->id)->update(['lat' => 45.01, 'lon' => 90.01]);
        $service2->save();

        $organisation3 = Organisation::factory()->create(['name' => 'Copper Pipe Lace']);
        $service3 = Service::factory()->create(['organisation_id' => $organisation3->id]);
        $serviceLocation3 = ServiceLocation::factory()->create(['service_id' => $service3->id]);
        DB::table('locations')->where('id', $serviceLocation3->location->id)->update(['lat' => 45, 'lon' => 90]);
        $service3->save();

        $service4 = Service::factory()->create();
        $serviceLocation4 = ServiceLocation::factory()->create(['service_id' => $service4->id]);
        DB::table('locations')->where('id', $serviceLocation4->location->id)->update(['lat' => 90, 'lon' => 180]);
        $service4->save();

        sleep(1);

        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'copper pipe lace',
            'order' => 'relevance',
            'location' => [
                'lat' => 45,
                'lon' => 90,
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $service2->id]);
        $response->assertJsonFragment(['id' => $service3->id]);
        $response->assertJsonMissing(['id' => $service1->id]);
        $response->assertJsonMissing(['id' => $service4->id]);

        $data = $this->getResponseContent($response)['data'];
        $this->assertEquals(2, count($data));
        $this->assertTrue(in_array($service2->id, [$data[0]['id'], $data[1]['id']]));
        $this->assertTrue(in_array($service3->id, [$data[0]['id'], $data[1]['id']]));
    }

    public function test_order_by_relevance_with_location_return_services_less_than_1_mile_away(): void
    {
        // Not relevant > 1 mile
        $service1 = Service::factory()->create();
        $serviceLocation = ServiceLocation::factory()->create(['service_id' => $service1->id]);
        DB::table('locations')->where('id', $serviceLocation->location->id)->update(['lat' => 51.469954129107016, 'lon' => -0.3973609967291171]);
        $service1->save();

        // Relevant < 1 mile
        $service2 = Service::factory()->create(['intro' => 'Unable Regard Festivity']);
        $serviceLocation2 = ServiceLocation::factory()->create(['service_id' => $service2->id]);
        DB::table('locations')->where('id', $serviceLocation2->location->id)->update(['lat' => 51.46813624630186, 'lon' => -0.38543053111827796]);
        $service2->save();

        // Relevant < 1 mile
        $organisation3 = Organisation::factory()->create(['name' => 'Unable Regard Festivity']);
        $service3 = Service::factory()->create(['organisation_id' => $organisation3->id]);
        $serviceLocation3 = ServiceLocation::factory()->create(['service_id' => $service3->id]);
        DB::table('locations')->where('id', $serviceLocation3->location->id)->update(['lat' => 51.46933926508632, 'lon' => -0.3745729484111921]);
        $service3->save();

        // Relevant > 1 mile
        $service4 = Service::factory()->create(['name' => 'Unable Regard Festivity']);
        $serviceLocation4 = ServiceLocation::factory()->create(['service_id' => $service4->id]);
        DB::table('locations')->where('id', $serviceLocation4->location->id)->update(['lat' => 51.46741441979822, 'lon' => -0.40152378521657234]);
        $service4->save();

        sleep(1);

        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'unable regard festivity',
            'order' => 'relevance',
            'distance' => 1,
            'location' => [
                'lat' => 51.46843366223185,
                'lon' => -0.3674811879751439,
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $service2->id]);
        $response->assertJsonFragment(['id' => $service3->id]);
        $response->assertJsonMissing(['id' => $service1->id]);
        $response->assertJsonMissing(['id' => $service4->id]);

        $data = $this->getResponseContent($response)['data'];
        $this->assertEquals(2, count($data));
        $this->assertEquals($service3->id, $data[0]['id']);
        $this->assertEquals($service2->id, $data[1]['id']);
    }

    public function test_searches_are_carried_out_in_specified_collections(): void
    {
        $collection1 = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'self-help',
            'name' => 'Self Help',
            'meta' => [],
            'order' => 1,
        ]);
        $taxonomy1 = Taxonomy::category()->children()->create([
            'slug' => 'test-taxonomy-1',
            'name' => 'Test Taxonomy 1',
            'order' => 1,
            'depth' => 1,
        ]);
        $collection1->collectionTaxonomies()->create(['taxonomy_id' => $taxonomy1->id]);

        $collection2 = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'addiction',
            'name' => 'Addiction',
            'meta' => [],
            'order' => 2,
        ]);
        $taxonomy2 = Taxonomy::category()->children()->create([
            'slug' => 'test-taxonomy-2',
            'name' => 'Test Taxonomy 2',
            'order' => 2,
            'depth' => 1,
        ]);
        $collection2->collectionTaxonomies()->create(['taxonomy_id' => $taxonomy2->id]);

        $collection3 = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'refugees',
            'name' => 'Refugees',
            'meta' => [],
            'order' => 2,
        ]);
        $taxonomy3 = Taxonomy::category()->children()->create([
            'slug' => 'test-taxonomy-3',
            'name' => 'Test Taxonomy 3',
            'order' => 2,
            'depth' => 1,
        ]);
        $collection3->collectionTaxonomies()->create(['taxonomy_id' => $taxonomy3->id]);

        // Service 1 is in Collection 1
        $service1 = Service::factory()->create(['name' => 'Learns Snows Incomes']);
        $service1->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy1->id]);
        $service1->save();

        // Service 2 is in Collection 2
        $service2 = Service::factory()->create(['name' => 'Snaps Snow Focus']);
        $service2->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy2->id]);
        $service2->save();

        // Service 3 is in Collection 2
        $service3 = Service::factory()->create(['name' => 'Former Snow Bond']);
        $service3->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy2->id]);
        $service3->save();

        // Service 4 is in Collection 3
        $service4 = Service::factory()->create(['name' => 'Grades Snow Foster']);
        $service4->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy3->id]);
        $service4->save();

        sleep(1);

        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'Snow',
            'category' => implode(',', [$collection2->slug, $collection3->slug]),
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $service2->id]);
        $response->assertJsonFragment(['id' => $service3->id]);
        $response->assertJsonFragment(['id' => $service4->id]);
        $response->assertJsonMissing(['id' => $service1->id]);
    }

    public function test_location_searches_are_carried_out_in_specified_collections(): void
    {
        $collection1 = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'self-help',
            'name' => 'Self Help',
            'meta' => [],
            'order' => 1,
        ]);
        $taxonomy1 = Taxonomy::category()->children()->create([
            'slug' => 'test-taxonomy-1',
            'name' => 'Test Taxonomy 1',
            'order' => 1,
            'depth' => 1,
        ]);
        $collection1->collectionTaxonomies()->create(['taxonomy_id' => $taxonomy1->id]);

        $collection2 = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'addiction',
            'name' => 'Addiction',
            'meta' => [],
            'order' => 2,
        ]);
        $taxonomy2 = Taxonomy::category()->children()->create([
            'slug' => 'test-taxonomy-2',
            'name' => 'Test Taxonomy 2',
            'order' => 2,
            'depth' => 1,
        ]);
        $collection2->collectionTaxonomies()->create(['taxonomy_id' => $taxonomy2->id]);

        // Service 1 is in Collection 1
        $service1 = Service::factory()->create(['name' => 'Bar']);
        $service1->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy1->id]);
        $serviceLocation1 = ServiceLocation::factory()->create(['service_id' => $service1->id]);
        DB::table('locations')->where('id', $serviceLocation1->location->id)->update(['lat' => 041.9374814, 'lon' => -8.8643883]);
        $service1->save();

        // Service 2 is in Collection 2
        $service2 = Service::factory()->create(['name' => 'Bim']);
        $service2->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy2->id]);
        $serviceLocation2 = ServiceLocation::factory()->create(['service_id' => $service2->id]);
        DB::table('locations')->where('id', $serviceLocation2->location->id)->update(['lat' => 041.9374814, 'lon' => -8.8643883]);
        $service2->save();

        // Service 3 is in Collection 2
        $service3 = Service::factory()->create(['name' => 'Foo']);
        $service3->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy2->id]);
        $serviceLocation3 = ServiceLocation::factory()->create(['service_id' => $service3->id]);
        DB::table('locations')->where('id', $serviceLocation3->location->id)->update(['lat' => 90, 'lon' => 90]);
        $service3->save();

        sleep(1);

        $response = $this->json('POST', '/core/v1/search', [
            'order' => 'distance',
            'category' => implode(',', [$collection2->slug]),
            'location' => [
                'lat' => 041.9374814,
                'lon' => -8.8643883,
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $service2->id]);
        $response->assertJsonMissing(['id' => $service1->id]);
        $response->assertJsonMissing(['id' => $service3->id]);
    }

    public function test_location_searches_and_queries_are_carried_out_in_specified_collections(): void
    {
        $collection1 = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'self-help',
            'name' => 'Self Help',
            'meta' => [],
            'order' => 1,
        ]);
        $taxonomy1 = Taxonomy::category()->children()->create([
            'slug' => 'test-taxonomy-1',
            'name' => 'Test Taxonomy 1',
            'order' => 1,
            'depth' => 1,
        ]);
        $collection1->collectionTaxonomies()->create(['taxonomy_id' => $taxonomy1->id]);

        $collection2 = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'addiction',
            'name' => 'Addiction',
            'meta' => [],
            'order' => 2,
        ]);
        $taxonomy2 = Taxonomy::category()->children()->create([
            'slug' => 'test-taxonomy-2',
            'name' => 'Test Taxonomy 2',
            'order' => 2,
            'depth' => 1,
        ]);
        $collection2->collectionTaxonomies()->create(['taxonomy_id' => $taxonomy2->id]);

        $collection3 = Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'refugees',
            'name' => 'Refugees',
            'meta' => [],
            'order' => 1,
        ]);
        $taxonomy3 = Taxonomy::category()->children()->create([
            'slug' => 'test-taxonomy-3',
            'name' => 'Test Taxonomy 3',
            'order' => 2,
            'depth' => 1,
        ]);
        $collection3->collectionTaxonomies()->create(['taxonomy_id' => $taxonomy3->id]);

        // Service 1 is in Collection 1
        $service1 = Service::factory()->create(['name' => 'Snow Forgot Dare']);
        $service1->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy1->id]);
        $serviceLocation1 = ServiceLocation::factory()->create(['service_id' => $service1->id]);
        DB::table('locations')->where('id', $serviceLocation1->location->id)->update(['lat' => 041.9374814, 'lon' => -8.8643883]);
        $service1->save();

        // Service 2 is in Collection 2
        $service2 = Service::factory()->create(['name' => 'Grace Vanish Organs']);
        $service2->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy2->id]);
        $service2->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy3->id]);
        $serviceLocation2 = ServiceLocation::factory()->create(['service_id' => $service2->id]);
        DB::table('locations')->where('id', $serviceLocation2->location->id)->update(['lat' => 041.9374814, 'lon' => -8.8643883]);
        $service2->save();

        // Service 3 is in Collection 2
        $service3 = Service::factory()->create(['name' => 'Mouth Score Limp']);
        $service3->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy2->id]);
        $serviceLocation3 = ServiceLocation::factory()->create(['service_id' => $service3->id]);
        DB::table('locations')->where('id', $serviceLocation3->location->id)->update(['lat' => 90, 'lon' => 90]);
        $service3->save();

        // Service 4 is in Collection 3
        $service4 = Service::factory()->create(['name' => 'Logo Vanish Froth']);
        $service4->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy3->id]);
        $serviceLocation4 = ServiceLocation::factory()->create(['service_id' => $service4->id]);
        DB::table('locations')->where('id', $serviceLocation4->location->id)->update(['lat' => 041.9374814, 'lon' => -8.8643883]);
        $service4->save();

        sleep(1);

        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'vanish',
            'persona' => implode(',', [$collection3->slug]),
            'location' => [
                'lat' => 041.9374814,
                'lon' => -8.8643883,
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['id' => $service4->id]);
        $response->assertJsonFragment(['id' => $service2->id]);
        $response->assertJsonMissing(['id' => $service1->id]);
        $response->assertJsonMissing(['id' => $service3->id]);
    }

    public function test_query_matches_eligibility_name(): void
    {
        // Given a service has an eligibility age group taxonomy of 12 - 15 years
        $service = Service::factory()
            ->create();

        $parentTaxonomy = Taxonomy::serviceEligibility()
            ->children()
            ->where(['name' => 'Age Group'])
            ->first();

        $taxonomy = $parentTaxonomy->children()
            ->where(['name' => '12 - 15 years'])
            ->first();

        $service->serviceEligibilities()->create(['taxonomy_id' => $taxonomy->id]);

        // Trigger a reindex
        $service->save();

        sleep(1);

        // When a search is performed with the age group taxonomies of 12 - 15 years and 16 - 18 years
        $response = $this->json('POST', '/core/v1/search', [
            'eligibilities' => [
                '12 - 15 years',
                '16 - 18 years',
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);

        // Then the results should include the service
        $response->assertJsonFragment(['id' => $service->id]);
    }

    public function test_no_results_when_query_does_not_match_eligibility_name(): void
    {
        // Given a service has an eligibility age group taxonomy of 12 - 15 years
        $service = Service::factory()
            ->create();

        $parentTaxonomy = Taxonomy::serviceEligibility()
            ->children()
            ->where(['name' => 'Age Group'])
            ->first();

        $taxonomy = $parentTaxonomy->children()
            ->where(['name' => '12 - 15 years'])
            ->first();

        $service->serviceEligibilities()->create(['taxonomy_id' => $taxonomy->id]);

        // Trigger a reindex
        $service->save();

        sleep(1);

        // When a search is performed with the age group taxonomies of 16 - 18 years and 19 - 20 years
        $response = $this->json('POST', '/core/v1/search', [
            'eligibilities' => [
                '16 - 18 years',
                '19 - 20 years',
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonMissing(['id' => $service->id]);
    }

    public function test_service_returned_in_result_if_it_has_no_eligibility_taxonomies_related_to_parent_of_searched_eligibility(): void
    {
        $service = Service::factory()
            ->create();

        $service->save();

        sleep(1);

        $response = $this->json('POST', '/core/v1/search', [
            'eligibilities' => [
                '16 - 18 years',
                '19 - 20 years',
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $service->id]);
    }

    public function test_query_results_filtered_when_eligibity_filter_applied(): void
    {
        $serviceEligibility1 = Taxonomy::where('name', '12 - 15 years')->firstOrFail();
        $serviceEligibility2 = Taxonomy::where('name', '16 - 18 years')->firstOrFail();
        $service1 = Service::factory()->create(['name' => 'Closer Tries Rust']);
        $service2 = Service::factory()->create(['intro' => $this->textContainingPhrases(['closer tries rust'])]);
        $service3 = Service::factory()->create(['description' => $this->textContainingPhrases(['closer tries rust'], 500)]);
        $service4 = Service::factory()->create();
        $taxonomy = Taxonomy::category()->children()->create([
            'slug' => 'closer-tries-rust',
            'name' => 'Closer Tries Rust',
            'order' => 1,
            'depth' => 1,
        ]);
        $service4->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy->id]);

        $service1->serviceEligibilities()->create([
            'taxonomy_id' => $serviceEligibility1->id,
        ]);
        $service4->serviceEligibilities()->create([
            'taxonomy_id' => $serviceEligibility1->id,
        ]);
        $service2->serviceEligibilities()->create([
            'taxonomy_id' => $serviceEligibility2->id,
        ]);

        // Reindex
        $service1->save();
        $service2->save();
        $service3->save();
        $service4->save();

        sleep(1);

        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'closer tries rust',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(4, 'data');

        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'closer tries rust',
            'eligibilities' => [
                '12 - 15 years'],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(3, 'data');
        $response->assertJsonFragment(['id' => $service1->id]);
        $response->assertJsonFragment(['id' => $service3->id]);
        $response->assertJsonFragment(['id' => $service4->id]);
        $response->assertJsonMissing(['id' => $service2->id]);
    }

    public function test_service_ranks_higher_if_eligibility_match(): void
    {
        // Given a service called Alpha Ltd has no eligibility age group taxonomies specified
        $serviceA = Service::factory()
            ->create([
                'name' => 'Alpha Ltd',
            ]);

        // And a service called Beta Ltd has an eligibility age group taxonomy of 12 - 15 years
        $serviceB = Service::factory()
            ->create([
                'name' => 'Beta Ltd',
            ]);

        $parentTaxonomy = Taxonomy::serviceEligibility()
            ->children()
            ->where(['name' => 'Age Group'])
            ->first();

        $taxonomyB = $parentTaxonomy->children()
            ->where(['name' => '12 - 15 years'])
            ->first();

        $serviceB->serviceEligibilities()->create([
            'taxonomy_id' => $taxonomyB->id,
        ]);

        // Trigger a reindex
        $serviceA->save();
        $serviceB->save();

        sleep(1);

        // When a search is performed with the age group taxonomies of 12 - 15 years and 16 - 18 years
        $response = $this->json('POST', '/core/v1/search', [
            'eligibilities' => [
                '12 - 15 years',
                '16 - 18 years',
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);

        $data = $this->getResponseContent($response)['data'];

        // Then the results should include both services
        $this->assertEquals(2, count($data));

        // And Beta Ltd should rank higher in the results
        $this->assertEquals($serviceB->id, $data[0]['id']);
        $this->assertEquals($serviceA->id, $data[1]['id']);
    }

    public function test_ranking_for_eligibility_higher_for_single_matching_eligibility_over_multiple_or_all(): void
    {
        $parentTaxonomy = Taxonomy::serviceEligibility()
            ->children()
            ->where(['name' => 'Age Group'])
            ->first();

        $taxonomyA = $parentTaxonomy->children()
            ->where(['name' => '12 - 15 years'])
            ->first();

        $taxonomyB = $parentTaxonomy->children()
            ->where(['name' => '16 - 18 years'])
            ->first();

        $taxonomyC = $parentTaxonomy->children()
            ->where(['name' => '19 - 20 years'])
            ->first();

        $serviceA = Service::factory()
            ->create([
                'name' => 'Single Eligibility',
            ]);

        $serviceA->serviceEligibilities()->create([
            'taxonomy_id' => $taxonomyA->id,
        ]);

        $serviceB = Service::factory()
            ->create([
                'name' => 'Double Eligibility',
            ]);

        $serviceB->serviceEligibilities()->createMany([
            ['taxonomy_id' => $taxonomyA->id],
            ['taxonomy_id' => $taxonomyB->id],
        ]);

        $serviceC = Service::factory()
            ->create([
                'name' => 'Multiple Eligibility',
            ]);

        $serviceC->serviceEligibilities()->createMany([
            ['taxonomy_id' => $taxonomyA->id],
            ['taxonomy_id' => $taxonomyB->id],
            ['taxonomy_id' => $taxonomyC->id],
        ]);

        $serviceD = Service::factory()
            ->create([
                'name' => 'All Eligibilities by default',
            ]);

        $serviceA->save();
        $serviceB->save();
        $serviceC->save();
        $serviceD->save();

        sleep(1);

        $response = $this->json('POST', '/core/v1/search', [
            'eligibilities' => [
                '12 - 15 years',
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);

        $data = $this->getResponseContent($response)['data'];

        $this->assertEquals(4, count($data));

        $this->assertTrue(in_array($serviceA->id, [$data[0]['id'], $data[1]['id']]));
        $this->assertTrue(in_array($serviceB->id, [$data[0]['id'], $data[1]['id'], $data[2]['id']]));
        $this->assertTrue(in_array($serviceC->id, [$data[1]['id'], $data[2]['id'], $data[3]['id']]));
        $this->assertTrue(in_array($serviceD->id, [$data[2]['id'], $data[3]['id']]));
    }

    public function test_search_ranking_given_more_relevant_matches_versus_less_hits_or_no_eligibilities_attached(): void
    {
        $parentTaxonomy = Taxonomy::serviceEligibility()
            ->children()
            ->where(['name' => 'Disability'])
            ->first();

        // Given a service called Alpha Ltd has no eligibility taxonomies specified
        $serviceA = Service::factory()
            ->create([
                'name' => 'Alpha Ltd',
            ]);

        // And a service called Bravo Ltd has matches one of the eligibility taxonomy terms that we will search for
        $serviceB = Service::factory()
            ->create([
                'name' => 'Bravo Ltd',
            ]);

        $taxonomyIdsB = $parentTaxonomy
            ->children()
            ->where(['name' => 'Bipolar disorder'])
            ->first()
            ->id;

        $serviceB->serviceEligibilities()->create(['taxonomy_id' => $taxonomyIdsB]);

        // And a service called Charlie Ltd matches two of the eligibility taxonomy terms that we will search for
        $serviceC = Service::factory()
            ->create([
                'name' => 'Charlie Ltd',
            ]);

        $taxonomyIdsC = $parentTaxonomy->children()
            ->whereIn('name', ['Bipolar disorder', 'Multiple sclerosis'])
            ->get()
            ->map(function ($taxonomy) {
                return ['taxonomy_id' => $taxonomy->id];
            });

        $serviceC->serviceEligibilities()->createMany($taxonomyIdsC->toArray());

        // And a service called Delta Ltd matches all of the eligibility taxonomy terms that we will search for
        $serviceD = Service::factory()
            ->create([
                'name' => 'Delta Ltd',
            ]);

        $taxonomyIdsD = $parentTaxonomy->children()
            ->whereIn('name', ['Bipolar disorder', 'Multiple sclerosis', 'Schizophrenia'])
            ->get()
            ->map(function ($taxonomy) {
                return ['taxonomy_id' => $taxonomy->id];
            });

        $serviceD->serviceEligibilities()->createMany($taxonomyIdsD->toArray());

        // And a Service called Inactive Service that matches all of the eligiblity taxonomies but is inactive
        $serviceIA = Service::factory()
            ->create([
                'name' => 'Inactive Service',
                'status' => Service::STATUS_INACTIVE,
            ]);

        $serviceIA->serviceEligibilities()->createMany($taxonomyIdsD->toArray());

        // Trigger reindex in a different order to ensure it's not just sorted by updated_at or something
        $serviceB->save();
        $serviceA->save();
        $serviceD->save();
        $serviceC->save();
        $serviceIA->save();

        sleep(1);

        $response = $this->json('POST', '/core/v1/search', [
            'eligibilities' => ['Bipolar disorder', 'Multiple sclerosis', 'Schizophrenia'],
        ]);

        $response->assertStatus(Response::HTTP_OK);

        $data = $this->getResponseContent($response)['data'];

        // There should be 4 results
        $this->assertEquals(4, count($data));

        // In this order
        $this->assertEquals($serviceD->id, $data[0]['id']);
        $this->assertEquals($serviceC->id, $data[1]['id']);
        $this->assertEquals($serviceB->id, $data[2]['id']);
        $this->assertEquals($serviceA->id, $data[3]['id']);

        // And the inactive one is filtered out
        $response->assertJsonMissing(['id' => $serviceIA->id]);
    }

    public function test_services_with_a_higher_score_are_more_relevant(): void
    {
        $organisation = \App\Models\Organisation::factory()->create();
        $serviceParams = [
            'organisation_id' => $organisation->id,
            'name' => 'Royal Organs Hike',
        ];
        $service3 = Service::factory()->create(array_merge($serviceParams, ['score' => 3]));
        $service5 = Service::factory()->create(array_merge($serviceParams, ['score' => 5]));
        $service0 = Service::factory()->create(array_merge($serviceParams, ['score' => 0]));

        sleep(1);

        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'royal organs hike',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $data = $this->getResponseContent($response)['data'];

        $this->assertEquals($service5->id, $data[0]['id']);
        $this->assertEquals($service3->id, $data[1]['id']);
        $this->assertEquals($service0->id, $data[2]['id']);
    }

    public function test_service_scores_are_secondary_to_distance(): void
    {
        $organisation = \App\Models\Organisation::factory()->create();
        $serviceParams = [
            'organisation_id' => $organisation->id,
            'name' => 'Stars Serve Remain',
        ];

        $service5 = Service::factory()->create(array_merge($serviceParams, ['score' => 5]));
        $serviceLocation = ServiceLocation::factory()->create(['service_id' => $service5->id]);
        DB::table('locations')->where('id', $serviceLocation->location->id)->update(['lat' => 45.01, 'lon' => 90.01]);
        $service5->save();

        $service0 = Service::factory()->create(array_merge($serviceParams, ['score' => 0]));
        $serviceLocation3 = ServiceLocation::factory()->create(['service_id' => $service0->id]);
        DB::table('locations')->where('id', $serviceLocation3->location->id)->update(['lat' => 45, 'lon' => 90]);
        $service0->save();

        sleep(1);

        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'stars serve remain',
            'order' => 'distance',
            'location' => [
                'lat' => 45,
                'lon' => 90,
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $data = $this->getResponseContent($response)['data'];

        $this->assertEquals($service0->id, $data[0]['id']);
        $this->assertEquals($service5->id, $data[1]['id']);
    }

    /**
     * @test
     */
    public function freshnessOfServiceDoesNotAffectSearchResult()
    {
        $organisation = \App\Models\Organisation::factory()->create();
        $serviceParams = [
            'organisation_id' => $organisation->id,
            'name' => 'Royal Organs Hike',
        ];
        $service5 = Service::factory()->create(array_merge($serviceParams, [
            'score' => 5,
            'created_at' => Carbon::now()->subMonths(24),
            'updated_at' => Carbon::now()->subMonths(24),
            'last_modified_at' => Carbon::now()->subMonths(24),
        ]));
        $service4 = Service::factory()->create(array_merge($serviceParams, [
            'score' => 4,
            'created_at' => Carbon::now()->subMonths(12),
            'updated_at' => Carbon::now()->subMonths(12),
            'last_modified_at' => Carbon::now()->subMonths(12),
        ]));
        $service3 = Service::factory()->create(array_merge($serviceParams, [
            'score' => 3,
            'created_at' => Carbon::now()->subMonths(6),
            'updated_at' => Carbon::now()->subMonths(6),
            'last_modified_at' => Carbon::now()->subMonths(6),
        ]));
        $service2 = Service::factory()->create(array_merge($serviceParams, [
            'score' => 2,
            'created_at' => Carbon::now()->subMonths(3),
            'updated_at' => Carbon::now()->subMonths(3),
            'last_modified_at' => Carbon::now()->subMonths(3),
        ]));
        $service1 = Service::factory()->create(array_merge($serviceParams, [
            'score' => 1,
            'created_at' => Carbon::now()->subMonths(1),
            'updated_at' => Carbon::now()->subMonths(1),
            'last_modified_at' => Carbon::now()->subMonths(1),
        ]));
        $service0 = Service::factory()->create(array_merge($serviceParams, [
            'score' => 0,
            'created_at' => Carbon::now(),
            'updated_at' => Carbon::now(),
            'last_modified_at' => Carbon::now(),
        ]));

        sleep(1);

        $response = $this->json('POST', '/core/v1/search', [
            'query' => 'royal organs hike',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $data = $this->getResponseContent($response)['data'];

        $this->assertEquals($service5->id, $data[0]['id']);
        $this->assertEquals($service4->id, $data[1]['id']);
        $this->assertEquals($service3->id, $data[2]['id']);
        $this->assertEquals($service2->id, $data[3]['id']);
        $this->assertEquals($service1->id, $data[4]['id']);
        $this->assertEquals($service0->id, $data[5]['id']);
    }
}
