<?php

namespace App\Providers;

use App\Models\Examination;
use App\Models\Voucher;
use App\Observers\ExaminationObserver;
use App\Observers\VoucherObserver;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    public function register(): void {}

    public function boot(): void
    {
        // ── Force HTTPS in production ───────────────────────────────────
        // Every generated URL (redirects, form actions, asset links) uses
        // https:// once deployed, closing the door on a man-in-the-middle
        // stripping encryption before login credentials are sent.
        if ($this->app->environment('production')) {
            URL::forceScheme('https');
        }

        // FIX 2: Was empty — @oribyteFeature, @oribyteLimit, @oribyteExpiring
        // all threw Blade errors without this single line.
        \App\Services\OriByte\OriByteBlade::register();

        // Guarantees an ExamPaper is auto-created for every Examination,
        // regardless of which controller/seeder/import created it.
        Examination::observe(ExaminationObserver::class);

        // Fires the "Internship confirmed!" student notification the moment
        // an internship challan is fully paid, from wherever the payment
        // was recorded (Accounts, verified online payment, etc.).
        Voucher::observe(VoucherObserver::class);
        \App\Models\Student::observe(\App\Observers\StudentStatusObserver::class);
    }
}
