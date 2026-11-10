<?php

namespace App\Console\Commands;

use App\Services\OriByte\LicenseConnector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class OriByteCheckUpdates extends Command
{
    protected $signature   = 'acadrix:check-updates';
    protected $description = 'Check for new Acadrix EMS software releases';

    public function handle(LicenseConnector $connector): int
    {
        $result = $connector->checkForUpdates();

        if ($result['update_available'] ?? false) {
            $this->warn(sprintf(
                '[Acadrix] Update available: v%s (%s) — %s',
                $result['version'],
                $result['mandatory'] ? 'MANDATORY' : 'Optional',
                $result['changelog'] ?? ''
            ));

            if ($result['mandatory']) {
                try {
                    $admin = config('mail.from.address');
                    \Illuminate\Support\Facades\Mail::raw(
                        "A mandatory Acadrix EMS update is available.\n\nVersion: {$result['version']}\n\n{$result['changelog']}\n\nDownload: {$result['download_url']}",
                        fn ($m) => $m->to($admin)->subject('[Acadrix] Mandatory Update Required: v' . $result['version'])
                    );
                } catch (\Exception $e) {
                    Log::error('[Acadrix] Failed to send update email: ' . $e->getMessage());
                }
            }
        } else {
            $this->info('[Acadrix] Software is up to date.');
        }

        return self::SUCCESS;
    }
}
