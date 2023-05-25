<?php

use App\Models\ServiceTaxonomy;
use App\Models\Taxonomy;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        $categoryId = Taxonomy::category()->id;
        $lgaStandardsId = uuid();
        $nowDateTimeString = Carbon::now()->toDateTimeString();

        // Create LGA Standards Taxonomy as child of Category
        DB::table((new Taxonomy())->getTable())->insert(
            [
                'id' => $lgaStandardsId,
                'parent_id' => $categoryId,
                'name' => 'LGA Standards',
                'order' => 0,
                'depth' => 1,
                'created_at' => $nowDateTimeString,
                'updated_at' => $nowDateTimeString,
            ]
        );

        // Move all direct children of Category to LGA Standards
        DB::table((new Taxonomy())->getTable())
            ->where('parent_id', $categoryId)
            ->whereIn('name', ['Functions', 'Services'])
            ->update(['parent_id' => $lgaStandardsId]);

        Taxonomy::category()->updateDepth();

        // Get the Services related to the now children of LGA Standards
        $serviceIds = DB::table((new ServiceTaxonomy())->getTable())
            ->distinct()
            ->whereIn('taxonomy_id', function ($query) use ($lgaStandardsId) {
                $query->select('id')
                    ->from((new Taxonomy())->getTable())
                    ->where('parent_id', $lgaStandardsId);
            })->pluck('service_id');

        // Relate those Services to LGA Standards as well
        DB::table((new ServiceTaxonomy())->getTable())
            ->insert(array_map(function ($serviceId) use ($lgaStandardsId, $nowDateTimeString) {
                return [
                    'id' => uuid(),
                    'service_id' => $serviceId,
                    'taxonomy_id' => $lgaStandardsId,
                    'created_at' => $nowDateTimeString,
                    'updated_at' => $nowDateTimeString,
                ];
            }, $serviceIds->all()));
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        $categoryId = Taxonomy::category()->id;
        $lgaStandardsId = DB::table((new Taxonomy())->getTable())
            ->where('parent_id', $categoryId)
            ->where('name', 'LGA Standards')
            ->value('id');

        DB::table((new ServiceTaxonomy())->getTable())
            ->where('taxonomy_id', $lgaStandardsId)
            ->delete();

        DB::table((new Taxonomy())->getTable())
            ->where('parent_id', $lgaStandardsId)
            ->update(['parent_id' => $categoryId]);

        Taxonomy::category()->updateDepth();

        DB::table((new Taxonomy())->getTable())
            ->where('id', $lgaStandardsId)
            ->delete();
    }
};
