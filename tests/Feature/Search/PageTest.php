<?php

namespace Tests\Feature\Search;

use App\Models\Collection;
use App\Models\Page;
use Illuminate\Http\Response;
use Tests\TestCase;
use Tests\UsesElasticsearch;

class PageTest extends TestCase implements UsesElasticsearch
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
        $response = $this->json('POST', '/core/v1/search/pages', [
            'query' => 'test',
            'page' => 1,
            'per_page' => 20,
        ]);

        $response->assertStatus(Response::HTTP_OK);
    }

    public function test_query_matches_page_title(): void
    {
        $page = Page::factory()->create([
            'title' => 'This is a test',
        ]);

        sleep(1);

        $response = $this->json('POST', '/core/v1/search/pages', [
            'query' => $page->title,
            'page' => 1,
            'per_page' => 20,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $page->id,
        ]);
    }

    public function test_query_matches_page_content(): void
    {
        $page = Page::factory()->landingPage()->create();
        sleep(1);

        $response = $this->json('POST', '/core/v1/search/pages', [
            'query' => $page->content['introduction']['content'][0]['value'],
            'page' => 1,
            'per_page' => 20,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $page->id,
        ]);

        $page = Page::factory()->landingPage()->create([
            'content' => [
                'introduction' => [
                    'content' => [
                        [
                            'type' => 'copy',
                            'value' => $this->faker->realText(),
                        ],
                        [
                            'type' => 'cta',
                            'title' => $this->faker->sentence(),
                            'description' => $this->faker->realText(),
                            'url' => $this->faker->url(),
                            'buttonText' => $this->faker->words(3, true),
                        ],
                    ],
                ],
            ],
        ]);
        sleep(1);

        $response = $this->json('POST', '/core/v1/search/pages', [
            'query' => $page->content['introduction']['content'][1]['title'],
            'page' => 1,
            'per_page' => 20,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $page->id,
        ]);

        $response = $this->json('POST', '/core/v1/search/pages', [
            'query' => $page->content['introduction']['content'][1]['description'],
            'page' => 1,
            'per_page' => 20,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment([
            'id' => $page->id,
        ]);
    }

    public function test_query_matches_single_word_from_page_content(): void
    {
        $page = Page::factory()->create([
            'content' => [
                'introduction' => [
                    'content' => [
                        [
                            'type' => 'copy',
                            'value' => 'This is a page that helps to homeless find temporary housing.',
                        ],
                    ],
                ],
            ],
        ]);
        sleep(1);

        $response = $this->json('POST', '/core/v1/search/pages', [
            'query' => 'homeless',
            'page' => 1,
            'per_page' => 20,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $page->id]);
    }

    public function test_query_matches_multiple_words_from_page_content(): void
    {
        $page = Page::factory()->create([
            'content' => [
                'introduction' => [
                    'content' => [
                        [
                            'type' => 'copy',
                            'value' => 'This is a page that helps to homeless find temporary housing.',
                        ],
                    ],
                ],
            ],
        ]);
        sleep(1);

        $response = $this->json('POST', '/core/v1/search/pages', [
            'query' => 'temporary housing',
            'page' => 1,
            'per_page' => 20,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $page->id]);
    }

    public function test_query_ranks_page_title_above_page_content(): void
    {
        $page1 = Page::factory()->create(['title' => 'This is a test']);
        $page2 = Page::factory()->create([
            'content' => [
                'introduction' => [
                    'content' => [
                        [
                            'type' => 'copy',
                            'value' => 'This is a test',
                        ],
                    ],
                ],
            ],
        ]);
        sleep(1);

        $response = $this->json('POST', '/core/v1/search/pages', [
            'query' => 'test this',
            'page' => 1,
            'per_page' => 20,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment([
            'id' => $page1->id,
        ]);

        $response->assertJsonFragment([
            'id' => $page2->id,
        ]);

        $data = $this->getResponseContent($response)['data'];
        $this->assertEquals($page1->id, $data[0]['id']);
        $this->assertEquals($page2->id, $data[1]['id']);
    }

    public function test_query_matches_collection_name(): void
    {
        $page1 = Page::factory()->create();
        $page1->updateCollections([Collection::factory()->create([
            'name' => 'This is a test',
        ])->id]);

        $page2 = Page::factory()->create();
        $page2->updateCollections([Collection::factory()->create([
            'name' => 'Should not match',
        ])->id]);

        sleep(1);

        $response = $this->json('POST', '/core/v1/search/pages', [
            'query' => 'This is a test',
            'page' => 1,
            'per_page' => 20,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['id' => $page1->id]);
        $response->assertJsonMissing(['id' => $page2->id]);

        // Fuzzy
        $response = $this->json('POST', '/core/v1/search/pages', [
            'query' => 'those tests',
            'page' => 1,
            'per_page' => 20,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['id' => $page1->id]);
        $response->assertJsonMissing(['id' => $page2->id]);
    }

    public function test_query_matches_partial_collection_name(): void
    {
        $page1 = Page::factory()->create();
        $page1->updateCollections([
            Collection::factory()->create([
                'name' => 'Testword Anotherphrase',
            ])->id,
        ]);

        $page2 = Page::factory()->create();
        $page2->updateCollections([
            Collection::factory()->create([
                'name' => 'Should not match',
            ])->id,
        ]);

        sleep(1);

        $response = $this->json('POST', '/core/v1/search/pages', [
            'query' => 'Anotherphrase',
            'page' => 1,
            'per_page' => 20,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['id' => $page1->id]);
        $response->assertJsonMissing(['id' => $page2->id]);

        // Fuzzy
        $response = $this->json('POST', '/core/v1/search/pages', [
            'query' => 'Anotherfrase',
            'page' => 1,
            'per_page' => 20,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(1, 'data');
        $response->assertJsonFragment(['id' => $page1->id]);
        $response->assertJsonMissing(['id' => $page2->id]);
    }

    public function test_query_ranks_perfect_match_above_fuzzy_match(): void
    {
        $page1 = Page::factory()->create(['title' => 'This is a test']);
        $page2 = Page::factory()->create(['title' => 'Those are tests']);
        sleep(1);

        $response = $this->json('POST', '/core/v1/search/pages', [
            'query' => 'This is a test',
            'page' => 1,
            'per_page' => 20,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonCount(2, 'data');
        $response->assertJsonFragment([
            'id' => $page1->id,
        ]);

        $response->assertJsonFragment([
            'id' => $page2->id,
        ]);

        $data = $this->getResponseContent($response)['data'];
        $this->assertEquals($page1->id, $data[0]['id']);
        $this->assertEquals($page2->id, $data[1]['id']);
    }

    public function test_only_enabled_pages_returned(): void
    {
        $activePage = Page::factory()->create([
            'title' => 'Testing Page',
            'enabled' => Page::ENABLED,
        ]);
        $inactivePage = Page::factory()->create([
            'title' => 'Testing Page',
            'enabled' => Page::DISABLED,
        ]);
        sleep(1);

        $response = $this->json('POST', '/core/v1/search/pages', [
            'query' => 'Testing Page',
            'page' => 1,
            'per_page' => 20,
        ]);

        $response->assertStatus(Response::HTTP_OK);
        $response->assertJsonFragment(['id' => $activePage->id]);
        $response->assertJsonMissing(['id' => $inactivePage->id]);
    }

    public function test_query_returns_paginated_result_set(): void
    {
        $pages = Page::factory()->count(30)->create([
            'title' => 'Testing Page',
        ]);
        sleep(1);

        $response = $this->json('POST', '/core/v1/search/pages', [
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
