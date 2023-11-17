<?php

namespace Tests\Unit\Rules;

use App\Rules\VideoEmbed;
use Tests\TestCase;

class VideoEmbedTest extends TestCase
{
    /**
     * @test
     */
    public function it_passes_video_hosting_urls(): void
    {
        $badUrls = [
            'https://youtube.com',
            'https://badmovie.com',
        ];

        $goodUrls = [
            'https://www.youtube.com',
            'https://vimeo.com',
            'https://player.vimeo.com',
        ];

        $videoEmbedRule = new VideoEmbed();

        foreach ($badUrls as $badUrl) {
            $passes = true;
            $videoEmbedRule->validate('video_embed', $badUrl, function () use (&$passes) {
                $passes = false;
            });
            $this->assertFalse($passes);
        }

        foreach ($goodUrls as $goodUrl) {
            $passes = true;
            $videoEmbedRule->validate('video_embed', $goodUrl, function () use (&$passes) {
                $passes = false;
            });
            $this->assertTrue($passes);
        }
    }
}
