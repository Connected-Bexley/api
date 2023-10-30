<?php

return [

    /*
     * The default number of results to show in paginated responses.
     */
    'pagination_results' => 25,

    /*
     * The maximum number of results that can be requested.
     */
    'max_pagination_results' => 100,

    /*
     * The contact details for the global admin team.
     */
    'global_admin' => [
        'email' => env('GLOBAL_ADMIN_EMAIL'),
    ],

    /*
     * The URI for the backend app.
     */
    'backend_uri' => env('BACKEND_URI', ''),

    /*
     * The number of working days a service must respond within.
     */
    'working_days_for_service_to_respond' => 10,

    /*
     * If one time password authentication should be enabled.
     */
    'otp_enabled' => env('OTP_ENABLED', true),

    /*
     * The distance (in miles) that the search results should limit up to.
     */
    'search_distance' => 5,

    /*
     * The dimensions to automatically generate resized images at.
     */
    'cached_image_dimensions' => [
        150,
        350,
    ],
    /**
     * The request api rate limit per minute per user / IP.
     */
    'api_rate_limit' => env('API_RATE_LIMIT', 300),
];
