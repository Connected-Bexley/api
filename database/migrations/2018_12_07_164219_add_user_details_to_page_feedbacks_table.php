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
        Schema::table('page_feedbacks', function (Blueprint $table) {
            $table->string('name')->nullable()->after('feedback');
            $table->string('email')->nullable()->after('name');
            $table->string('phone')->nullable()->after('email');
            $table->timestamp('consented_at')->nullable()->after('phone');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('page_feedbacks', function (Blueprint $table) {
            $table->dropColumn('name', 'email', 'phone', 'consented_at');
        });
    }
};
