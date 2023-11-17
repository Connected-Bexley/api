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
        Schema::create('useful_infos', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuidKeyColumn('service_id', 'services');
            $table->string('title');
            $table->text('description');
            $table->unsignedInteger('order');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('useful_infos');
    }
};
