<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        DB::table('settings')
            ->where('key', '=', 'cms')
            ->update([
                'value' => DB::raw(
                    <<<'EOT'
JSON_INSERT(
    `settings`.`value`,
    '$.frontend.home.banners',
    JSON_ARRAY(
        JSON_OBJECT(
            "title", null,
            "content", null,
            "button_text", null,
            "button_url", null
        )
    )
)
EOT
                ),
            ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('settings')
            ->where('key', '=', 'cms')
            ->update([
                'value' => DB::raw(
                    <<<'EOT'
JSON_REMOVE(
    `settings`.`value`,
    '$.frontend.home.banners'
)
EOT
                ),
            ]);
    }
};
