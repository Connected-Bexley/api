<?php

use App\BatchImport\OpenActiveTaxonomyImporter;
use App\Models\ServiceTaxonomy;
use App\Models\Taxonomy;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        $categoryId = Taxonomy::category()->id;
        $openActiveTaxonomyId = uuid();
        $nowDateTimeString = Carbon::now()->toDateTimeString();

        // Create LGA Standards Taxonomy as child of Category
        DB::table((new Taxonomy())->getTable())->insert(
            [
                'id' => $openActiveTaxonomyId,
                'parent_id' => $categoryId,
                'name' => 'OpenActive',
                'order' => 1,
                'depth' => 1,
                'created_at' => $nowDateTimeString,
                'updated_at' => $nowDateTimeString,
            ]
        );

        $openActiveData = json_decode(Storage::disk('local')->get('/open-active/activity-list.jsonld'), true);

        $openActiveImporter = new OpenActiveTaxonomyImporter();

        $openActiveCategory = $openActiveImporter->getOpenActiveCategory();

        $taxonomyImports = $openActiveImporter->mapOpenActiveTaxonomyImport($openActiveCategory, $openActiveData['concept']);

        $openActiveImporter->importTaxonomies($openActiveCategory, $taxonomyImports);

        Taxonomy::category()->updateDepth();
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        $taxonomyTable = (new Taxonomy())->getTable();
        $categoryId = Taxonomy::category()->id;
        $openActiveId = DB::table($taxonomyTable)
            ->where('parent_id', $categoryId)
            ->where('name', 'OpenActive Taxonomy')
            ->value('id');

        $openActiveTaxonomyIds = $this->getDescendantTaxonomyIds(
            DB::table($taxonomyTable)->where('parent_id', $openActiveId)->pluck('id')
        );

        DB::table((new ServiceTaxonomy())->getTable())
            ->whereIn('taxonomy_id', $openActiveTaxonomyIds)
            ->delete();

        DB::table((new ServiceTaxonomy())->getTable())
            ->where('taxonomy_id', $openActiveId)
            ->delete();

        DB::table($taxonomyTable)
            ->whereIn('id', $openActiveTaxonomyIds)
            ->delete();

        DB::table($taxonomyTable)
            ->where('id', $openActiveId)
            ->delete();

        Taxonomy::category()->updateDepth();
    }

    /**
     * Get all Open Active Taxonomy IDs.
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
};
