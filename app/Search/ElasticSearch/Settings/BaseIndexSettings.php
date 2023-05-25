<?php

namespace App\Search\ElasticSearch\Settings;

use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;

class BaseIndexSettings
{
    /**
     * @return array
     */
    public function getSettings(): array
    {
        return [
            'analysis' => [
                'analyzer' => [
                    'english_analyser' => [
                        'type' => 'custom',
                        'tokenizer' => 'standard',
                        'filter' => [
                            'lowercase',
                            'synonym',
                            'stop',
                            'stopwords',
                        ],
                    ],
                ],
                'filter' => [
                    'synonym' => [
                        'type' => 'synonym',
                        'synonyms' => $this->getThesaurus(),
                    ],
                    'stopwords' => [
                        'type' => 'stop',
                        'stopwords' => $this->getStopWords(),
                    ],
                ],
            ],
        ];
    }

    /**
     * @return array
     */
    protected function getStopWords(): array
    {
        if (!$content = Storage::cloud()->get('elasticsearch/stop-words.csv')) {
            return [];
        }

        $stopWords = csv_to_array($content);

        $stopWords = collect($stopWords)->map(function (array $stopWord) {
            return mb_strtolower($stopWord[0]);
        });

        return $stopWords->toArray();
    }

    /**
     * @return array
     */
    protected function getThesaurus(): array
    {
        if (!$content = Storage::cloud()->get('elasticsearch/thesaurus.csv')) {
            return [];
        }

        $thesaurus = csv_to_array($content);

        $thesaurus = collect($thesaurus)->map(function (array $synonyms) {
            return collect($synonyms);
        });

        $thesaurus = $thesaurus
            ->map(function (Collection $synonyms) {
                // Parse the synonyms.
                $parsedSynonyms = $synonyms
                    ->reject(function (string $term) {
                        // Filter out any empty strings.
                        return $term === '';
                    })
                    ->map(function (string $term) {
                        // Convert each term to lower case.
                        return mb_strtolower($term);
                    });

                // Check if the synonyms are using simple contraction.
                $usingSimpleContraction = $parsedSynonyms->filter(function (string $term) {
                    return preg_match('/\s/', $term);
                })->isNotEmpty();

                // If using simple contraction, then format accordingly.
                if ($usingSimpleContraction) {
                    $lastTerm = $parsedSynonyms->pop();
                    $allWords = $parsedSynonyms->implode(',');

                    return "$allWords => $lastTerm";
                }

                // Otherwise, format as normal.
                return $parsedSynonyms->implode(',');
            });

        return $thesaurus->toArray();
    }
}
