<?php

namespace Tests\Feature;

use App\Models\Collection;
use App\Models\Organisation;
use App\Models\Page;
use App\Models\Service;
use DOMDocument;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SitemapTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        if (! Storage::disk('local')->exists('test-data/sitemap.xsd')) {
            Storage::disk('local')->put('test-data/sitemap.xsd', file_get_contents('http://www.sitemaps.org/schemas/sitemap/0.9/sitemap.xsd'));
        }
    }

    /**
     * Create a frontend url for the given environment
     *
     * @param  string  $path
     * @return string
     **/
    public function frontendUrl($path = '')
    {
        return str_replace('://api.', '://', url($path));
    }

    /**
     * @test
     */
    public function getSitemapAsGuest200()
    {
        $response = $this->json('GET', '/sitemap');

        $response->assertStatus(Response::HTTP_OK);
    }

    /**
     * @test
     */
    public function getSitemapReturnsXml200()
    {
        $response = $this->get('/sitemap');

        $response->assertStatus(Response::HTTP_OK);

        $xml = new DOMDocument('1.0', 'UTF-8');

        $this->assertTrue($xml->loadXML($response->content()));
    }

    /**
     * @test
     */
    public function getSitemapReturnsAValidSitemap200()
    {
        $response = $this->get('/sitemap');

        $response->assertStatus(Response::HTTP_OK);

        $xml = new DOMDocument('1.0', 'UTF-8');

        $xml->loadXML($response->content());

        $this->assertTrue($xml->schemaValidateSource(Storage::disk('local')->get('test-data/sitemap.xsd')));
    }

    /**
     * @test
     */
    public function getSitemapIncludesStaticPages200()
    {
        $pages = [
            'home' => false,
            'about' => false,
            'contact' => false,
            'get-involved' => false,
            'privacy-policy' => false,
            'terms-and-conditions' => false,
        ];
        $response = $this->get('/sitemap');

        $response->assertStatus(Response::HTTP_OK);

        $xml = new DOMDocument('1.0', 'UTF-8');

        $xml->loadXML($response->content());

        $locTags = $xml->getElementsByTagName('loc');

        foreach ($locTags as $tag) {
            foreach ($pages as $page => &$status) {
                $url = $page === 'home' ? $this->frontendUrl() : $this->frontendUrl($page);
                if ($url === $tag->textContent) {
                    $pages[$page] = true;
                }
            }
        }

        $this->assertNotContains(false, $pages);
    }

    /**
     * @test
     */
    public function getSitemapIncludesServices200()
    {
        /** @var \App\Models\Service $service */
        $service = Service::factory()->create();
        $included = false;

        $response = $this->get('/sitemap');

        $response->assertStatus(Response::HTTP_OK);

        $xml = new DOMDocument('1.0', 'UTF-8');

        $xml->loadXML($response->content());

        $locTags = $xml->getElementsByTagName('loc');

        foreach ($locTags as $tag) {
            if ($this->frontendUrl('services/'.$service->slug) === $tag->textContent) {
                $included = true;
            }
        }

        return $this->assertTrue($included);
    }

    /**
     * @test
     */
    public function getSitemapIncludesOrganisations200()
    {
        /** @var \App\Models\Organisation $organisation */
        $organisation = Organisation::factory()->create();
        $included = false;

        $response = $this->get('/sitemap');

        $response->assertStatus(Response::HTTP_OK);

        $xml = new DOMDocument('1.0', 'UTF-8');

        $xml->loadXML($response->content());

        $locTags = $xml->getElementsByTagName('loc');

        foreach ($locTags as $tag) {
            if ($this->frontendUrl('organisations/'.$organisation->slug) === $tag->textContent) {
                $included = true;
            }
        }

        return $this->assertTrue($included);
    }

    /**
     * @test
     */
    public function getSitemapIncludesCategories200()
    {
        /** @var \App\Models\Collection $collection */
        $collection = Collection::where('type', 'category')->latest()->first();
        $included = false;

        $response = $this->get('/sitemap');

        $response->assertStatus(Response::HTTP_OK);

        $xml = new DOMDocument('1.0', 'UTF-8');

        $xml->loadXML($response->content());

        $locTags = $xml->getElementsByTagName('loc');

        foreach ($locTags as $tag) {
            if ($this->frontendUrl('results?category='.$collection->id) === $tag->textContent) {
                $included = true;
            }
        }

        return $this->assertTrue($included);
    }

    /**
     * @test
     */
    public function getSitemapIncludesPersonas200()
    {
        /** @var \App\Models\Collection $collection */
        $collection = Collection::where('type', 'persona')->latest()->first();
        $included = false;

        $response = $this->get('/sitemap');

        $response->assertStatus(Response::HTTP_OK);

        $xml = new DOMDocument('1.0', 'UTF-8');

        $xml->loadXML($response->content());

        $locTags = $xml->getElementsByTagName('loc');

        foreach ($locTags as $tag) {
            if ($this->frontendUrl('results?persona='.$collection->id) === $tag->textContent) {
                $included = true;
            }
        }

        return $this->assertTrue($included);
    }

    /**
     * @test
     */
    public function getSitemapIncludesPages200()
    {
        /** @var \App\Models\Page $page */
        $page = Page::factory()->create();
        $included = false;

        $response = $this->get('/sitemap');

        $response->assertStatus(Response::HTTP_OK);

        $xml = new DOMDocument('1.0', 'UTF-8');

        $xml->loadXML($response->content());

        $locTags = $xml->getElementsByTagName('loc');

        foreach ($locTags as $tag) {
            if ($this->frontendUrl('pages/'.$page->slug) === $tag->textContent) {
                $included = true;
            }
        }

        return $this->assertTrue($included);
    }
}
