<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up()
    {
        Schema::table('services', function (Blueprint $table) {
            Schema::table('services', function (Blueprint $table) {
                $table->string('cqc_location_id')->nullable();
            });
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('services', function (Blueprint $table) {
            Schema::table('services', function (Blueprint $table) {
                $table->dropColumn('cqc_location_id');
            });
        });
    }
};
