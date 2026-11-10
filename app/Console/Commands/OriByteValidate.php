<?php

namespace App\Console\Commands;

use App\Services\OriByte\LicenseConnector;
use Illuminate\Console\Command;

class OriByteValidate extends Command
{
    protected $signature   = 'acadrix:validate {--force : Force re-validation bypassing cache}';
    protected $description = 'Validate the Acadrix license with the SaaS dashboard';

    public function handle(LicenseConnector $connector): int
    {
        try {
            $result = $connector->validate(force: (bool) $this->option('force'));

            $this->info('[Acadrix] License valid.');
            $this->line('  Plan:       ' . ($result['plan']['name']  ?? 'Unknown'));
            $this->line('  Expires:    ' . ($result['expires_at']    ?? 'N/A'));
            $this->line('  Students:   ' . ($result['limits']['students']['used']  ?? 0) . ' / ' . ($result['limits']['students']['max']  ?? '?'));
            $this->line('  Students:   ' . ($result['limits']['students']['used']  ?? 0) . ' / ' . ($result['limits']['students']['max']  ?? '?'));
            $this->line('  Vouchers:   ' . ($result['limits']['vouchers']['used']   ?? 0) . ' / ' . ($result['limits']['vouchers']['max']   ?? '?'));
        } catch (\RuntimeException $e) {
            $this->error('[Acadrix] License INVALID: ' . $e->getMessage());
            return self::FAILURE;
        }

        return self::SUCCESS;
    }
}
