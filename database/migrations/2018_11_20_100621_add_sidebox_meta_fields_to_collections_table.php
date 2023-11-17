<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('collections')->update([
            'meta' => DB::raw('JSON_SET(`meta`, "$.sidebox_title", NULL, "$.sidebox_content", NULL)'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('collections')->update([
            'meta' => DB::raw('JSON_REMOVE(`meta`, "$.sidebox_title", "$.sidebox_content")'),
        ]);
    }
};
