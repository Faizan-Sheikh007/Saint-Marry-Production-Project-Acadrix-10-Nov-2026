<?php

/**
 * ============================================================================
 * Acadrix Client Configuration v2
 * ============================================================================
 * Place this file in your EMS at: config/oribyte_client.php
 *
 * Required .env variables to add to your EMS .env:
 *
 *   ACADRIX_SERVER_URL=https://acadrix.cloud
 *   ACADRIX_API_KEY=<from admin panel → Clients → your client → Installation Details>
 *   ACADRIX_LICENSE_KEY=ACX-XXXX-XXXX-XXXX-XXXX
 *   ACADRIX_INSTALLATION_KEY=<32-char key from admin panel>
 *   ACADRIX_LICENSE_SECRET=<must match ACADRIX_LICENSE_SECRET in acadrix.cloud .env>
 *   ACADRIX_API_SECRET=<must match ACADRIX_API_SECRET in acadrix.cloud .env>
 *   ACADRIX_WEBHOOK_SECRET=<webhook signing secret from admin panel>
 *   ACADRIX_CLIENT_VERSION=2.1.0
 * ============================================================================
 */

return [

    // ── Connection ──────────────────────────────────────────────────────────
    'api_base'         => env('ACADRIX_SERVER_URL', 'https://acadrix.cloud'),
    'api_key'          => env('ACADRIX_API_KEY'),

    // ── Identity ────────────────────────────────────────────────────────────
    'license_key'      => env('ACADRIX_LICENSE_KEY'),
    'installation_key' => env('ACADRIX_INSTALLATION_KEY'),

    // ── Secrets (must match acadrix.cloud .env exactly) ─────────────────────
    'license_secret'   => env('ACADRIX_LICENSE_SECRET'),
    'api_secret'       => env('ACADRIX_API_SECRET'),
    'webhook_secret'   => env('ACADRIX_WEBHOOK_SECRET'),

    // ── Version reporting ────────────────────────────────────────────────────
    'version'          => env('ACADRIX_CLIENT_VERSION', '1.0.0'),

    // ── Cache ────────────────────────────────────────────────────────────────
    // How long (seconds) a valid license response is trusted before re-checking.
    // Default: 5 minutes. Lower = more security, higher = better performance.
    'cache_ttl'        => env('ACADRIX_CACHE_TTL', 300),

    // ── Heartbeat ────────────────────────────────────────────────────────────
    'heartbeat_interval' => 5,

    // ── Behaviour ────────────────────────────────────────────────────────────
    'feature_flags_enabled'  => true,
    'announcements_enabled'  => true,
    'update_notices_enabled' => true,

];
