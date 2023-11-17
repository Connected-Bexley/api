<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->string('slug')->after('id');
        });

        Page::query()->chunk(200, function (Collection $pages) {
            $pages->each(function (Page $page) {
                $iteration = 0;
                do {
                    $slug = $iteration === 0
                    ? Str::slug($page->title)
                    : Str::slug($page->title) . '-' . $iteration;
                    $iteration++;
                } while (Page::query()->where('slug', $slug)->exists());

                $page->update(['slug' => $slug]);
            });
        });

        Schema::table('pages', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
