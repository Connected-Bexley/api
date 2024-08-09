<?php

use App\Console\Commands\Ck\ReindexElasticsearchCommand;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Storage;

return new class() extends Migration {
    /**
     * Run the migrations.
     *
     * @throws Illuminate\Contracts\Filesystem\FileNotFoundException
     */
    public function up(): void
    {
        $thesaurus = Storage::disk('local')->get('elasticsearch/thesaurus.csv');
        Storage::cloud()->put('elasticsearch/thesaurus.csv', $thesaurus);

        Artisan::call(ReindexElasticsearchCommand::class);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Storage::cloud()->delete('elasticsearch/thesaurus.csv');

        Artisan::call(ReindexElasticsearchCommand::class);
    }
};
