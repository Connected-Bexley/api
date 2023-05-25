<?php

declare(strict_types=1);

use App\Search\ElasticSearch\Settings\EventsIndexSettings;
use ElasticMigrations\Facades\Index;
use ElasticMigrations\MigrationInterface;

final class CreateOrganisationEventIndex implements MigrationInterface
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
            'intro' => [
                'type' => 'text',
                'analyzer' => 'english_analyser',
                'search_analyzer' => 'english_analyser',
            ],
            'description' => [
                'type' => 'text',
                'analyzer' => 'english_analyser',
                'search_analyzer' => 'english_analyser',
            ],
            'start_date' => [
                'type' => 'date',
                'format' => 'strict_date_hour_minute_second',
            ],
            'end_date' => [
                'type' => 'date',
                'format' => 'strict_date_hour_minute_second',
            ],
            'is_free' => ['type' => 'boolean'],
            'is_virtual' => ['type' => 'boolean'],
            'has_wheelchair_access' => ['type' => 'boolean'],
            'has_induction_loop' => ['type' => 'boolean'],
            'organisation_name' => [
                'type' => 'text',
                'analyzer' => 'english_analyser',
                'search_analyzer' => 'english_analyser',
                'fields' => [
                    'keyword' => ['type' => 'keyword'],
                ],
            ],
            'taxonomy_categories' => [
                'type' => 'text',
                'analyzer' => 'english_analyser',
                'search_analyzer' => 'english_analyser',
                'fields' => [
                    'keyword' => ['type' => 'keyword'],
                ],
            ],
            'collection_categories' => ['type' => 'keyword'],
            'event_location' => [
                'type' => 'nested',
                'properties' => [
                    'id' => ['type' => 'keyword'],
                    'location' => ['type' => 'geo_point'],
                    'has_wheelchair_access' => ['type' => 'boolean'],
                    'has_induction_loop' => ['type' => 'boolean'],
                    'has_accessible_toilet' => ['type' => 'boolean'],
                ],
            ],
        ],
    ];

    /**
     * Run the migration.
     */
    public function up(): void
    {
        $settings = (new EventsIndexSettings())->getSettings();
        Index::dropIfExists('events');
        Index::createRaw('events', $this->mapping, $settings);
    }

    /**
     * Reverse the migration.
     */
    public function down(): void
    {
        Index::dropIfExists('events');
    }
}
