<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class DebugLicense extends Command
{
    protected $signature   = 'debug:license';
    protected $description = 'Debug license server response';

    public function handle()
    {
        $payload = json_encode([
            'license_key'      => config('oribyte_client.license_key'),
            'installation_key' => config('oribyte_client.installation_key'),
            'server_data'      => [
                'ip'              => $this->resolveIp(),
                'hostname'        => gethostname(),
                'php_version'     => PHP_VERSION,
                'server_software' => 'unknown',
            ],
            'version' => config('oribyte_client.version', '1.0.0'),
        ]);

        $signature = hash_hmac('sha256', $payload, config('oribyte_client.license_secret'));

        $this->info('--- Config ---');
        $this->info('api_base:         ' . config('oribyte_client.api_base'));
        $this->info('api_key:          ' . config('oribyte_client.api_key'));
        $this->info('license_key:      ' . config('oribyte_client.license_key'));
        $this->info('installation_key: ' . config('oribyte_client.installation_key'));
        $this->info('license_secret:   ' . substr(config('oribyte_client.license_secret'), 0, 10) . '...');
        $this->info('version:          ' . config('oribyte_client.version', '1.0.0'));
        $this->info('environment:      ' . app()->environment());
        $this->info('resolved_ip:      ' . $this->resolveIp());
        $this->info('');
        $this->info('--- Sending Request ---');

        try {
            $response = Http::withOptions(['verify' => false])
                ->timeout(15)
                ->withHeaders([
                    'X-API-Key'           => config('oribyte_client.api_key'),
                    'X-License-Signature' => $signature,  // FIX 3: was /api/v1/license/validate
                    'Content-Type'        => 'application/json',
                    'Accept'              => 'application/json',
                ])
                ->post(
                    config('oribyte_client.api_base') . '/api/license/validate',
                    json_decode($payload, true)
                );

            $this->info('Status:  ' . $response->status());
            $this->info('Body:    ' . $response->body());

        } catch (\Exception $e) {
            $this->error('Exception: ' . $e->getMessage());
        }
    }

    private function resolveIp(): string
    {
        $ip = $_SERVER['SERVER_ADDR'] ?? gethostbyname(gethostname());

        if (! filter_var($ip, FILTER_VALIDATE_IP)) {
            return '127.0.0.1';
        }

        return $ip;
    }
}
