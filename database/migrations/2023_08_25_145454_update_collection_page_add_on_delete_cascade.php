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
        Schema::table('collection_page', function (Blueprint $table) {
            $table->dropForeign(['page_id']);
            $table->dropForeign(['collection_id']);
        });
        Schema::table('collection_page', function (Blueprint $table) {
            $table->foreign('page_id')->references('id')->on('pages')->onDelete('cascade');
            $table->foreign('collection_id')->references('id')->on('collections')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('collection_page', function (Blueprint $table) {
            $table->dropForeign(['page_id']);
            $table->dropForeign(['collection_id']);
        });
        Schema::table('collection_page', function (Blueprint $table) {
            $table->foreign('page_id')->references('id')->on('pages');
            $table->foreign('collection_id')->references('id')->on('collections');
        });
    }
};
