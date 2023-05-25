<?php

namespace App\BatchImport;

use App\Models\Taxonomy;
use GuzzleHttp\Client;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class OpenActiveTaxonomyImporter
{
    /**
     * The URL for the Open Active taxonomy in jsonld format.
     *
     * @var string
     */
    protected $openActiveDirectoryUrl = 'https://openactive.io/activity-list/activity-list.jsonld';

    /**
     * Unique Slug Generator.
     *
     * @var App\Generators\UniqueSlugGenerator
     */
    protected $slugGenerator;

    public function __construct()
    {
        $this->slugGenerator = resolve(\App\Generators\UniqueSlugGenerator::class);
    }

    /**
     * Fetch the Open Active taxonomy data and store it as a collection.
     *
     * @param mixed $openActiveDirectoryUrl
     * @return array
     */
    public function fetchTaxonomies($openActiveDirectoryUrl)
    {
        $client = new Client();
        try {
            $response = $client->get($openActiveDirectoryUrl);
            if (200 === $response->getStatusCode() && $response->getBody()->isReadable()) {
                $data = json_decode((string)$response->getBody(), true);

                return $data['concept'];
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());

            throw $e;
        }
    }

    /**
     * Get the root Open Active category.
     *
     * @return \App\Models\Taxonomy
     */
    public function getOpenActiveCategory()
    {
        return Taxonomy::category()
            ->children()
            ->where('name', 'OpenActive')
            ->firstOrFail();
    }

    public function runImport($directoryUrl = null)
    {
        $openActiveDirectoryUrl = $directoryUrl ?? $this->openActiveDirectoryUrl;
        // Get the root Open Active category
        $openActiveCategory = $this->getOpenActiveCategory();

        // Fetch the remote Open Active data
        $openActiveTaxonomies = $this->fetchTaxonomies($openActiveDirectoryUrl);

        // Correctly format the remote data ready for import
        $taxonomyImports = $this->mapOpenActiveTaxonomyImport($openActiveCategory, $openActiveTaxonomies);

        $this->importTaxonomies($openActiveCategory, $taxonomyImports);
    }

    /**
     * Import the formatted taxonomies into the database.
     *
     * @param \App\Models\Taxonomy $rootTaxonomy
     * @param array $taxonomyImports
     */
    public function importTaxonomies(Taxonomy $openActiveCategory, array $taxonomyImports)
    {
        // Import the data
        DB::transaction(function () use ($openActiveCategory, $taxonomyImports) {
            Schema::disableForeignKeyConstraints();

            DB::table((new Taxonomy())->getTable())->insert($taxonomyImports);

            Schema::enableForeignKeyConstraints();

            $openActiveCategory->refresh();

            $openActiveCategory->updateDepth();
        });
    }

    /**
     * Map the imported data into an import friendly format.
     *
     * @param \App\Models\Taxonomy $rootTaxonomy
     * @param array openActiveTaxonomyData
     * @param mixed $openActiveTaxonomyData
     * @return array
     */
    public function mapOpenActiveTaxonomyImport(Taxonomy $rootTaxonomy, $openActiveTaxonomyData)
    {
        $nowDateTimeString = Carbon::now()->toDateTimeString();

        return array_map(function ($taxonomy) use ($rootTaxonomy, $nowDateTimeString) {
            return array_merge(
                $this->mapOpenActiveTaxonomyToTaxonomyModelSchema($rootTaxonomy, $taxonomy),
                [
                    'created_at' => $nowDateTimeString,
                    'updated_at' => $nowDateTimeString,
                ]
            );
        }, $openActiveTaxonomyData);
    }

    /**
     * Return the uuid component from an Open Active directory url.
     *
     * @param string $identifierUrl
     * @return string
     */
    private function parseIdentifier(string $identifierUrl)
    {
        return mb_substr($identifierUrl, mb_strpos($identifierUrl, '#') + 1);
    }

    /**
     * Convert Open Active taxonomies into Taxonomy model data.
     *
     * @param \App\Models\Taxonomy $rootTaxonomy
     * @param array openActiveTaxonomyData
     * @return array
     */
    private function mapOpenActiveTaxonomyToTaxonomyModelSchema(Taxonomy $rootTaxonomy, array $taxonomyData)
    {
        $modelData = [
            'id' => $taxonomyData['identifier'],
            'name' => $taxonomyData['prefLabel'],
            'parent_id' => array_key_exists('broader', $taxonomyData) ? $this->parseIdentifier($taxonomyData['broader'][0]) : $rootTaxonomy->id,
            'order' => 0,
            'depth' => 2,
        ];

        if (Schema::hasColumn('taxonomies', 'slug')) {
            $modelData['slug'] = $this->slugGenerator->generate($taxonomyData['prefLabel'], 'taxonomies');
        }

        return $modelData;
    }
}
