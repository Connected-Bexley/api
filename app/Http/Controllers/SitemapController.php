<?php

namespace App\Http\Controllers;

use App\Models\Collection;
use App\Models\Organisation;
use App\Models\Page;
use App\Models\Service;
use DateTime;
use DOMDocument;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\Cache;

class SitemapController extends Controller
{
    /**
     * The DomDocument that holds the xml sitemap.
     *
     * @var DOMDocument
     */
    protected $sitemap;

    public function __invoke(): Response
    {
        $sitemap = Cache::remember('sitemap', (60 * 60), function () {
            $this->sitemap = new DOMDocument('1.0', 'UTF-8');
            $urlset = $this->sitemap->createElementNS('http://www.sitemaps.org/schemas/sitemap/0.9', 'urlset');
            $this->sitemap->appendChild($urlset);

            $this->createStaticPageNodes();

            $this->createServiceNodes();

            $this->createOrganisationNodes();

            $this->createCategoryNodes();

            $this->createPersonaNodes();

            $this->createPageNodes();

            return $this->sitemap->saveXML();
        });

        return response($sitemap, 200);
    }

    /**
     * Create the static page routes.
     *
     * @return array [DomElement]
     */
    public function createStaticPageNodes()
    {
        $pageSlugs = [
            '',
            'about',
            'contact',
            'get-involved',
            'privacy-policy',
            'terms-and-conditions',
        ];

        $pages = collect($pageSlugs)
            ->map(function ($slug) {
                return [
                    'path' => $slug,
                    'updated' => date(DateTime::W3C, strtotime('2 weeks ago')),
                ];
            })
            ->all();

        $this->addUrlNodes($pages);
    }

    /**
     * Create the service routes.
     *
     * @return array [DomElement]
     */
    public function createServiceNodes()
    {
        $services = Service::where('status', '=', Service::STATUS_ACTIVE)
            ->pluck('updated_at', 'slug')
            ->map(function ($updatedAt, $slug) {
                return [
                    'path' => "services/$slug",
                    'updated' => date(DateTime::W3C, strtotime($updatedAt)),
                ];
            })
            ->all();

        $this->addUrlNodes($services);
    }

    /**
     * Create the organisation routes.
     *
     * @return array [DomElement]
     */
    public function createOrganisationNodes()
    {
        $organisations = Organisation::pluck('updated_at', 'slug')
            ->map(function ($updatedAt, $slug) {
                return [
                    'path' => "organisations/$slug",
                    'updated' => date(DateTime::W3C, strtotime($updatedAt)),
                ];
            })
            ->all();

        $this->addUrlNodes($organisations);
    }

    /**
     * Create the Collection category routes.
     *
     * @return array [DomElement]
     */
    public function createCategoryNodes()
    {
        $categories = Collection::categories()
            ->pluck('updated_at', 'id')
            ->map(function ($updatedAt, $id) {
                return [
                    'path' => "results?category=$id",
                    'updated' => date(DateTime::W3C, strtotime($updatedAt)),
                ];
            })
            ->all();

        $this->addUrlNodes($categories);
    }

    /**
     * Create the Collection persona routes.
     *
     * @return array [DomElement]
     */
    public function createPersonaNodes()
    {
        $personas = Collection::personas()
            ->pluck('updated_at', 'id')
            ->map(function ($updatedAt, $id) {
                return [
                    'path' => "results?persona=$id",
                    'updated' => date(DateTime::W3C, strtotime($updatedAt)),
                ];
            })
            ->all();

        $this->addUrlNodes($personas);
    }

    /**
     * Create the information page routes.
     *
     * @return array [DomElement]
     */
    public function createPageNodes()
    {
        $pages = Page::where('enabled', '=', Page::ENABLED)
            ->pluck('updated_at', 'slug')
            ->map(function ($updatedAt, $slug) {
                return [
                    'path' => "pages/$slug",
                    'updated' => date(DateTime::W3C, strtotime($updatedAt)),
                ];
            })
            ->all();

        $this->addUrlNodes($pages);
    }

    /**
     * Create the static page routes.
     *
     * @param mixed $routes
     * @param mixed|null $lastMod
     * @param mixed $changeFreq
     * @param mixed $priority
     * @return array [DomElement]
     */
    public function addUrlNodes($routes, $lastMod = null, $changeFreq = 'monthly', $priority = '0.5')
    {
        $lastMod = $lastMod ?: date(DateTime::W3C);

        $urlset = $this->sitemap->getElementsByTagNameNS('http://www.sitemaps.org/schemas/sitemap/0.9', 'urlset')->item(0);

        foreach ($routes as $route) {
            $url = $this->sitemap->createElement('url');
            $url->appendChild($this->sitemap->createElement('loc', $this->frontendUrl($route['path'])));
            $url->appendChild($this->sitemap->createElement('lastmod', $route['updated'] ?? $lastMod));
            $url->appendChild($this->sitemap->createElement('changefreq', $changeFreq));
            $url->appendChild($this->sitemap->createElement('priority', $priority));
            $urlset->appendChild($url);
        }
    }

    /**
     * Create a frontend url for the given environment.
     *
     * @return string
     */
    public function frontendUrl(string $path = '')
    {
        return str_replace('://api.', '://', url($path));
    }
}
