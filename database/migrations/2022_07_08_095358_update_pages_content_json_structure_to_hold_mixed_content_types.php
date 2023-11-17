<?php

use App\Models\Page;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class() extends Migration {
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('pages', function (Blueprint $table) {
            Page::chunk(50, function ($pages) {
                foreach ($pages as $page) {
                    $content = $page->content;

                    foreach ($content as $key => $value) {
                        $copyArr = $value['copy'];
                        $value['content'] = [];
                        foreach ($copyArr as $copy) {
                            $value['content'][] = [
                                'type' => 'copy',
                                'value' => $copy,
                            ];
                        }
                        unset($value['copy']);
                        $content[$key] = $value;
                    }

                    $page->content = $content;
                    $page->save();
                }
            });
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
