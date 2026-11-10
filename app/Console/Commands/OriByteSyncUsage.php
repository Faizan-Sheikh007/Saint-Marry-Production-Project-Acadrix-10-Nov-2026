<?php

namespace App\Console\Commands;

use App\Services\OriByte\LicenseConnector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class OriByteSyncUsage extends Command
{
    protected $signature   = 'acadrix:sync-usage';
    protected $description = 'Report current resource usage counts to Acadrix dashboard';

    public function handle(LicenseConnector $connector): int
    {
        try {
            $usage = [
                'students'   => \App\Models\Student::count(),
                'vouchers'   => \App\Models\Voucher::count(),
                'storage_gb' => $this->diskUsageGb(),
            ];

            $connector->reportUsage($usage);

            $this->table(
                ['Resource', 'Count'],
                collect($usage)->map(fn ($v, $k) => [$k, $v])->values()->toArray()
            );

            $this->info('[Acadrix] Usage synced.');
        } catch (\Exception $e) {
            $this->error('[Acadrix] Sync failed: ' . $e->getMessage());
            Log::error('[Acadrix] Usage sync failed: ' . $e->getMessage());
        }

        return self::SUCCESS;
    }

    private function diskUsageGb(): float
    {
        $bytes = 0;
        $path  = storage_path('app/public');

        if (is_dir($path)) {
            $iterator = new \RecursiveIteratorIterator(
                new \RecursiveDirectoryIterator($path, \FilesystemIterator::SKIP_DOTS)
            );
            foreach ($iterator as $file) {
                $bytes += $file->getSize();
            }
        }

        return round($bytes / 1073741824, 3);
    }
}
