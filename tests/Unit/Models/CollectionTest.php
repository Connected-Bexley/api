<?php

namespace Tests\Unit\Models;

use Tests\TestCase;
use App\Models\Service;
use App\Models\Taxonomy;
use App\Models\Collection;
use Tests\UsesElasticsearch;
use App\Models\OrganisationEvent;
use Illuminate\Database\Eloquent\Collection as EloquentCollection;

class CollectionTest extends TestCase implements UsesElasticsearch
{
    /**
     * @test
     */
    public function updateCollectionTaxonomiesReIndexesRelatedServices()
    {
        $service1 = Service::factory()->create([
            'name' => 'Undeniable Giraffe',
        ]);
        $service2 = Service::factory()->create([
            'name' => 'Accomplished Possum',
        ]);
        $collection = Collection::create([
            'type' => Collection::TYPE_CATEGORY,
            'slug' => 'adventurous-animals',
            'name' => 'Adventurous Animals',
            'meta' => [],
            'order' => 1,
        ]);
        $taxonomy1 = Taxonomy::category()->children()->create([
            'slug' => 'test-taxonomy-1',
            'name' => 'Test Taxonomy 1',
            'order' => 1,
            'depth' => 1,
        ]);
        $taxonomy2 = Taxonomy::category()->children()->create([
            'slug' => 'test-taxonomy-2',
            'name' => 'Test Taxonomy 2',
            'order' => 2,
            'depth' => 1,
        ]);

        // Attach services to taxonomies
        $service1->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy1->id]);
        $service1->save();

        $service2->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy2->id]);
        $service2->save();

        /**
         * Search 1: service 1 and 2 should not be in the collection
         */
        $searchResponse = Service::search('Undeniable Giraffe')->raw();

        $this->assertEquals(1, $searchResponse->total());
        $this->assertEquals($service1->id, $searchResponse->hits()[0]->document()->content()['id']);
        $this->assertEquals([], $searchResponse->hits()[0]->document()->content()['collection_categories']);

        $searchResponse = Service::search('Accomplished Possum')->raw();

        $this->assertEquals(1, $searchResponse->total());
        $this->assertEquals($service2->id, $searchResponse->hits()[0]->document()->content()['id']);
        $this->assertEquals([], $searchResponse->hits()[0]->document()->content()['collection_categories']);

        // Sync taxonomy 1 to the collection
        $collection->syncCollectionTaxonomies((new EloquentCollection([$taxonomy1])));

        /**
         * Search 2: service 1 should be in the collection, service 2 should not
         */
        $searchResponse = Service::search('Undeniable Giraffe')->raw();

        $this->assertEquals(1, $searchResponse->total());
        $this->assertEquals($service1->id, $searchResponse->hits()[0]->document()->content()['id']);
        $this->assertEquals(['Adventurous Animals'], $searchResponse->hits()[0]->document()->content()['collection_categories']);

        $searchResponse = Service::search('Accomplished Possum')->raw();

        $this->assertEquals(1, $searchResponse->total());
        $this->assertEquals($service2->id, $searchResponse->hits()[0]->document()->content()['id']);
        $this->assertEquals([], $searchResponse->hits()[0]->document()->content()['collection_categories']);

        // Sync taxonomy 2 to the collection
        $collection->syncCollectionTaxonomies((new EloquentCollection([$taxonomy2])));

        $this->assertCount(1, $collection->taxonomies);

        /**
         * Search 3: service 2 should be in the collection, service 1 should not
         */
        $searchResponse = Service::search('Undeniable Giraffe')->raw();

        $this->assertEquals(1, $searchResponse->total());
        $this->assertEquals($service1->id, $searchResponse->hits()[0]->document()->content()['id']);
        $this->assertEquals([], $searchResponse->hits()[0]->document()->content()['collection_categories']);

        $searchResponse = Service::search('Accomplished Possum')->raw();

        $this->assertEquals(1, $searchResponse->total());
        $this->assertEquals($service2->id, $searchResponse->hits()[0]->document()->content()['id']);
        $this->assertEquals(['Adventurous Animals'], $searchResponse->hits()[0]->document()->content()['collection_categories']);
    }

    /**
     * @test
     */
    public function updateCollectionTaxonomiesReIndexesRelatedOrganisationEvents()
    {
        $event1 = OrganisationEvent::factory()->create([
            'title' => 'Undeniable Giraffe',
        ]);
        $event2 = OrganisationEvent::factory()->create([
            'title' => 'Accomplished Possum',
        ]);
        $collection = Collection::create([
            'type' => Collection::TYPE_ORGANISATION_EVENT,
            'slug' => 'adventurous-animals',
            'name' => 'Adventurous Animals',
            'meta' => [],
            'order' => 1,
        ]);
        $taxonomy1 = Taxonomy::category()->children()->create([
            'slug' => 'test-taxonomy-1',
            'name' => 'Test Taxonomy 1',
            'order' => 1,
            'depth' => 1,
        ]);
        $taxonomy2 = Taxonomy::category()->children()->create([
            'slug' => 'test-taxonomy-2',
            'name' => 'Test Taxonomy 2',
            'order' => 2,
            'depth' => 1,
        ]);

        // Attach events to taxonomies
        $event1->organisationEventTaxonomies()->create(['taxonomy_id' => $taxonomy1->id]);
        $event1->save();

        $event2->organisationEventTaxonomies()->create(['taxonomy_id' => $taxonomy2->id]);
        $event2->save();

        /**
         * Search 1: event 1 and 2 should not be in the collection
         */
        $searchResponse = OrganisationEvent::search('Undeniable Giraffe')->raw();

        $this->assertEquals(1, $searchResponse->total());
        $this->assertEquals($event1->id, $searchResponse->hits()[0]->document()->content()['id']);
        $this->assertEquals([], $searchResponse->hits()[0]->document()->content()['collection_categories']);

        $searchResponse = OrganisationEvent::search('Accomplished Possum')->raw();

        $this->assertEquals(1, $searchResponse->total());
        $this->assertEquals($event2->id, $searchResponse->hits()[0]->document()->content()['id']);
        $this->assertEquals([], $searchResponse->hits()[0]->document()->content()['collection_categories']);

        // Sync taxonomy 1 to the collection
        $collection->syncCollectionTaxonomies((new EloquentCollection([$taxonomy1])));

        /**
         * Search 2: event 1 should be in the collection, event 2 should not
         */
        $searchResponse = OrganisationEvent::search('Undeniable Giraffe')->raw();

        $this->assertEquals(1, $searchResponse->total());
        $this->assertEquals($event1->id, $searchResponse->hits()[0]->document()->content()['id']);
        $this->assertEquals(['Adventurous Animals'], $searchResponse->hits()[0]->document()->content()['collection_categories']);

        $searchResponse = OrganisationEvent::search('Accomplished Possum')->raw();

        $this->assertEquals(1, $searchResponse->total());
        $this->assertEquals($event2->id, $searchResponse->hits()[0]->document()->content()['id']);
        $this->assertEquals([], $searchResponse->hits()[0]->document()->content()['collection_categories']);

        // Sync taxonomy 2 to the collection
        $collection->syncCollectionTaxonomies((new EloquentCollection([$taxonomy2])));

        $this->assertCount(1, $collection->taxonomies);

        /**
         * Search 3: event 2 should be in the collection, event 1 should not
         */
        $searchResponse = OrganisationEvent::search('Undeniable Giraffe')->raw();

        $this->assertEquals(1, $searchResponse->total());
        $this->assertEquals($event1->id, $searchResponse->hits()[0]->document()->content()['id']);
        $this->assertEquals([], $searchResponse->hits()[0]->document()->content()['collection_categories']);

        $searchResponse = OrganisationEvent::search('Accomplished Possum')->raw();

        $this->assertEquals(1, $searchResponse->total());
        $this->assertEquals($event2->id, $searchResponse->hits()[0]->document()->content()['id']);
        $this->assertEquals(['Adventurous Animals'], $searchResponse->hits()[0]->document()->content()['collection_categories']);
    }
}
