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
        Schema::create('referrals', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuidKeyColumn('service_id', 'services');
            $table->char('reference', 10)->unique();
            $table->enum('status', ['new', 'in_progress', 'completed', 'incompleted']);
            $table->text('name');
            $table->text('email')->nullable();
            $table->text('phone')->nullable();
            $table->text('other_contact')->nullable();
            $table->text('postcode_outward_code')->nullable();
            $table->text('comments')->nullable();
            $table->timestamp('referral_consented_at')->nullable();
            $table->timestamp('feedback_consented_at')->nullable();
            $table->text('referee_name')->nullable();
            $table->text('referee_email')->nullable();
            $table->text('referee_phone')->nullable();
            $table->nullableForeignUuidKeyColumn('organisation_taxonomy_id', 'taxonomies');
            $table->string('organisation')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('referrals');
    }
};
