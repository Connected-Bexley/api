<?php

namespace App\Console\Commands\Ck;

use App\Models\CollectionTaxonomy;
use App\Models\ServiceTaxonomy;
use App\Models\Taxonomy;
use App\Models\UpdateRequest;
use Carbon\Carbon;
use GuzzleHttp\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class ImportTaxonomiesCommand extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ck:import-taxonomies
                            {url : The url of the taxonomies .csv file}
                            {--refresh : Delete all current taxonomies}
                            {--dry-run : Parse the import but do not commit the changes}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Imports a .csv file of Taxonomies';

    /**
     * Rows which have failed to import.
     *
     * @var array
     */
    protected $failedRows = [];

    /**
     * Unique Slug Generator.
     *
     * @var App\Generators\UniqueSlugGenerator
     */
    protected $slugGenerator;

    /**
     * Create a new command instance.
     */
    public function __construct()
    {
        parent::__construct();

        $this->slugGenerator = resolve(\App\Generators\UniqueSlugGenerator::class);
    }

    /**
     * Execute the console command.
     *
     * @throws \Exception
     * @return mixed
     */
    public function handle()
    {
        $csvUrl = $this->argument('url');
        $refresh = $this->option('refresh');
        $dryrun = $this->option('dry-run');

        if ($dryrun) {
            $this->warn('Dry Run, no data will be committed, delete option ignored');
        }

        $this->line('Fetching ' . $csvUrl);

        $records = $this->fetchTaxonomyRecords($csvUrl);

        if (is_array($records) && count($records)) {
            $this->info('Spreadsheet uploaded');

            if ($refresh) {
                $this->warn($dryrun ? 'Current Taxonomies will be preserved' : 'All current Taxonomies will be deleted');
            }

            $importCount = $this->importTaxonomyRecords($records, $refresh, $dryrun);

            if (count($this->failedRows)) {
                $this->warn('Unable to import all records. Failed records:');
                $this->table(['ID', 'Name', 'Parent ID'], $this->failedRows);
            } else {
                $this->info('All records imported. Total records imported: ' . $importCount);
            }
        } else {
            $this->info('Spreadsheet could not be uploaded');
        }

        if ($dryrun) {
            $this->warn('Dry Run complete');
        }
    }

    /**
     * Delete all current taxonomies.
     */
    public function deleteAllTaxonomies()
    {
        $taxonomyIds = $this->getDescendantTaxonomyIds([Taxonomy::category()->id]);
        DB::table((new ServiceTaxonomy())->getTable())->whereIn('taxonomy_id', $taxonomyIds)->delete();
        DB::table((new CollectionTaxonomy())->getTable())->whereIn('taxonomy_id', $taxonomyIds)->delete();
        DB::table((new UpdateRequest())->getTable())->whereIn(
            'updateable_type',
            [
                UpdateRequest::EXISTING_TYPE_SERVICE,
                UpdateRequest::NEW_TYPE_ORGANISATION_SIGN_UP_FORM,
            ]
        )->delete();

        Schema::disableForeignKeyConstraints();
        DB::table((new Taxonomy())->getTable())->whereIn('id', $taxonomyIds)->delete();
        Schema::enableForeignKeyConstraints();
    }

    /**
     * Get all the Caregory Taxonomy IDs.
     *
     * @param array $rootId
     * @param array $taxonomyIds
     * @param mixed $rootIds
     * @return array
     */
    public function getDescendantTaxonomyIds($rootIds, $taxonomyIds = []): array
    {
        $childIds = DB::table((new Taxonomy())->getTable())->whereIn('parent_id', $rootIds)->pluck('id');

        $taxonomyIds = array_merge($taxonomyIds, array_diff($childIds->all(), $taxonomyIds));

        if (count($childIds)) {
            $childTaxonomyIds = $this->getDescendantTaxonomyIds($childIds, $taxonomyIds);
            $taxonomyIds = array_merge($taxonomyIds, array_diff($childTaxonomyIds, $taxonomyIds));
        }

        return $taxonomyIds;
    }

    /**
     * Get the Taxonomy records to import.
     *
     * @param string $csvUrl
     * @return array || Null
     */
    public function fetchTaxonomyRecords(string $csvUrl)
    {
        $client = new Client();
        try {
            $response = $client->get($csvUrl);
            if (200 === $response->getStatusCode() && $response->getBody()->isReadable()) {
                return csv_to_array($response->getBody()->getContents());
            }
        } catch (\Exception $e) {
            Log::error($e->getMessage());
            if ($this->output) {
                $this->error('Error Fetching Taxonomy Records:');
                $this->error($e->getMessage());
            }
        }
    }

    /**
     * Import the Taxonomy records into the database.
     *
     * @param array $taxonomyRecords
     * @param bool $refresh
     * @param bool $dryrun
     * @return bool|int
     */
    public function importTaxonomyRecords(array $taxonomyRecords, bool $refresh, bool $dryrun)
    {
        if (App::environment() != 'testing') {
            $this->info('Starting transaction');
            DB::beginTransaction();
        }

        if (!is_uuid($taxonomyRecords[0][0])) {
            array_shift($taxonomyRecords);
        }

        $taxonomyImports = $this->prepareImports($taxonomyRecords, $refresh);

        if (count($this->failedRows) && App::environment() != 'testing') {
            $this->info('Rolling back transaction');
            DB::rollBack();

            return false;
        }

        $taxonomyImports = $this->mapTaxonomyDepth($taxonomyImports);

        if ($refresh && !$dryrun) {
            $this->deleteAllTaxonomies();
        }

        DB::table((new Taxonomy())->getTable())->insert($taxonomyImports);

        if (!$dryrun && App::environment() != 'testing') {
            $this->info('Commiting transaction');
            DB::commit();
        }

        return count($taxonomyImports);
    }

    /**
     * Sanity check the records before converting them to format for import.
     *
     * @param array $records
     * @param bool $refresh
     * @return array
     */
    public function prepareImports(array $records, bool $refresh): array
    {
        $parentIds = array_map(function ($record) {
            return $record[2] ?? null;
        }, $records);

        /**
         * Non-UUID cells or incorrect relationships cannot be imported so the import will fail.
         */
        foreach ($records as $record) {
            $failedRow = null;
            if (!is_uuid($record[0]) || (!empty($record[2]) && !in_array($record[2], $parentIds))) {
                $failedRow = $record;
                $failedRow[] = 'ID or parent ID invalid';
            }
            if (!$refresh && DB::table((new Taxonomy())->getTable())->where('id', $record[0])->exists()) {
                $failedRow = $failedRow ?? $record;
                $failedRow[] = 'Taxonomy exists';
            }
            if ($failedRow) {
                $this->failedRows[] = $failedRow;
            }
        }

        if (count($this->failedRows)) {
            return [];
        }

        $imports = $this->mapToIdKeys($records);

        return $imports;
    }

    /**
     * Convert the flat array to a collection of associative array with UUID keys.
     *
     * @param array $records
     * @return array
     */
    public function mapToIdKeys(array $records): array
    {
        return collect($records)->mapWithKeys(function ($record) {
            $modelData = [
                'id' => $record[0],
                'name' => $record[1],
                'parent_id' => $record[2] ?: Taxonomy::category()->id,
                'order' => 0,
                'depth' => 1,
                'created_at' => Carbon::now()->toDateTimeString(),
                'updated_at' => Carbon::now()->toDateTimeString(),
            ];

            if (Schema::hasColumn('taxonomies', 'slug')) {
                $modelData['slug'] = $this->slugGenerator->generate($record[1], 'taxonomies');
            }

            return [
                $record[0] => $modelData,
            ];
        })->all();
    }

    /**
     * Calculate the depth of each Taxonomy record.
     *
     * @param array $records
     * @return array
     */
    public function mapTaxonomyDepth(array $records): array
    {
        $rootRecords = array_filter($records, function ($record) {
            return $record['parent_id'] == Taxonomy::category()->id;
        });

        return $this->calculateTaxonomyDepth(array_keys($rootRecords), $records);
    }

    /**
     * Walk through the levels of child records and record the depth.
     *
     * @param array $parentIds
     * @param array $records
     * @param int $depth
     * @return array
     */
    public function calculateTaxonomyDepth($parentIds, &$records, $depth = 1): array
    {
        $newParentIds = [];
        $depth++;
        foreach ($records as $id => &$record) {
            // Is this a direct child node?
            if (in_array($record['parent_id'], $parentIds)) {
                // Set the depth
                $record['depth'] = $depth;
                // Add to the array of parent nodes to pass through to the next depth
                if (!in_array($id, $newParentIds)) {
                    $newParentIds[] = $id;
                }
            }
        }
        if (count($newParentIds)) {
            $this->calculateTaxonomyDepth($newParentIds, $records, $depth);
        }

        return $records;
    }
}
