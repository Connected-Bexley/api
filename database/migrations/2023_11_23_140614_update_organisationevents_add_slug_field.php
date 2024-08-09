<?php

use App\Models\OrganisationEvent;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('organisation_events', function (Blueprint $table) {
            $table->string('slug')->after('id');
        });

        OrganisationEvent::query()->chunk(200, function (Collection $events) {
            $events->each(function (OrganisationEvent $event) {
                $iteration = 0;
                do {
                    $slug = $iteration === 0
                    ? Str::slug($event->title)
                    : Str::slug($event->title) . '-' . $iteration;
                    $iteration++;
                } while (OrganisationEvent::query()->where('slug', $slug)->exists());

                $event->update(['slug' => $slug]);
            });
        });

        Schema::table('organisation_events', function (Blueprint $table) {
            $table->unique('slug');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('organisation_events', function (Blueprint $table) {
            $table->dropUnique(['slug']);
            $table->dropColumn('slug');
        });
    }
};
