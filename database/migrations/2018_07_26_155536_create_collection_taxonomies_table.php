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
        Schema::create('collection_taxonomies', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuidKeyColumn('collection_id', 'collections');
            $table->foreignUuidKeyColumn('taxonomy_id', 'taxonomies');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('collection_taxonomies');
    }
};
