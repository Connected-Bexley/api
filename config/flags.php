<?php
/*
|--------------------------------------------------------------------------
| Application Flags
|--------------------------------------------------------------------------
|
| Flags to control if optional app functionality is available or not
|
 */
return [
    /**
     * Flag to allow use the CQC location ID field on Services.
     */
    'cqc_location' => env('CQC_LOCATION', false),

    /**
     * Flag to allow tagging Services.
     */
    'service_tags' => env('SERVICE_TAGS', false),

    /**
     * Flag to allow the 'What we offer' fields.
     */
    'offerings' => env('SERVICE_OFFERINGS', true),
];
