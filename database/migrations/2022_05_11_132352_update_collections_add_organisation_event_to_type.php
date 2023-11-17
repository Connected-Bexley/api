<?php

use App\Models\Collection;
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
        Schema::table('collections', function (Blueprint $table) {
            DB::statement(sprintf(
                "ALTER TABLE `%s` MODIFY COLUMN `%s` ENUM('%s')",
                $table->getTable(),
                'type',
                implode("','", [Collection::TYPE_CATEGORY, Collection::TYPE_PERSONA, Collection::TYPE_ORGANISATION_EVENT])
            ));
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collections', function (Blueprint $table) {
            DB::statement(sprintf(
                "ALTER TABLE `%s` MODIFY COLUMN `%s` ENUM('%s')",
                $table->getTable(),
                'type',
                implode("','", [Collection::TYPE_CATEGORY, Collection::TYPE_PERSONA])
            ));
        });
    }
};
