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
        Schema::table('update_requests', function (Blueprint $table) {
            $table->index('created_at');
            $table->index('approved_at');
            $table->index('deleted_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('update_requests', function (Blueprint $table) {
            $table->dropIndex(['created_at']);
            $table->dropIndex(['approved_at']);
            $table->dropIndex(['deleted_at']);
        });
    }
};
