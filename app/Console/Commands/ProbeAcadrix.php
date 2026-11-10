<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Http;

class ProbeAcadrix extends Command
{
    protected $signature = 'debug:probe';
    protected $description = 'Probe Acadrix server endpoints';

    public function handle()
    {
        $base    = config('oribyte_client.api_base');
        $apiKey  = config('oribyte_client.api_key');
        $headers = ['Accept' => 'application/json', 'X-API-Key' => $apiKey];
        $http    = Http::withOptions(['verify' => false])->timeout(10)->withHeaders($headers);

        $payload = [
            'license_key'      => config('oribyte_client.license_key'),
            'installation_key' => config('oribyte_client.installation_key'),
            'version'          => config('oribyte_client.version', '1.0.0'),
            'server_data'      => [
                'ip'              => gethostbyname(gethostname()),
                'hostname'        => gethostname(),
                'php_version'     => PHP_VERSION,
                'server_software' => 'unknown',
            ],
        ];

        $signature = hash_hmac('sha256', json_encode($payload), config('oribyte_client.license_secret'));

        $this->info('=== Trying /api/license/validate with full payload ===');
        $r = $http->withHeaders(['X-License-Signature' => $signature])->post($base . '/api/license/validate', $payload);
        $this->info('Status: ' . $r->status());
        $this->info('Body:   ' . $r->body());

        $this->info('');
        $this->info('=== Trying /api/license/validate WITHOUT signature ===');
        $r2 = $http->post($base . '/api/license/validate', $payload);
        $this->info('Status: ' . $r2->status());
        $this->info('Body:   ' . $r2->body());

        $this->info('');
        $this->info('=== Trying /api/license/validate with api_secret as signature ===');
        $sig2 = hash_hmac('sha256', json_encode($payload), config('oribyte_client.api_secret'));
        $r3 = $http->withHeaders(['X-License-Signature' => $sig2])->post($base . '/api/license/validate', $payload);
        $this->info('Status: ' . $r3->status());
        $this->info('Body:   ' . $r3->body());
    }
}
