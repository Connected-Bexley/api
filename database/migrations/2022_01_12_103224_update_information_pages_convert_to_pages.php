<?php

use function GuzzleHttp\json_decode;
use function GuzzleHttp\json_encode;
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
        $content = DB::table('information_pages')->pluck('content', 'id');

        Schema::rename('information_pages', 'pages');

        Schema::table('pages', function (Blueprint $table) {
            $table->dropColumn('content');
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->json('content');
        });

        foreach ($content as $id => $copy) {
            DB::table('pages')
                ->where('id', $id)
                ->update([
                    'content' => json_encode([
                        'introduction' => [
                            'label' => 'Page content',
                            'hint' => 'This is the largest content of the page. Use formatting to improve readability and impact.',
                            'copy' => [$copy],
                        ],
                    ]),
                ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $content = DB::table('pages')->pluck('content', 'id')->all();

        Schema::rename('pages', 'information_pages');

        Schema::table('information_pages', function (Blueprint $table) {
            $table->dropColumn('content');
        });

        Schema::table('information_pages', function (Blueprint $table) {
            $table->text('content');
        });

        foreach ($content as $id => $contentJson) {
            $copy = json_decode($contentJson);
            $copy = $copy['content']['introduction']['copy'][0] ?? null;
            DB::table('pages')
                ->where('id', $id)
                ->update($copy);
        }
    }
};
