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
        Schema::create('tags', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('slug');
            $table->string('label');
            $table->timestamps();
        });

        Schema::create('service_tag', function (Blueprint $table) {
            $table->foreignUuidKeyColumn('service_id', 'services');
            $table->foreignUuidKeyColumn('tag_id', 'tags');
            $table->unique(['service_id', 'tag_id']);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('service_tag');
        Schema::dropIfExists('tags');
    }
};
