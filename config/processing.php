<?php

return [

    /*
    |--------------------------------------------------------------------------
    | External document processing service
    |--------------------------------------------------------------------------
    |
    | Base URL must not include a trailing slash. Credentials must be set in
    | the environment — never commit real secrets to the repository.
    |
    */

    'base_url' => rtrim((string) env('PROCESSING_SERVICE_BASE_URL', 'http://127.0.0.1'), '/'),

    'username' => env('PROCESSING_SERVICE_USERNAME', 'api_user'),

    'password' => env('PROCESSING_SERVICE_PASSWORD'),

    'connect_timeout' => (float) env('PROCESSING_SERVICE_CONNECT_TIMEOUT', 60),

    'timeout' => (float) env('PROCESSING_SERVICE_TIMEOUT', 600),

    'read_timeout' => (float) env('PROCESSING_SERVICE_READ_TIMEOUT', 600),

    /*
    | When true, multi-file datasheet endpoints dispatch queue jobs and return
    | HTTP 202 with a batch id. Run `php artisan queue:work` in production.
    | Clients should poll GET /api/data-process/batch/{batchId} (and equivalents).
    */

    'use_queue' => filter_var(env('PROCESSING_USE_QUEUE', false), FILTER_VALIDATE_BOOLEAN),

    /*
    | Named path segments appended to base_url (leading slash optional).
    */

    'endpoints' => [
        'datasheet_process' => env('PROCESSING_ENDPOINT_DATASHEET', '/datasheet_process'),
        'free_datasheet_process' => env('PROCESSING_ENDPOINT_FREE_DATASHEET', '/free_datasheet_process'),
        'contract_automation' => env('PROCESSING_ENDPOINT_CONTRACT', '/contract_automation'),
        'werthenbach' => env('PROCESSING_ENDPOINT_WERTHENBACH', '/datasheet/werthenback'),
        'surfachem' => env('PROCESSING_ENDPOINT_SURFACHEM', '/datasheet/surfachem'),
        'verbund' => env('PROCESSING_ENDPOINT_VERBUND', '/datasheet/verbund'),
        'scheren' => env('PROCESSING_ENDPOINT_SCHEREN', '/datasheet/scheren'),
        'sennheiser' => env('PROCESSING_ENDPOINT_SENNHEISER', '/datasheet/sennheiser'),
        'sthamer_datasheet_process' => env('PROCESSING_ENDPOINT_STHAMER', '/sthamer/datasheet_process'),
    ],

];
