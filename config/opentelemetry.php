<?php

return [
    /*
    |--------------------------------------------------------------------------
    | OpenTelemetry Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains the configuration for OpenTelemetry integration
    | with your Laravel application. 
    |
    */

    // Tempo collector endpoint
    'tempo_endpoint' => env('TEMPO_ENDPOINT', 'http://tempo:4318/v1/traces'),

    // Service details
    'service' => [
        'name' => env('APP_NAME', 'Laravel'),
        'version' => env('APP_VERSION', '1.0.0'),
        'environment' => env('APP_ENV', 'production'),
    ],

    // Tracing configuration
    'tracing' => [
        'enabled' => env('OTEL_TRACING_ENABLED', true),
        'sampler' => 'always_on',  // always_on, never, ratio
        'sampling_ratio' => 1.0,   // Used with ratio sampler
    ],

    // Database tracing
    'database_tracing' => [
        'enabled' => env('OTEL_DB_TRACING_ENABLED', true),
        'sanitize_bindings' => true,
        'max_string_length' => 100,
    ],
];
