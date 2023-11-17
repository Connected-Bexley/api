<?php

namespace Tests\Feature;

use App\Models\Tag;
use Carbon\CarbonImmutable;
use Illuminate\Http\Response;
use Tests\TestCase;

class TagsTest extends TestCase
{
    /**
     * @test
     */
    public function indexTagsAsGuest200(): void
    {
        $tags = Tag::factory()->count(5)->create();

        $response = $this->json('GET', '/core/v1/tags');

        $response->assertStatus(Response::HTTP_OK);

        $response->assertJsonStructure([
            'data' => [
                [
                    'id',
                    'slug',
                    'label',
                    'created_at',
                    'updated_at',
                ],
            ],
        ]);

        $response->assertJsonCount(5, 'data');

        $response->assertJsonFragment([
            'id' => $tags->get(0)->id,
            'slug' => $tags->get(0)->slug,
            'label' => $tags->get(0)->label,
            'created_at' => $tags->get(0)->created_at->format(CarbonImmutable::ISO8601),
            'updated_at' => $tags->get(0)->updated_at->format(CarbonImmutable::ISO8601),
        ]);
    }
}
