<?php

use App\Models\Taxonomy;
use Carbon\Carbon;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = Carbon::now()->toDateTimeString();

        $housingEligibilitytaxonomy = [
            'id' => uuid(),
            'name' => 'Housing',
            'parent_id' => Taxonomy::serviceEligibility()->id,
            'order' => Taxonomy::serviceEligibility()->children->max('order') + 1,
            'depth' => 1,
            'created_at' => $now,
            'updated_at' => $now,
        ];

        $housingOptionNames = [
            'Council or housing association tenant',
            'Homeless',
            'Home Owner',
            'Private Renter',
            'Lease Holder',
        ];

        Schema::table('taxonomies', function (Blueprint $table) use ($now, $housingEligibilitytaxonomy, $housingOptionNames) {
            $housingOptionTaxonomies = [];
            $order = 0;
            foreach ($housingOptionNames as $housingOptionName) {
                $order++;
                $housingOptionTaxonomies[] = [
                    'id' => uuid(),
                    'name' => $housingOptionName,
                    'parent_id' => $housingEligibilitytaxonomy['id'],
                    'order' => $order,
                    'depth' => 2,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            DB::statement('SET FOREIGN_KEY_CHECKS=0;');
            DB::table('taxonomies')->insert($housingEligibilitytaxonomy);
            DB::table('taxonomies')->insert($housingOptionTaxonomies);
            Taxonomy::serviceEligibility()->updateDepth();
            DB::statement('SET FOREIGN_KEY_CHECKS=1;');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('taxonomies', function (Blueprint $table) {
            $housingEligibilitytaxonomy = Taxonomy::serviceEligibility()->children()->where('name', 'Housing')->firstOrFail();
            Taxonomy::destroy($housingEligibilitytaxonomy->children->pluck('id'));
            $housingEligibilitytaxonomy->delete();
        });
    }
};
