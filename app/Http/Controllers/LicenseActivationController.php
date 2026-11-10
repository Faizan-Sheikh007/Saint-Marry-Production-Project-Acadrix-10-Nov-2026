<?php

/**
 * Acadrix EMS — License Activation Controller (patched)
 * FIX 5: Block file was named oribyte_block.lock but connector writes acadrix_block.lock
 */

namespace App\Http\Controllers;

use App\Services\OriByte\LicenseConnector;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class LicenseActivationController extends Controller
{
    public function activate(Request $request, LicenseConnector $connector)
    {
        $request->validate([
            'license_key' => ['required', 'string'],
        ]);

        $key = strtoupper(trim($request->license_key));

        try {
            // Update .env
            $envPath = base_path('.env');
            if (file_exists($envPath)) {
                $env = file_get_contents($envPath);
                if (str_contains($env, 'ACADRIX_LICENSE_KEY=')) {
                    $env = preg_replace('/ACADRIX_LICENSE_KEY=.*/m', "ACADRIX_LICENSE_KEY={$key}", $env);
                } else {
                    $env .= "\nACADRIX_LICENSE_KEY={$key}";
                }
                file_put_contents($envPath, $env);
            }

            // FIX 5: Was deleting 'oribyte_block.lock' — connector writes 'acadrix_block.lock'
            $blockFile = storage_path('app/acadrix_block.lock');
            if (file_exists($blockFile)) {
                unlink($blockFile);
                Log::info('[Acadrix] Block file removed on license activation.');
            }

            // Clear all license caches
            Cache::forget('acadrix_license_valid');
            Cache::forget('acadrix_validation_result');
            Cache::forget('acadrix_license_last_valid');
            Cache::forget('acadrix_suspended');

            // Force re-validate with new key
            $result = $connector->forceRevalidate($key);

            if (! ($result['valid'] ?? false)) {
                return response()->json([
                    'success' => false,
                    'message' => 'License key is not valid or has expired. Please purchase a plan at acadrix.cloud.',
                ], 422);
            }

            try {
                Artisan::call('config:clear');
            } catch (\Exception $e) {
                // Non-fatal on shared hosting
            }

            return response()->json([
                'success' => true,
                'message' => 'License activated successfully! Redirecting...',
                'redirect' => route('admin.dashboard'),
            ]);

        } catch (\RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        } catch (\Exception $e) {
            Log::error('[Acadrix] Activation error: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => 'Could not validate. Check your internet connection.'], 500);
        }
    }
}
