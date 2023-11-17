<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('service_locations', function (Blueprint $table) {
            $table->uuid('image_file_id')->nullable()->after('location_id');
            $table->foreign('image_file_id')->references('id')->on('files');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_locations', function (Blueprint $table) {
            $table->dropForeign(['image_file_id']);
            $table->dropColumn('image_file_id');
        });
    }
};
