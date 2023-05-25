<?php

namespace Tests\Feature\Search;

use App\Models\Collection;
use App\Models\Service;
use App\Models\Taxonomy;
use Illuminate\Http\Response;
use Tests\TestCase;
use Tests\UsesElasticsearch;

class CollectionPersonasTest extends TestCase implements UsesElasticsearch
{
    /**
     * Setup the test environment.
     *
     * @return void
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

    public function test_guest_can_search()
    {
        $collectionCategory = Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'self-help',
            'name' => 'Self Help',
            'meta' => [],
            'order' => 1,
        ]);

        sleep(1);

        $response = $this->json('POST', '/core/v1/search/collections/personas', [
            'persona' => $collectionCategory->getAttribute('slug'),
        ]);

        $response->assertStatus(Response::HTTP_OK);
    }

    public function test_filter_by_personas_works()
    {
        $service1 = Service::factory()->create();
        $service2 = Service::factory()->create();
        $collection1 = Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'refugees',
            'name' => 'Refugees',
            'meta' => [],
            'order' => 1,
        ]);
        $collection2 = Collection::create([
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'homeless',
            'name' => 'Homeless',
            'meta' => [],
            'order' => 2,
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
        $collection1->collectionTaxonomies()->create(['taxonomy_id' => $taxonomy1->id]);
        $service1->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy1->id]);
        $service1->save();

        $collection2->collectionTaxonomies()->create(['taxonomy_id' => $taxonomy2->id]);
        $service2->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy2->id]);
        $service2->save();

        sleep(1);

        $response = $this->json('POST', '/core/v1/search', [
            'persona' => $collection1->slug,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $service1->id]);
        $response->assertJsonMissing(['id' => $service2->id]);

        $response = $this->json('POST', '/core/v1/search', [
            'persona' => implode(',', [$collection1->slug, $collection2->slug]),
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $service1->id]);
        $response->assertJsonFragment(['id' => $service2->id]);
    }

    public function test_services_with_more_taxonomies_in_a_persona_collection_are_more_relevant()
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
            'type' => Collection::TYPE_PERSONA,
            'slug' => 'self-help',
            'name' => 'Self Help',
            'meta' => [],
            'order' => 1,
        ]);

        // Link the taxonomies to the collection
        $collection->collectionTaxonomies()->create(['taxonomy_id' => $taxonomy1->id]);
        $collection->collectionTaxonomies()->create(['taxonomy_id' => $taxonomy2->id]);
        $collection->collectionTaxonomies()->create(['taxonomy_id' => $taxonomy3->id]);

        // Create 3 services
        $service1 = Service::factory()->create(['name' => 'Gold Co.']);
        $service2 = Service::factory()->create(['name' => 'Silver Co.']);
        $service3 = Service::factory()->create(['name' => 'Bronze Co.']);

        // Link the services to 1, 2 and 3 taxonomies respectively.
        $service1->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy1->id]);
        $service1->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy2->id]);
        $service1->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy3->id]);
        $service1->save(); // Update the Elasticsearch index.

        $service2->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy1->id]);
        $service2->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy2->id]);
        $service2->save(); // Update the Elasticsearch index.

        $service3->serviceTaxonomies()->create(['taxonomy_id' => $taxonomy1->id]);
        $service3->save(); // Update the Elasticsearch index.

        sleep(1);

        // Assert that when searching by collection, the services with more taxonomies are ranked higher.
        $response = $this->json('POST', '/core/v1/search/collections/personas', [
            'persona' => $collection->slug,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(3, 'data');

        $content = $this->getResponseContent($response)['data'];
        $this->assertEquals($service1->id, $content[0]['id']);
        $this->assertEquals($service2->id, $content[1]['id']);
        $this->assertEquals($service3->id, $content[2]['id']);
    }
}
