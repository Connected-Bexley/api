<?php

namespace Tests\Feature\Search;

use App\Models\Collection;
use App\Models\Location;
use App\Models\Organisation;
use App\Models\OrganisationEvent;
use App\Models\Taxonomy;
use Illuminate\Http\Response;
use Tests\TestCase;
use Tests\UsesElasticsearch;

class EventTest extends TestCase implements UsesElasticsearch
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
     * Perform a search for events.
     */

    /**
     * @test
     */
    public function searchEventsAsGuest(): void
    {
        $response = $this->json('POST', '/core/v1/search/events', [
            'query' => 'test',
            'page' => 1,
            'per_page' => 20,
        ]);

        $response->assertStatus(Response::HTTP_OK);
    }

    /**
     * @test
     */
    public function searchEventsEmptyQueryAsGuest(): void
    {
        OrganisationEvent::factory()->count(5)->create();

        sleep(1);

        $response = $this->json('POST', '/core/v1/search/events', [
            'page' => 1,
            'per_page' => 20,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(5, 'data');
    }

    /**
     * @test
     */
    public function searchEventsMatchTitleAsGuest(): void
    {
        $event = OrganisationEvent::factory()->create();

        sleep(1);

        $response = $this->json('POST', '/core/v1/search/events', [
            'query' => $event->title,
            'page' => 1,
            'per_page' => 20,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $event->id,
        ]);
    }

    /**
     * @test
     */
    public function searchEventsMatchSingleWordFromTitleAsGuest(): void
    {
        $event = OrganisationEvent::factory()->create([
            'title' => 'Quick Brown Fox',
        ]);

        sleep(1);

        $response = $this->json('POST', '/core/v1/search/events', [
            'query' => 'brown',
            'page' => 1,
            'per_page' => 20,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $event->id]);
    }

    /**
     * @test
     */
    public function searchEventsMatchMultipleWordsFromTitleAsGuest(): void
    {
        $event = OrganisationEvent::factory()->create([
            'title' => 'Quick Brown Fox',
        ]);

        sleep(1);

        $response = $this->json('POST', '/core/v1/search/events', [
            'query' => 'quick fox',
            'page' => 1,
            'per_page' => 20,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $event->id]);
    }

    /**
     * @test
     */
    public function searchEventsMatchIntroAsGuest(): void
    {
        $event = OrganisationEvent::factory()->create();

        sleep(1);

        $response = $this->json('POST', '/core/v1/search/events', [
            'query' => $event->intro,
            'page' => 1,
            'per_page' => 20,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $event->id,
        ]);
    }

    /**
     * @test
     */
    public function searchEventsMatchSingleWordFromIntroAsGuest(): void
    {
        $event = OrganisationEvent::factory()->create([
            'intro' => 'This is an event that helps to homeless find temporary housing.',
        ]);

        sleep(1);

        $response = $this->json('POST', '/core/v1/search/events', [
            'query' => 'homeless',
            'page' => 1,
            'per_page' => 20,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $event->id]);
    }

    /**
     * @test
     */
    public function searchEventsMatchMultipleWordsFromIntroAsGuest(): void
    {
        $event = OrganisationEvent::factory()->create([
            'intro' => 'This is an event that helps to homeless find temporary housing.',
        ]);

        sleep(1);

        $response = $this->json('POST', '/core/v1/search/events', [
            'query' => 'temporary housing',
            'page' => 1,
            'per_page' => 20,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $event->id]);
    }

    /**
     * @test
     */
    public function searchEventsMatchDescriptionAsGuest(): void
    {
        $event = OrganisationEvent::factory()->create();

        sleep(1);

        $response = $this->json('POST', '/core/v1/search/events', [
            'query' => $event->description,
            'page' => 1,
            'per_page' => 20,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $event->id,
        ]);
    }

    /**
     * @test
     */
    public function searchEventsMatchSingleWordFromDescriptionAsGuest(): void
    {
        $event = OrganisationEvent::factory()->create([
            'description' => '<p>This is an event that helps to homeless find temporary housing.</p>',
        ]);

        sleep(1);

        $response = $this->json('POST', '/core/v1/search/events', [
            'query' => 'homeless',
            'page' => 1,
            'per_page' => 20,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $event->id]);
    }

    /**
     * @test
     */
    public function searchEventsMatchMultipleWordsFromDescriptionAsGuest(): void
    {
        $event = OrganisationEvent::factory()->create([
            'description' => '<p>This is an event that helps to homeless find temporary housing.</p>',
        ]);

        sleep(1);

        $response = $this->json('POST', '/core/v1/search/events', [
            'query' => 'temporary housing',
            'page' => 1,
            'per_page' => 20,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $event->id]);
    }

    /**
     * @test
     */
    public function searchEventsMatchCategoryNameAsGuest(): void
    {
        $event1 = OrganisationEvent::factory()->create([
            'title' => 'Event title',
        ]);
        $event2 = OrganisationEvent::factory()->create([
            'title' => 'Event title',
        ]);

        $taxonomy1 = Taxonomy::category()->children()->create([
            'slug' => 'quick-brown-fox',
            'name' => 'Quick Brown Fox',
            'order' => 1,
            'depth' => 1,
        ]);
        $taxonomy2 = Taxonomy::category()->children()->create([
            'slug' => 'lazy-dog',
            'name' => 'Lazy Dog',
            'order' => 1,
            'depth' => 1,
        ]);

        $event1->organisationEventTaxonomies()->create(['taxonomy_id' => $taxonomy1->id]);
        $event1->save();

        $event2->organisationEventTaxonomies()->create(['taxonomy_id' => $taxonomy2->id]);
        $event2->save();

        sleep(1);

        $response = $this->json('POST', '/core/v1/search/events', [
            'query' => 'Quick Brown Fox',
            'page' => 1,
            'per_page' => 20,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['id' => $event1->id]);
        $response->assertJsonMissing(['id' => $event2->id]);

        // Fuzzy
        $response = $this->json('POST', '/core/v1/search/events', [
            'query' => 'Foxy Brown',
            'page' => 1,
            'per_page' => 20,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['id' => $event1->id]);
        $response->assertJsonMissing(['id' => $event2->id]);
    }

    /**
     * @test
     */
    public function searchEventsRankTitleAboveIntroAsGuest(): void
    {
        $event1 = OrganisationEvent::factory()->create(['title' => 'This is a test']);
        $event2 = OrganisationEvent::factory()->create([
            'intro' => 'This is a test',
        ]);

        sleep(1);

        $response = $this->json('POST', '/core/v1/search/events', [
            'query' => 'test this',
            'order' => 'relevance',
            'page' => 1,
            'per_page' => 20,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment([
            'id' => $event1->id,
        ]);

        $response->assertJsonFragment([
            'id' => $event2->id,
        ]);

        $data = $this->getResponseContent($response)['data'];
        $this->assertEquals($event1->id, $data[0]['id']);
        $this->assertEquals($event2->id, $data[1]['id']);
    }

    /**
     * @test
     */
    public function searchEventsRankTitleAboveDescriptionAsGuest(): void
    {
        $event1 = OrganisationEvent::factory()->create(['title' => 'This is a test']);
        $event2 = OrganisationEvent::factory()->create([
            'description' => '<p>This is a test</p>',
        ]);

        sleep(1);

        $response = $this->json('POST', '/core/v1/search/events', [
            'query' => 'test this',
            'order' => 'relevance',
            'page' => 1,
            'per_page' => 20,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment([
            'id' => $event1->id,
        ]);

        $response->assertJsonFragment([
            'id' => $event2->id,
        ]);

        $data = $this->getResponseContent($response)['data'];
        $this->assertEquals($event1->id, $data[0]['id']);
        $this->assertEquals($event2->id, $data[1]['id']);
    }

    /**
     * @test
     */
    public function searchEventsRankIntroAboveDescriptionAsGuest(): void
    {
        $event1 = OrganisationEvent::factory()->create(['intro' => 'This is a test']);
        $event2 = OrganisationEvent::factory()->create([
            'description' => '<p>This is a test</p>',
        ]);

        sleep(1);

        $response = $this->json('POST', '/core/v1/search/events', [
            'query' => 'test this',
            'order' => 'relevance',
            'page' => 1,
            'per_page' => 20,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment([
            'id' => $event1->id,
        ]);

        $response->assertJsonFragment([
            'id' => $event2->id,
        ]);

        $data = $this->getResponseContent($response)['data'];
        $this->assertEquals($event1->id, $data[0]['id']);
        $this->assertEquals($event2->id, $data[1]['id']);
    }

    /**
     * @test
     */
    public function searchEventsRankPerfectMatchAboveCloseMatchAsGuest(): void
    {
        $event1 = OrganisationEvent::factory()->create(['title' => 'This is a test']);
        $event2 = OrganisationEvent::factory()->create(['title' => 'Those are tests']);

        sleep(1);

        $response = $this->json('POST', '/core/v1/search/events', [
            'query' => 'This is a test',
            'order' => 'relevance',
            'page' => 1,
            'per_page' => 20,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment([
            'id' => $event1->id,
        ]);

        $response->assertJsonFragment([
            'id' => $event2->id,
        ]);

        $data = $this->getResponseContent($response)['data'];
        $this->assertEquals($event1->id, $data[0]['id']);
        $this->assertEquals($event2->id, $data[1]['id']);
    }

    /**
     * @test
     */
    public function searchEventsRankDescriptionAboveCategoryNameAsGuest(): void
    {
        $event1 = OrganisationEvent::factory()->create([
            'description' => '<p>Quick Brown Fox</p>',
        ]);
        $taxonomy1 = Taxonomy::category()->children()->create([
            'slug' => 'lazy-dog',
            'name' => 'Lazy Dog',
            'order' => 1,
            'depth' => 1,
        ]);
        $event1->organisationEventTaxonomies()->create(['taxonomy_id' => $taxonomy1->id]);
        $event1->save();

        $event2 = OrganisationEvent::factory()->create([
            'description' => '<p>Lazy Dog</p>',
        ]);
        $taxonomy2 = Taxonomy::category()->children()->create([
            'slug' => 'quick-brown-fox',
            'name' => 'Quick Brown Fox',
            'order' => 1,
            'depth' => 1,
        ]);
        $event2->organisationEventTaxonomies()->create(['taxonomy_id' => $taxonomy2->id]);
        $event2->save();

        sleep(1);

        $response = $this->json('POST', '/core/v1/search/events', [
            'query' => 'Quick Brown Fox',
            'order' => 'relevance',
            'page' => 1,
            'per_page' => 20,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['id' => $event1->id]);
        $response->assertJsonFragment(['id' => $event2->id]);

        $data = $this->getResponseContent($response)['data'];
        $this->assertEquals($event1->id, $data[0]['id']);
        $this->assertEquals($event2->id, $data[1]['id']);

        // Fuzzy
        $response = $this->json('POST', '/core/v1/search/events', [
            'query' => 'Foxy Brown',
            'order' => 'relevance',
            'page' => 1,
            'per_page' => 20,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['id' => $event1->id]);
        $response->assertJsonFragment(['id' => $event2->id]);

        $data = $this->getResponseContent($response)['data'];
        $this->assertEquals($event1->id, $data[0]['id']);
        $this->assertEquals($event2->id, $data[1]['id']);
    }

    /**
     * @test
     */
    public function searchEventsFilterByCollectionNameAsGuest(): void
    {
        $event1 = OrganisationEvent::factory()->create([
            'title' => 'Event title',
        ]);
        $collection1 = Collection::create([
            'type' => Collection::TYPE_ORGANISATION_EVENT,
            'slug' => 'quick-brown-fox',
            'name' => 'Quick Brown Fox',
            'meta' => [],
            'order' => 1,
        ]);
        $taxonomy1 = Taxonomy::category()->children()->create([
            'slug' => 'category-1',
            'name' => 'Category 1',
            'order' => 1,
            'depth' => 1,
        ]);
        $collection1->collectionTaxonomies()->create(['taxonomy_id' => $taxonomy1->id]);
        $event1->organisationEventTaxonomies()->create(['taxonomy_id' => $taxonomy1->id]);
        $event1->save();

        $event2 = OrganisationEvent::factory()->create([
            'title' => 'Event title',
        ]);
        $collection2 = Collection::create([
            'type' => Collection::TYPE_ORGANISATION_EVENT,
            'slug' => 'lazy-dog',
            'name' => 'Lazy Dog',
            'meta' => [],
            'order' => 1,
        ]);
        $taxonomy2 = Taxonomy::category()->children()->create([
            'slug' => 'category-2',
            'name' => 'Category 2',
            'order' => 1,
            'depth' => 1,
        ]);
        $collection2->collectionTaxonomies()->create(['taxonomy_id' => $taxonomy2->id]);
        $event2->organisationEventTaxonomies()->create(['taxonomy_id' => $taxonomy2->id]);
        $event2->save();

        sleep(1);

        $response = $this->json('POST', '/core/v1/search/events', [
            'query' => 'Event title',
            'category' => 'quick-brown-fox',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['id' => $event1->id]);
        $response->assertJsonMissing(['id' => $event2->id]);

        // Fuzzy
        $response = $this->json('POST', '/core/v1/search/events', [
            'query' => 'Title Events',
            'category' => 'quick-brown-fox',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['id' => $event1->id]);
        $response->assertJsonMissing(['id' => $event2->id]);
    }

    /**
     * @test
     */
    public function searchEventsFilterByisFreeAsGuest(): void
    {
        $paidEvent = OrganisationEvent::factory()->nonFree()->create();
        $freeEvent = OrganisationEvent::factory()->create();

        sleep(1);

        $response = $this->json('POST', '/core/v1/search/events', [
            'is_free' => true,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $freeEvent->id]);
        $response->assertJsonMissing(['id' => $paidEvent->id]);
    }

    /**
     * @test
     */
    public function searchEventsFilterByNotisFreeAsGuest(): void
    {
        $paidEvent = OrganisationEvent::factory()->nonFree()->create();
        $freeEvent = OrganisationEvent::factory()->create();

        sleep(1);

        $response = $this->json('POST', '/core/v1/search/events', [
            'is_free' => false,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonMissing(['id' => $freeEvent->id]);
        $response->assertJsonFragment(['id' => $paidEvent->id]);
    }

    /**
     * @test
     */
    public function searchEventsFilterByisVirtualAsGuest(): void
    {
        $locatedEvent = OrganisationEvent::factory()->notVirtual()->create();
        $virtualEvent = OrganisationEvent::factory()->create();

        sleep(1);

        $response = $this->json('POST', '/core/v1/search/events', [
            'is_virtual' => true,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $virtualEvent->id]);
        $response->assertJsonMissing(['id' => $locatedEvent->id]);
    }

    /**
     * @test
     */
    public function searchEventsFilterByNotisVirtualAsGuest(): void
    {
        $locatedEvent = OrganisationEvent::factory()->notVirtual()->create();
        $virtualEvent = OrganisationEvent::factory()->create();

        sleep(1);

        $response = $this->json('POST', '/core/v1/search/events', [
            'is_virtual' => false,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonMissing(['id' => $virtualEvent->id]);
        $response->assertJsonFragment(['id' => $locatedEvent->id]);
    }

    /**
     * @test
     */
    public function searchEventsFilterByHasWheelchairAsGuest(): void
    {
        $locatedEvent = OrganisationEvent::factory()->notVirtual()->create();
        $virtualEvent = OrganisationEvent::factory()->create();
        $locatedEventWheelchairAccess = OrganisationEvent::factory()->create([
            'is_virtual' => false,
            'location_id' => function () {
                return Location::factory()->create([
                    'has_wheelchair_access' => true,
                ])->id;
            },
        ]);

        sleep(1);

        $response = $this->json('POST', '/core/v1/search/events', [
            'has_wheelchair_access' => true,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $locatedEventWheelchairAccess->id]);
        $response->assertJsonMissing(['id' => $virtualEvent->id]);
        $response->assertJsonMissing(['id' => $locatedEvent->id]);
    }

    /**
     * @test
     */
    public function searchEventsFilterByHasInductionLoopAsGuest(): void
    {
        $locatedEvent = OrganisationEvent::factory()->notVirtual()->create();
        $virtualEvent = OrganisationEvent::factory()->create();
        $locatedEventInductionLoop = OrganisationEvent::factory()->create([
            'is_virtual' => false,
            'location_id' => function () {
                return Location::factory()->create([
                    'has_induction_loop' => true,
                ])->id;
            },
        ]);

        sleep(1);

        $response = $this->json('POST', '/core/v1/search/events', [
            'has_induction_loop' => true,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $locatedEventInductionLoop->id]);
        $response->assertJsonMissing(['id' => $virtualEvent->id]);
        $response->assertJsonMissing(['id' => $locatedEvent->id]);
    }

    /**
     * @test
     */
    public function searchEventsFilterByHasAccessibleToiletAsGuest(): void
    {
        $locatedEvent = OrganisationEvent::factory()->notVirtual()->create();
        $virtualEvent = OrganisationEvent::factory()->create();
        $locatedEventAccessibleToilet = OrganisationEvent::factory()->create([
            'is_virtual' => false,
            'location_id' => function () {
                return Location::factory()->create([
                    'has_accessible_toilet' => true,
                ])->id;
            },
        ]);

        sleep(1);

        $response = $this->json('POST', '/core/v1/search/events', [
            'has_accessible_toilet' => true,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $locatedEventAccessibleToilet->id]);
        $response->assertJsonMissing(['id' => $virtualEvent->id]);
        $response->assertJsonMissing(['id' => $locatedEvent->id]);
    }

    /**
     * @test
     */
    public function searchEventsOnlyFutureDatesReturnedAsGuest(): void
    {
        $futureEvent = OrganisationEvent::factory()->create([
            'title' => 'Testing Dates',
            'start_date' => $this->faker->dateTimeBetween('+1 week', '+2 weeks'),
            'end_date' => $this->faker->dateTimeBetween('+2 week', '+3 weeks'),
        ]);
        $pastEvent = OrganisationEvent::factory()->create([
            'title' => 'Testing Dates',
            'start_date' => $this->faker->dateTimeBetween('-3 week', '-2 weeks'),
            'end_date' => $this->faker->dateTimeBetween('-2 week', '-1 weeks'),
        ]);

        sleep(1);

        $response = $this->json('POST', '/core/v1/search/events', [
            'query' => 'Testing Dates',
            'page' => 1,
            'per_page' => 20,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $futureEvent->id]);
        $response->assertJsonMissing(['id' => $pastEvent->id]);
    }

    /**
     * @test
     */
    public function searchEventsFilterByStartsAfterAsGuest(): void
    {
        $date1 = $this->faker->dateTimeBetween('+3 days', '+1 weeks');
        $date2 = $this->faker->dateTimeBetween('+2 week', '+3 weeks');
        $date3 = $this->faker->dateTimeBetween('+1 days', '+2 days');
        $endtime = $this->faker->time('H:i:s', '+1 hour');
        $starttime = $this->faker->time('H:i:s', 'now');

        $organisationEvent1 = OrganisationEvent::factory()->create([
            'start_date' => $date1->format('Y-m-d'),
            'end_date' => $date1->format('Y-m-d'),
            'start_time' => $starttime,
            'end_time' => $endtime,
        ]);
        $organisationEvent2 = OrganisationEvent::factory()->create([
            'start_date' => $date2->format('Y-m-d'),
            'end_date' => $date2->format('Y-m-d'),
            'start_time' => $starttime,
            'end_time' => $endtime,
        ]);
        $organisationEvent3 = OrganisationEvent::factory()->create([
            'start_date' => $date3->format('Y-m-d'),
            'end_date' => $date3->format('Y-m-d'),
            'start_time' => $starttime,
            'end_time' => $endtime,
        ]);

        sleep(1);

        $from = clone $date1;
        $from->modify('-1 day');

        $response = $this->json('POST', '/core/v1/search/events', [
            'starts_after' => $from->format('Y-m-d'),
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $organisationEvent1->id]);
        $response->assertJsonFragment(['id' => $organisationEvent2->id]);
        $response->assertJsonMissing(['id' => $organisationEvent3->id]);
    }

    /**
     * @test
     */
    public function searchEventsFilterByEndsBeforeAsGuest(): void
    {
        $date1 = $this->faker->dateTimeBetween('+3 days', '+1 weeks');
        $date2 = $this->faker->dateTimeBetween('+2 week', '+3 weeks');
        $date3 = $this->faker->dateTimeBetween('+1 days', '+2 days');
        $endtime = $this->faker->time('H:i:s', '+1 hour');
        $starttime = $this->faker->time('H:i:s', 'now');

        $organisationEvent1 = OrganisationEvent::factory()->create([
            'start_date' => $date1->format('Y-m-d'),
            'end_date' => $date1->format('Y-m-d'),
            'start_time' => $starttime,
            'end_time' => $endtime,
        ]);
        $organisationEvent2 = OrganisationEvent::factory()->create([
            'start_date' => $date2->format('Y-m-d'),
            'end_date' => $date2->format('Y-m-d'),
            'start_time' => $starttime,
            'end_time' => $endtime,
        ]);
        $organisationEvent3 = OrganisationEvent::factory()->create([
            'start_date' => $date3->format('Y-m-d'),
            'end_date' => $date3->format('Y-m-d'),
            'start_time' => $starttime,
            'end_time' => $endtime,
        ]);

        sleep(1);

        $to = clone $date1;
        $to->modify('+1 day');

        $response = $this->json('POST', '/core/v1/search/events', [
            'ends_before' => $to->format('Y-m-d'),
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $organisationEvent1->id]);
        $response->assertJsonFragment(['id' => $organisationEvent3->id]);
        $response->assertJsonMissing(['id' => $organisationEvent2->id]);
    }

    /**
     * @test
     */
    public function searchEventsFilterByDateRangeAsGuest(): void
    {
        $date1 = $this->faker->dateTimeBetween('+4 days', '+1 weeks');
        $date2 = $this->faker->dateTimeBetween('+2 week', '+3 weeks');
        $date3 = $this->faker->dateTimeBetween('+1 days', '+2 days');
        $endtime = $this->faker->time('H:i:s', '+1 hour');
        $starttime = $this->faker->time('H:i:s', 'now');

        $organisationEvent1 = OrganisationEvent::factory()->create([
            'start_date' => $date1->format('Y-m-d'),
            'end_date' => $date1->format('Y-m-d'),
            'start_time' => $starttime,
            'end_time' => $endtime,
        ]);
        $organisationEvent2 = OrganisationEvent::factory()->create([
            'start_date' => $date2->format('Y-m-d'),
            'end_date' => $date2->format('Y-m-d'),
            'start_time' => $starttime,
            'end_time' => $endtime,
        ]);
        $organisationEvent3 = OrganisationEvent::factory()->create([
            'start_date' => $date3->format('Y-m-d'),
            'end_date' => $date3->format('Y-m-d'),
            'start_time' => $starttime,
            'end_time' => $endtime,
        ]);

        sleep(1);

        $from = clone $date1;
        $to = clone $date1;
        $from->modify('-1 day');
        $to->modify('+1 day');

        $response = $this->json('POST', '/core/v1/search/events', [
            'starts_after' => $from->format('Y-m-d'),
            'ends_before' => $to->format('Y-m-d'),
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $organisationEvent1->id]);
        $response->assertJsonMissing(['id' => $organisationEvent3->id]);
        $response->assertJsonMissing(['id' => $organisationEvent2->id]);
    }

    /**
     * @test
     */
    public function searchEventsOrderByLocationReturnEventsLessThan5MilesAwayAsGuest(): void
    {
        $event1 = OrganisationEvent::factory()->create([
            'is_virtual' => false,
            'location_id' => function () {
                return Location::factory()->create([
                    'lat' => 0,
                    'lon' => 0,
                ])->id;
            },
        ]);

        $event2 = OrganisationEvent::factory()->create([
            'is_virtual' => false,
            'location_id' => function () {
                return Location::factory()->create([
                    'lat' => 45,
                    'lon' => 90,
                ])->id;
            },
        ]);

        $event3 = OrganisationEvent::factory()->create([
            'is_virtual' => false,
            'location_id' => function () {
                return Location::factory()->create([
                    'lat' => 90,
                    'lon' => 180,
                ])->id;
            },
        ]);

        sleep(1);

        $response = $this->json('POST', '/core/v1/search/events', [
            'order' => 'distance',
            'location' => [
                'lat' => 45,
                'lon' => 90,
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $event2->id]);
        $response->assertJsonMissing(['id' => $event1->id]);
        $response->assertJsonMissing(['id' => $event3->id]);
    }

    /**
     * @test
     */
    public function searchEventsOrderByLocationReturnServicesLessThan1MileAwayAsGuest(): void
    {
        // > 1 mile
        $event1 = OrganisationEvent::factory()->create([
            'is_virtual' => false,
            'location_id' => function () {
                return Location::factory()->create([
                    'lat' => 51.469954129107016, 'lon' => -0.3973609967291171,
                ])->id;
            },
        ]);

        // < 1 mile
        $event2 = OrganisationEvent::factory()->create([
            'is_virtual' => false,
            'location_id' => function () {
                return Location::factory()->create([
                    'lat' => 51.46813624630186, 'lon' => -0.38543053111827796,
                ])->id;
            },
        ]);

        // > 1 mile
        $event3 = OrganisationEvent::factory()->create([
            'is_virtual' => false,
            'location_id' => function () {
                return Location::factory()->create([
                    'lat' => 51.47591520714541, 'lon' => -0.41139431461981674,
                ])->id;
            },
        ]);

        sleep(1);

        $response = $this->json('POST', '/core/v1/search/events', [
            'order' => 'distance',
            'distance' => 1,
            'location' => [
                'lat' => 51.46843366223185,
                'lon' => -0.3674811879751439,
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $event2->id]);
        $response->assertJsonMissing(['id' => $event1->id]);
        $response->assertJsonMissing(['id' => $event3->id]);
    }

    /**
     * @test
     */
    public function searchEventsOrderByRelevanceWithLocationReturnEventsLessThan5MilesAwayAsGuest(): void
    {
        $event1 = OrganisationEvent::factory()->create([
            'is_virtual' => false,
            'location_id' => function () {
                return Location::factory()->create([
                    'lat' => 0,
                    'lon' => 0,
                ])->id;
            },
        ]);

        $event2 = OrganisationEvent::factory()->create([
            'title' => 'Test Name',
            'is_virtual' => false,
            'location_id' => function () {
                return Location::factory()->create([
                    'lat' => 45.001,
                    'lon' => 90.001,
                ])->id;
            },
        ]);

        $event3 = OrganisationEvent::factory()->create([
            'is_virtual' => false,
            'location_id' => function () {
                return Location::factory()->create([
                    'lat' => 45,
                    'lon' => 90,
                ])->id;
            },
            'organisation_id' => function () {
                return Organisation::factory()->create(['name' => 'Test Name'])->id;
            },
        ]);

        $event4 = OrganisationEvent::factory()->create([
            'is_virtual' => false,
            'location_id' => function () {
                return Location::factory()->create([
                    'lat' => 90,
                    'lon' => 180,
                ])->id;
            },
        ]);

        sleep(1);

        $response = $this->json('POST', '/core/v1/search/events', [
            'query' => 'Test Name',
            'order' => 'relevance',
            'location' => [
                'lat' => 45,
                'lon' => 90,
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['id' => $event2->id]);
        $response->assertJsonFragment(['id' => $event3->id]);
        $response->assertJsonMissing(['id' => $event1->id]);
        $response->assertJsonMissing(['id' => $event4->id]);
    }

    /**
     * @test
     */
    public function searchEventsOrderByRelevanceWithLocationReturnEventsLessThan1MileAwayAsGuest(): void
    {
        // Not relevant > 1 mile
        $event1 = OrganisationEvent::factory()->create([
            'is_virtual' => false,
            'location_id' => function () {
                return Location::factory()->create([
                    'lat' => 51.469954129107016,
                    'lon' => -0.3973609967291171,
                ])->id;
            },
        ]);

        // Relevant < 1 mile
        $event2 = OrganisationEvent::factory()->create([
            'title' => 'Event number 2',
            'intro' => 'Test name',
            'is_virtual' => false,
            'location_id' => function () {
                return Location::factory()->create([
                    'lat' => 51.46813624630186,
                    'lon' => -0.38543053111827796,
                ])->id;
            },
        ]);

        // Relevant < 1 mile
        $event3 = OrganisationEvent::factory()->create([
            'title' => 'Test name',
            'intro' => 'Event number 3',
            'is_virtual' => false,
            'location_id' => function () {
                return Location::factory()->create([
                    'lat' => 51.46933926508632,
                    'lon' => -0.3745729484111921,
                ])->id;
            },
        ]);

        // Relevant > 1 mile
        $event4 = OrganisationEvent::factory()->create([
            'title' => 'Test name',
            'is_virtual' => false,
            'location_id' => function () {
                return Location::factory()->create([
                    'lat' => 51.46741441979822,
                    'lon' => -0.40152378521657234,
                ])->id;
            },
        ]);

        sleep(5);

        $response = $this->json('POST', '/core/v1/search/events', [
            'query' => 'Test name',
            'order' => 'relevance',
            'distance' => 1,
            'location' => [
                'lat' => 51.46843366223185,
                'lon' => -0.3674811879751439,
            ],
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment(['id' => $event2->id]);
        $response->assertJsonFragment(['id' => $event3->id]);
        $response->assertJsonMissing(['id' => $event1->id]);
        $response->assertJsonMissing(['id' => $event4->id]);
    }

    /**
     * @test
     */
    public function searchEventsOrderByStartDate(): void
    {
        $event1 = OrganisationEvent::factory()->create([
            'title' => 'Testing Dates',
            'start_date' => $this->faker->dateTimeBetween('+1 day', '+2 days'),
            'end_date' => $this->faker->dateTimeBetween('+2 days', '+3 days'),
        ]);

        $event2 = OrganisationEvent::factory()->create([
            'title' => 'Testing Dates',
            'start_date' => $this->faker->dateTimeBetween('+4 days', '+5 days'),
            'end_date' => $this->faker->dateTimeBetween('+6 days', '+7 days'),
        ]);

        $event3 = OrganisationEvent::factory()->create([
            'title' => 'Testing Dates',
            'start_date' => $this->faker->dateTimeBetween('+8 days', '+9 days'),
            'end_date' => $this->faker->dateTimeBetween('+10 days', '+11 days'),
        ]);

        sleep(1);

        $response = $this->json('POST', '/core/v1/search/events', [
            'query' => 'Testing',
            'order' => 'start_date',
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $event1->id]);
        $response->assertJsonFragment(['id' => $event2->id]);
        $response->assertJsonFragment(['id' => $event3->id]);

        $data = $this->getResponseContent($response)['data'];
        $this->assertEquals(3, count($data));
        $this->assertEquals($event1->id, $data[0]['id']);
        $this->assertEquals($event2->id, $data[1]['id']);
        $this->assertEquals($event3->id, $data[2]['id']);
    }

    /**
     * @test
     */
    public function searchEventsMoreTaxonomiesInACategoryCollectionAreMoreRelevantAsGuest(): void
    {
        // Create 3 taxonomies
        $taxonomy1 = Taxonomy::category()->children()->create([
            'slug' => 'red',
            'name' => 'Red',
            'order' => 1,
            'depth' => 1,
        ]);
        $taxonomy2 = Taxonomy::category()->children()->create([
            'slug' => 'blue',
            'name' => 'Blue',
            'order' => 2,
            'depth' => 1,
        ]);
        $taxonomy3 = Taxonomy::category()->children()->create([
            'slug' => 'green',
            'name' => 'Green',
            'order' => 3,
            'depth' => 1,
        ]);

        // Create a collection
        $collection = Collection::create([
            'type' => Collection::TYPE_ORGANISATION_EVENT,
            'slug' => 'self-help',
            'name' => 'Self Help',
            'meta' => [],
            'order' => 1,
        ]);

        // Link the taxonomies to the collection
        $collection->collectionTaxonomies()->create(['taxonomy_id' => $taxonomy1->id]);
        $collection->collectionTaxonomies()->create(['taxonomy_id' => $taxonomy2->id]);
        $collection->collectionTaxonomies()->create(['taxonomy_id' => $taxonomy3->id]);

        // Create 3 events
        $event1 = OrganisationEvent::factory()->create([
            'title' => 'Gold Co.',
        ]);
        $event2 = OrganisationEvent::factory()->create([
            'title' => 'Silver Co.',
        ]);
        $event3 = OrganisationEvent::factory()->create([
            'title' => 'Bronze Co.',
        ]);

        // Link the events to 1, 2 and 3 taxonomies respectively.
        $event1->organisationEventTaxonomies()->create(['taxonomy_id' => $taxonomy1->id]);
        $event1->organisationEventTaxonomies()->create(['taxonomy_id' => $taxonomy2->id]);
        $event1->organisationEventTaxonomies()->create(['taxonomy_id' => $taxonomy3->id]);
        $event1->save(); // Update the Elasticsearch index.

        $event2->organisationEventTaxonomies()->create(['taxonomy_id' => $taxonomy1->id]);
        $event2->organisationEventTaxonomies()->create(['taxonomy_id' => $taxonomy2->id]);
        $event2->save(); // Update the Elasticsearch index.

        $event3->organisationEventTaxonomies()->create(['taxonomy_id' => $taxonomy1->id]);
        $event3->save(); // Update the Elasticsearch index.

        sleep(1);

        // Assert that when searching by collection, the events with more taxonomies are ranked higher.
        $response = $this->json('POST', '/core/v1/search/events', [
            'order' => 'relevance',
            'category' => $collection->slug,
        ]);

        $response->assertStatus(Response::HTTP_OK);

        $response->assertJsonCount(3, 'data');

        $content = $this->getResponseContent($response)['data'];
        $this->assertEquals($event1->id, $content[0]['id']);
        $this->assertEquals($event2->id, $content[1]['id']);
        $this->assertEquals($event3->id, $content[2]['id']);
    }

    /**
     * @test
     */
    public function searchEventsReturnsPaginatedResultSetAsGuest(): void
    {
        $events = OrganisationEvent::factory()->count(30)->create([
            'title' => 'Testing Page',
        ]);

        sleep(1);

        $response = $this->json('POST', '/core/v1/search/events', [
            'query' => 'Testing',
            'page' => 1,
            'per_page' => 20,
        ]);

        $response->assertStatus(Response::HTTP_OK);

        $response->assertJsonStructure([
            'data' => [],
            'links' => [],
            'meta' => [
                'current_page',
                'from',
                'last_page',
                'path',
                'per_page',
                'to',
                'total',
            ],

        ]);
    }
}
