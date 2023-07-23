<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Third Party Services
    |--------------------------------------------------------------------------
    |
    | This file is for storing the credentials for third party services such
    | as Mailgun, Postmark, AWS and more. This file provides the de facto
    | location for this type of information, allowing packages to have
    | a conventional file to locate the various service credentials.
    |
    */

    'pdftotext' => [
        'path' => env('PDF_TO_TEXT_PATH', '/usr/bin/pdftotext')
    ],

    'google' => [
        'search_key' => env('GOOGLE_SEARCH_KEY', null),
        'search_cx' => env('GOOGLE_SEARCH_CX', null),
    ],

    'browserless' => [
        'key' => env('BROWSERLESS_KEY')
    ],

    'scenario' => [
        'key' => env('SCENARIO_API_KEY'),
        'secret' => env('SCENARIO_API_SECRET'),
    ],

    'mailgun' => [
        'domain' => env('MAILGUN_DOMAIN'),
        'secret' => env('MAILGUN_SECRET'),
        'endpoint' => env('MAILGUN_ENDPOINT', 'api.mailgun.net'),
        'scheme' => 'https',
    ],

    'postmark' => [
        'token' => env('POSTMARK_TOKEN'),
    ],

    'ses' => [
        'key' => env('AWS_ACCESS_KEY_ID'),
        'secret' => env('AWS_SECRET_ACCESS_KEY'),
        'region' => env('AWS_DEFAULT_REGION', 'us-east-1'),
    ],

];
