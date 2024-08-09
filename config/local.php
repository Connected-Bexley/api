<?php

use Illuminate\Support\Str;

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
     * The URI for the frontend app.
     */
    'frontend_uri' => env('FRONTEND_URI', ''),

    /*
     * The URI for the Terms and Conditions page.
     */
    'tandc_uri' => env('TANDC_URI', Str::finish(env('FRONTEND_URI', ''), '/') . 'terms-and-conditions'),

    /*
     * The URI for the Privacy page.
     */
    'privacy_uri' => env('PRIVACY_URI', Str::finish(env('FRONTEND_URI', ''), '/') . 'privacy-policy'),

    /*
     * The URI for the Accessibility page.
     */
    'accessibility_uri' => env('ACCESSIBILITY_URI', Str::finish(env('FRONTEND_URI', ''), '/') . 'accessibility_statement'),

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

    /**
     * Organisation description character limit.
     */
    'organisation_description_max_chars' => env('ORG_DESCRIPTION_MAX_CHARS', 3000),

    /**
     * Service description character limit.
     */
    'service_description_max_chars' => env('SERVICE_DESCRIPTION_MAX_CHARS', 10000),

    /**
     * Useful Info description character limit.
     */
    'useful_info_description_max_chars' => env('USEFUL_INFO_DESCRIPTION_MAX_CHARS', 1000),

    /**
     * Organisation Event description character limit.
     */
    'event_description_max_chars' => env('EVENT_DESCRIPTION_MAX_CHARS', 10000),

    /**
     * Page copy character limit.
     */
    'page_copy_max_chars' => env('PAGE_COPY_MAX_CHARS', 60000),

    /**
     * Max number of gallery images per service.
     */
    'max_gallery_images' => env('SERVICE_MAX_GALLERY_IMAGES', 5),
];
