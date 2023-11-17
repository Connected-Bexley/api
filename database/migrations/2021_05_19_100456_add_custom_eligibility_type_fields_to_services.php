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
        Schema::table('services', function (Blueprint $table) {
            $table->text('eligibility_age_group_custom')->nullable();
            $table->text('eligibility_disability_custom')->nullable();
            $table->text('eligibility_employment_custom')->nullable();
            $table->text('eligibility_gender_custom')->nullable();
            $table->text('eligibility_housing_custom')->nullable();
            $table->text('eligibility_income_custom')->nullable();
            $table->text('eligibility_language_custom')->nullable();
            $table->text('eligibility_ethnicity_custom')->nullable();
            $table->text('eligibility_other_custom')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('services', function (Blueprint $table) {
            $table->dropColumn([
                'eligibility_age_group_custom',
                'eligibility_disability_custom',
                'eligibility_employment_custom',
                'eligibility_gender_custom',
                'eligibility_housing_custom',
                'eligibility_income_custom',
                'eligibility_language_custom',
                'eligibility_ethnicity_custom',
                'eligibility_other_custom',
            ]);
        });
    }
};
