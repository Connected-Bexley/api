<?php

use App\Models\Location;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->boolean('has_accessible_toilet')->after('has_induction_loop');
        });

        Schema::table('locations', function (Blueprint $table) {
            DB::table((new Location())->getTable())
                ->update(['has_accessible_toilet' => false]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('locations', function (Blueprint $table) {
            $table->dropColumn('has_accessible_toilet');
        });
    }
};
