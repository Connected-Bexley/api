<?php

use App\Models\Role;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Date;
use Illuminate\Support\Facades\DB;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $now = Date::now();

        DB::table('roles')->insert([
            'id' => uuid(),
            'name' => Role::NAME_CONTENT_ADMIN,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        DB::table('roles')->where(Role::NAME_CONTENT_ADMIN)->delete();
    }
};
