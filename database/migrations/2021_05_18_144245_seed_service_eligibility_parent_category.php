<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->now = Date::now();
        $taxonomies = $this->loadServiceEligibilityTaxonomies();
        DB::statement('SET FOREIGN_KEY_CHECKS=0;');
        DB::table('taxonomies')->insert($taxonomies);
        DB::statement('SET FOREIGN_KEY_CHECKS=1;');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('taxonomies')->truncate();
    }

    /**
     * Load the Service Eligibility taxonomies into an array.
     *
     * @throws Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    protected function loadServiceEligibilityTaxonomies(): array
    {
        $fileContents = Storage::disk('local')->get('/service-eligibility/taxonomy.json');

        $taxonomies = json_decode($fileContents, true);

        return $taxonomies;
    }
};
