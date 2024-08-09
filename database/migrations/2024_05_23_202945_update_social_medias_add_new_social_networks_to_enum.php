<?php

use App\Models\SocialMedia;
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
        Schema::table('social_medias', function (Blueprint $table) {
            DB::statement(sprintf(
                "ALTER TABLE `%s` MODIFY COLUMN `%s` ENUM('%s')",
                $table->getTable(),
                'type',
                implode("','", [
                    SocialMedia::TYPE_FACEBOOK,
                    SocialMedia::TYPE_INSTAGRAM,
                    SocialMedia::TYPE_OTHER,
                    SocialMedia::TYPE_TIKTOK,
                    SocialMedia::TYPE_TWITTER,
                    SocialMedia::TYPE_SNAPCHAT,
                    SocialMedia::TYPE_YOUTUBE,
                ])
            ));
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('social_medias', function (Blueprint $table) {
            DB::statement(sprintf(
                "ALTER TABLE `%s` MODIFY COLUMN `%s` ENUM('%s')",
                $table->getTable(),
                'type',
                implode("','", [
                    SocialMedia::TYPE_FACEBOOK,
                    SocialMedia::TYPE_INSTAGRAM,
                    SocialMedia::TYPE_OTHER,
                    SocialMedia::TYPE_TWITTER,
                    SocialMedia::TYPE_YOUTUBE,
                ])
            ));
        });
    }
};
