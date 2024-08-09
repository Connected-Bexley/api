<?php

use App\Models\Page;
use App\Models\Service;
use App\Models\UsefulInfo;
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
        $host = parse_url(config('app.url'), PHP_URL_HOST);
        $escHost = str_replace('.', '\.', $host);
        $uuidRegex = '|' . $escHost . '\/([0-9a-f]{8}-[0-9a-f]{4}-[0-5][0-9a-f]{3}-[089ab][0-9a-f]{3}-[0-9a-f]{12})|i';
        Page::chunk(50, function ($pages) use ($host, $uuidRegex) {
            foreach ($pages as $page) {
                if (preg_match_all($uuidRegex, print_r($page->content, true), $matches)) {
                    $content = $page->content;
                    $matchingPages = DB::table((new Page())->getTable())
                        ->whereIn('id', $matches[1])
                        ->pluck('slug', 'id');
                    foreach ($matchingPages as $id => $slug) {
                        foreach ($content as $label => $details) {
                            foreach ($details['copy'] as $i => $copy) {
                                $content[$label]['copy'][$i] = str_replace("$host/$id", "$host/pages/$slug", $copy);
                            }
                        }
                    }
                    $page->content = $content;
                    $page->save();
                }
            }
        });

        Service::chunk(50, function ($services) use ($host, $uuidRegex) {
            foreach ($services as $service) {
                if (preg_match_all($uuidRegex, $service->description, $matches)) {
                    $description = $service->description;
                    $matchingServices = DB::table((new Service())->getTable())
                        ->whereIn('id', $matches[1])
                        ->pluck('slug', 'id');
                    foreach ($matchingServices as $id => $slug) {
                        $description = str_replace("$host/$id", "$host/pages/$slug", $description);
                    }
                    $service->description = $description;
                    $service->save();
                }
            }
        });

        UsefulInfo::chunk(50, function ($usefulInfos) use ($host, $uuidRegex) {
            foreach ($usefulInfos as $usefulInfo) {
                if (preg_match_all($uuidRegex, $usefulInfo->description, $matches)) {
                    $description = $usefulInfo->description;
                    $matchingUsefulInfos = DB::table((new UsefulInfo())->getTable())
                        ->whereIn('id', $matches[1])
                        ->pluck('slug', 'id');
                    foreach ($matchingUsefulInfos as $id => $slug) {
                        $description = str_replace("$host/$id", "$host/pages/$slug", $description);
                    }
                    $usefulInfo->description = $description;
                    $usefulInfo->save();
                }
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            //
        });
    }
};
