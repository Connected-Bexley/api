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
        Schema::table('collection_taxonomies', function (Blueprint $table) {
            $table->unique(['collection_id', 'taxonomy_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down()
    {
        Schema::table('collection_taxonomies', function (Blueprint $table) {
            $table->dropUnique(['collection_id', 'taxonomy_id']);
        });
    }
};
