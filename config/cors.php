<?php

return [

    /*
     * EMS Route Tester — CORS Configuration
     * ─────────────────────────────────────
     * This config allows the external HTML tester tool to communicate
     * with your Laravel app from file:// or any localhost origin.
     *
     * ⚠ Use this config ONLY for local development/testing.
     * Switch back to a restrictive config before going to production.
     */

    'paths' => ['*'],

    'allowed_methods' => ['*'],

    // Allow ALL localhost origins + file:// (null origin)
    'allowed_origins' => [
        'http://localhost',
        'http://localhost:8000',
        'http://localhost:8080',
        'http://localhost:3000',
        'http://127.0.0.1',
        'http://127.0.0.1:8000',
        'http://127.0.0.1:8080',
        'http://127.0.0.1:5500',   // VS Code Live Server
        'http://127.0.0.1:5501',
    ],

    // This pattern allows ANY localhost port (e.g. :5500, :9000, etc.)
    'allowed_origins_patterns' => [
        '#^https?://localhost(:\d+)?$#',
        '#^https?://127\.0\.0\.1(:\d+)?$#',
    ],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    /*
     * IMPORTANT: supports_credentials must be TRUE for session cookies to work.
     * When this is true, allowed_origins CANNOT be ['*'] — must be explicit URLs.
     * That's why we list them above.
     */
    'supports_credentials' => true,
];
