<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $uncleanPhoneNumbers = DB::table('services')
            ->whereRaw('id not in (select id from services where contact_phone REGEXP ?)', ['^(0[0-9]{10})$'])
            ->pluck('contact_phone', 'id');

        $uncleanPhoneNumbers->each(function ($phone, $id) {
            $cleanPhone = preg_replace('/[^0-9]/', '', $phone);

            DB::table('services')
                ->where('id', $id)
                ->update(['contact_phone' => $cleanPhone]);
        });

        $uncleanUpdateRequests = DB::table('update_requests')
            ->where('updateable_type', 'services')
            ->whereNotNull('data->contact_phone')
            ->pluck('data', 'id');

        $uncleanUpdateRequests->each(function ($data, $id) {
            $cleanPhone = preg_replace('/[^0-9]/', '', json_decode($data)->contact_phone);

            DB::table('update_requests')
                ->where('id', $id)
                ->update(['data->contact_phone' => $cleanPhone]);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        //
    }
};
