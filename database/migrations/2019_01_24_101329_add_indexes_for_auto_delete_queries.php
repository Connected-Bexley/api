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
        Schema::table('referrals', function (Blueprint $table) {
            $table->index('updated_at');
        });

        Schema::table('audits', function (Blueprint $table) {
            $table->index('updated_at');
        });

        Schema::table('page_feedbacks', function (Blueprint $table) {
            $table->index('updated_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('referrals', function (Blueprint $table) {
            $table->dropIndex(['updated_at']);
        });

        Schema::table('audits', function (Blueprint $table) {
            $table->dropIndex(['updated_at']);
        });

        Schema::table('page_feedbacks', function (Blueprint $table) {
            $table->dropIndex(['updated_at']);
        });
    }
};
