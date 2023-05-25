<?php

declare(strict_types=1);

use App\Search\ElasticSearch\Settings\PagesIndexSettings;
use ElasticMigrations\Facades\Index;
use ElasticMigrations\MigrationInterface;

final class CreatePageIndex implements MigrationInterface
{
    /**
     * The mapping for the fields.
     *
     * @var array
     */
    protected $mapping = [
        'properties' => [
            'id' => ['type' => 'keyword'],
            'enabled' => ['type' => 'boolean'],
            'title' => [
                'type' => 'text',
                'analyzer' => 'english_analyser',
                'search_analyzer' => 'english_analyser',
                'fields' => [
                    'keyword' => ['type' => 'keyword'],
                ],
            ],
            'content' => [
                'properties' => [
                    'introduction' => [
                        'properties' => [
                            'title' => [
                                'type' => 'text',
                                'analyzer' => 'english_analyser',
                                'search_analyzer' => 'english_analyser',
                            ],
                            'content' => [
                                'type' => 'text',
                                'analyzer' => 'english_analyser',
                                'search_analyzer' => 'english_analyser',
                            ],
                        ],
                    ],
                    'about' => [
                        'properties' => [
                            'title' => [
                                'type' => 'text',
                                'analyzer' => 'english_analyser',
                                'search_analyzer' => 'english_analyser',
                            ],
                            'content' => [
                                'type' => 'text',
                                'analyzer' => 'english_analyser',
                                'search_analyzer' => 'english_analyser',
                            ],
                        ],
                    ],
                    'info_pages' => [
                        'properties' => [
                            'title' => [
                                'type' => 'text',
                                'analyzer' => 'english_analyser',
                                'search_analyzer' => 'english_analyser',
                            ],
                            'content' => [
                                'type' => 'text',
                                'analyzer' => 'english_analyser',
                                'search_analyzer' => 'english_analyser',
                            ],
                        ],
                    ],
                    'collections' => [
                        'properties' => [
                            'title' => [
                                'type' => 'text',
                                'analyzer' => 'english_analyser',
                                'search_analyzer' => 'english_analyser',
                            ],
                            'content' => [
                                'type' => 'text',
                                'analyzer' => 'english_analyser',
                                'search_analyzer' => 'english_analyser',
                            ],
                        ],
                    ],
                ],
            ],
            'collection_categories' => [
                'type' => 'text',
                'analyzer' => 'english_analyser',
                'search_analyzer' => 'english_analyser',
            ],
            'collection_personas' => [
                'type' => 'text',
                'analyzer' => 'english_analyser',
                'search_analyzer' => 'english_analyser',
            ],
        ],
    ];

    /**
     * Run the migration.
     */
    public function up(): void
    {
        $settings = (new PagesIndexSettings())->getSettings();
        Index::dropIfExists('pages');
        Index::createRaw('pages', $this->mapping, $settings);
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Index::dropIfExists('pages');
    }
}
