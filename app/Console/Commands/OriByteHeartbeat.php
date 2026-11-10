<?php

namespace App\Console\Commands;

use App\Services\OriByte\LicenseConnector;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class OriByteHeartbeat extends Command
{
    protected $signature   = 'acadrix:heartbeat';
    protected $description = 'Send heartbeat ping to Acadrix SaaS dashboard';

    public function handle(LicenseConnector $connector): int
    {
        $success = $connector->heartbeat();

        if ($success) {
            $this->info('[Acadrix] Heartbeat sent.');
            Log::debug('[Acadrix] Heartbeat OK');
        } else {
            $this->warn('[Acadrix] Heartbeat failed — dashboard may be unreachable.');
        }

        return self::SUCCESS;
    }
}
