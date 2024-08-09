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
        $stopWords = Storage::disk('local')->get('elasticsearch/stop-words.csv');
        Storage::cloud()->put('elasticsearch/stop-words.csv', $stopWords);

        Artisan::call(ReindexElasticsearchCommand::class);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Storage::cloud()->delete('elasticsearch/stop-words.csv');

        Artisan::call(ReindexElasticsearchCommand::class);
    }
};
