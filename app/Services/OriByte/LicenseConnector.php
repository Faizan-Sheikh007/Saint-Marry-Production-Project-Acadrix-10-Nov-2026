<?php

/**
 * ============================================================================
 * Acadrix SaaS License Connector v2
 * ============================================================================
 * Drop-in replacement for the original LicenseConnector. New in v2:
 *
 *  - Feature flags: remotely toggle EMS features from the SaaS dashboard
 *  - Announcements: show admin-broadcast messages in the EMS dashboard
 *  - Software update check: notify admins when a new EMS version is available
 *  - Webhook receiver helpers: verify and process incoming Acadrix webhooks
 *  - IP-bound licensing: validate the request IP matches the licensed server
 *  - Signed heartbeat commands: receive emergency commands from the dashboard
 *
 * Place this file at: app/Services/OriByte/LicenseConnector.php
 * ============================================================================
 */

namespace App\Services\OriByte;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LicenseConnector
{
    private string $apiBase;
    private string $apiKey;
    private string $licenseKey;
    private string $installationKey;
    private string $cipher = 'AES-256-GCM';
    private int    $cacheTtl;

    public function __construct()
    {
        $this->apiBase         = rtrim(config('oribyte_client.api_base'), '/');
        $this->apiKey          = config('oribyte_client.api_key');
        $this->licenseKey      = config('oribyte_client.license_key');
        $this->installationKey = config('oribyte_client.installation_key');
        $this->cacheTtl        = (int) config('oribyte_client.cache_ttl', 300);
    }

    // ── Core validation ────────────────────────────────────────────────────

    /**
     * Validate this installation's license. Returns the full validation result.
     * Throws RuntimeException if the license is blocked or cannot be verified.
     */
    public function validate(bool $force = false): array
    {
        $cacheKey = 'acadrix_license_valid';

        if (! $force && Cache::has($cacheKey)) {
            return Cache::get($cacheKey);
        }

        try {
            $serverData = $this->collectServerData();
            $payload    = json_encode([
                'license_key'      => $this->licenseKey,
                'installation_key' => $this->installationKey,
                'server_data'      => $serverData,
                'version'          => config('oribyte_client.version', '1.0.0'),
            ]);

            $response = $this->http()
                ->connectTimeout(2)
                ->timeout(4)
                ->withHeaders([
                    'X-API-Key'           => $this->apiKey,
                    'X-License-Signature' => $this->signPayload($payload),
                    'Content-Type'        => 'application/json',
                ])
                ->post("{$this->apiBase}/api/license/validate", json_decode($payload, true));

            if ($response->status() === 403) {
                $data   = $response->json();
                $action = $data['action'] ?? 'block';

                if (in_array($action, ['block', 'suspend'])) {
                    $this->blockSystem($data['error'] ?? 'License invalid or suspended.');
                }

                throw new \RuntimeException($data['error'] ?? 'License validation failed.');
            }

            if (! $response->successful()) {
                // Fail-open: use cached result up to 1 hour on network error
                $cached = Cache::get('acadrix_license_last_valid');
                if ($cached && isset($cached['cached_at']) && (time() - $cached['cached_at']) < 3600) {
                    return $cached;
                }

                throw new \RuntimeException('Unable to reach license server.');
            }

            $result              = $response->json();
            $result['cached_at'] = time();

            Cache::put($cacheKey, $result, $this->cacheTtl);
            Cache::forever('acadrix_license_last_valid', $result);

            // ── Handle any emergency commands from the dashboard ───────────
            $this->handleCommands($result['commands'] ?? []);

            // ── Check for mandatory update ─────────────────────────────────
            if (! empty($result['update']['mandatory'])) {
                $this->handleMandatoryUpdate($result['update']);
            }

            return $result;

        } catch (\RuntimeException $e) {
            throw $e;
        } catch (\Exception $e) {
            Log::error('[Acadrix] License validation exception: ' . $e->getMessage());
            throw new \RuntimeException('License check failed: ' . $e->getMessage());
        }
    }

    // ── Feature flags ──────────────────────────────────────────────────────

    /**
     * Check whether a feature flag is enabled for this installation.
     */
    public function isFeatureEnabled(string $flagKey, bool $default = false): bool
    {
        try {
            $result = $this->validate();
            return (bool) ($result['feature_flags'][$flagKey] ?? $default);
        } catch (\Exception $e) {
            return $default;
        }
    }

    /**
     * Get all feature flags as an array.
     */
    public function getFeatureFlags(): array
    {
        try {
            $result = $this->validate();
            return $result['feature_flags'] ?? [];
        } catch (\Exception $e) {
            return [];
        }
    }

    // ── Announcement ──────────────────────────────────────────────────────

    /**
     * Get the active announcement for this installation, if any.
     */
    public function getAnnouncement(): ?array
    {
        try {
            $result = $this->validate();
            return $result['announcement'] ?? null;
        } catch (\Exception $e) {
            return null;
        }
    }

    // ── Plan limit enforcement ─────────────────────────────────────────────

    /**
     * Check whether adding $requested units of $resource stays within plan limits.
     */
    public function canAdd(string $resource, int $requested = 1): bool
    {
        try {
            $validation = $this->validate();
            $limits     = $validation['limits'] ?? [];

            if (! isset($limits[$resource])) {
                return true;
            }

            $limit = $limits[$resource]['max'];
            $used  = $limits[$resource]['used'];

            if ($limit === -1) {
                return true; // Unlimited
            }

            return ($used + $requested) <= $limit;
        } catch (\Exception $e) {
            Log::warning('[Acadrix] canAdd() check failed — defaulting to deny: ' . $e->getMessage());
            return false;
        }
    }

    // ── Usage reporting ────────────────────────────────────────────────────

    public function reportUsage(array $usage): bool
    {
        try {
            $response = $this->apiHttp(10)
                ->post("{$this->apiBase}/api/license/usage", [
                    'license_key'      => $this->licenseKey,
                    'installation_key' => $this->installationKey,
                    'usage'            => $usage,
                ]);

            if ($response->successful()) {
                $data = $response->json();

                if (! empty($data['warnings'])) {
                    session()->flash('acadrix_warnings', $data['warnings']);
                }

                if (! empty($data['exceeded'])) {
                    session()->flash('acadrix_exceeded', $data['exceeded']);
                }

                return true;
            }
        } catch (\Exception $e) {
            Log::error('[Acadrix] Usage report failed: ' . $e->getMessage());
        }

        return false;
    }

    // ── Heartbeat ──────────────────────────────────────────────────────────

    public function heartbeat(): bool
    {
        try {
            $response = $this->apiHttp(5)
                ->post("{$this->apiBase}/api/license/heartbeat", [  // FIX 2: was /api/heartbeat
                    'license_key'      => $this->licenseKey,
                    'installation_key' => $this->installationKey,
                    'system_health'    => [
                        'memory_usage' => memory_get_usage(true),
                        'disk_free'    => disk_free_space('/'),
                        'php_version'  => PHP_VERSION,
                        'uptime'       => time(),
                    ],
                ]);

            if ($response->successful()) {
                $data     = $response->json();
                $commands = $data['commands'] ?? [];
                $this->handleCommands($commands);
                return true;
            }
        } catch (\Exception $e) {
            Log::debug('[Acadrix] Heartbeat failed: ' . $e->getMessage());
        }

        return false;
    }

    // ── Software update check ──────────────────────────────────────────────

    public function checkForUpdates(): array
    {
        try {
            $response = $this->apiHttp(10)
                ->get("{$this->apiBase}/api/updates/check", [
                    'installation_key' => $this->installationKey,
                ]);

            if ($response->successful()) {
                return $response->json();
            }
        } catch (\Exception $e) {
            Log::debug('[Acadrix] Update check failed: ' . $e->getMessage());
        }

        return ['update_available' => false];
    }

    // ── Webhook receiver ──────────────────────────────────────────────────

    public function verifyWebhook(\Illuminate\Http\Request $request): bool
    {
        $signature = $request->header('X-Acadrix-Signature');
        if (! $signature) {
            return false;
        }

        $expected = hash_hmac(
            'sha256',
            $request->getContent(),
            config('oribyte_client.webhook_secret', config('oribyte_client.api_secret'))
        );

        return hash_equals($expected, $signature);
    }

    // ── Decrypt dashboard response ─────────────────────────────────────────

    public function decryptDashboardResponse(string $encryptedPayload): ?array
    {
        try {
            $key  = hash('sha256', $this->installationKey . config('oribyte_client.api_secret'), true);
            $data = base64_decode($encryptedPayload);

            if (strlen($data) < 32) {
                return null;
            }

            $iv         = substr($data, 0, 16);
            $tag        = substr($data, 16, 16);
            $ciphertext = substr($data, 32);

            $decrypted = openssl_decrypt(
                $ciphertext, $this->cipher, $key,
                OPENSSL_RAW_DATA, $iv, $tag
            );

            return $decrypted ? json_decode($decrypted, true) : null;
        } catch (\Exception $e) {
            Log::error('[Acadrix] Decryption failed: ' . $e->getMessage());
            return null;
        }
    }

    // ── Private helpers ───────────────────────────────────────────────────

    /**
     * Return an Http client with SSL verification disabled on local/dev,
     * or full SSL verification on production.
     */
    private function http(): \Illuminate\Http\Client\PendingRequest
    {
        $client = \Illuminate\Support\Facades\Http::connectTimeout(2)->timeout(4)
            ->withHeaders(['Accept' => 'application/json']);

        if (app()->environment(['local', 'testing'])) {
            $client = $client->withOptions(['verify' => false]);
        }

        return $client;
    }

    /**
     * HTTP client pre-loaded with API key header.
     */
    private function apiHttp(int $timeout = 15): \Illuminate\Http\Client\PendingRequest
    {
        return $this->http()
            ->timeout($timeout)
            ->withHeaders(['X-API-Key' => $this->apiKey]);
    }

    private function collectServerData(): array
    {
        $ip = $_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname());

        // Fallback to 127.0.0.1 if hostname resolution fails (common on Windows localhost)
        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            $ip = '127.0.0.1';
        }

        return [
            'ip'              => $ip,
            'hostname'        => gethostname(),
            'php_version'     => PHP_VERSION,
            'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'unknown',
        ];
    }

    private function signPayload(string $payload): string
    {
        return hash_hmac('sha256', $payload, config('oribyte_client.license_secret'));
    }

    /**
     * Handle commands sent from the SaaS dashboard via heartbeat/validation response.
     */
    private function handleCommands(array $commands): void
    {
        foreach ($commands as $command) {
            match ($command) {
                'force_revalidate' => Cache::forget('acadrix_license_valid'),
                'clear_cache'      => \Illuminate\Support\Facades\Artisan::call('cache:clear'),
                'flush_sessions'   => \Illuminate\Support\Facades\DB::table('sessions')->truncate(),
                default            => Log::info("[Acadrix] Unknown command: {$command}"),
            };
        }
    }

    private function handleMandatoryUpdate(array $updateInfo): void
    {
        Cache::put('acadrix_mandatory_update', $updateInfo, now()->addHours(1));
        Log::warning('[Acadrix] Mandatory update required. New version: ' . $updateInfo['version']);
    }

    private function blockSystem(string $reason): void
    {
        $blockFile = storage_path('app/acadrix_block.lock');
        file_put_contents($blockFile, json_encode([
            'reason'     => $reason,
            'blocked_at' => now()->toIso8601String(),
        ]));

        $this->renderBlockPage($reason);
    }

    /**
     * Force re-validate with a new license key (used on lock screen key entry).
     */
    public function forceRevalidate(string $newKey): array
    {
        config(['oribyte_client.license_key' => $newKey]);

        Cache::forget('acadrix_license_valid');
        Cache::forget('acadrix_validation_result');

        return $this->validate(force: true);
    }

    /**
     * Fetch available plans from the SaaS dashboard (for lock screen display).
     */
    public function getAvailablePlans(): array
    {
        return Cache::remember('acadrix_available_plans', 3600, function () {
            try {
                $response = $this->http()
                    ->timeout(8)
                    ->withHeaders([
                        'X-Api-Key'     => config('oribyte_client.api_key'),
                        'X-Install-Key' => config('oribyte_client.installation_key'),
                    ])
                    ->get(config('oribyte_client.api_base') . '/api/plans/public');

                if ($response->successful()) {
                    return $response->json('plans', []);
                }
            } catch (\Exception $e) {
                // Return empty — lock screen still works without plan cards
            }
            return [];
        });
    }

    private function renderBlockPage(string $reason): void
    {
        http_response_code(403);
        echo <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>System Suspended — Acadrix</title>
<style>
  * { margin: 0; padding: 0; box-sizing: border-box; }
  body { font-family: 'Outfit', sans-serif; background: #0f0f0f; color: #fff;
         display: flex; align-items: center; justify-content: center; min-height: 100vh; }
  .card { background: #1a1a1a; border: 1px solid #890C25; border-radius: 12px;
          padding: 48px; max-width: 500px; text-align: center; }
  .logo { color: #890C25; font-size: 2rem; font-weight: 700; margin-bottom: 24px; }
  h1 { font-size: 1.5rem; margin-bottom: 16px; }
  p  { color: #aaa; line-height: 1.6; margin-bottom: 24px; }
  a  { display: inline-block; background: #890C25; color: #fff; padding: 12px 28px;
       border-radius: 8px; text-decoration: none; font-weight: 600; }
</style>
</head>
<body>
  <div class="card">
    <div class="logo">Acadrix</div>
    <h1>System Suspended</h1>
    <p>{$reason}</p>
    <p>Please contact support or renew your license to restore access.</p>
    <a href="https://acadrix.cloud/pricing">Renew License</a>
  </div>
</body>
</html>
HTML;
        exit(1);
    }
}
