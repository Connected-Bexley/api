<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('taxonomies', function (Blueprint $table) {
            $table->string('slug')->after('parent_id')->nullable()->unique();
        });

        DB::table('taxonomies')
            ->orderBy('id')
            ->chunk(200, function (Collection $taxonomies): void {
                foreach ($taxonomies as $taxonomy) {
                    $index = 0;
                    do {
                        $slug = Str::slug($taxonomy->name);
                        $slug .= $index === 0 ? '' : "-{$index}";

                        $slugAlreadyUsed = DB::table('taxonomies')
                            ->where('slug', '=', $slug)
                            ->exists();

                        if ($slugAlreadyUsed) {
                            $index++;

                            continue;
                        }

                        DB::table('taxonomies')
                            ->where('id', '=', $taxonomy->id)
                            ->update(['slug' => $slug]);

                        continue 2;
                    } while (true);
                }
            });

        Schema::table('taxonomies', function (Blueprint $table) {
            $table->string('slug')->nullable(false)->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('taxonomies', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
