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
            $table->unique(['service_id', 'location_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('service_locations', function (Blueprint $table) {
            $table->dropUnique(['service_id', 'location_id']);
        });
    }
};
