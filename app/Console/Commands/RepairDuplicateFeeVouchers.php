<?php

namespace App\Console\Commands;

use App\Models\FeeAssignment;
use App\Models\FeeHead;
use App\Models\Voucher;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairDuplicateFeeVouchers extends Command
{
    /**
     * php artisan fees:repair-duplicates {--dry-run}
     *
     * One-off cleanup for vouchers generated before Admission Fee / Examination
     * Fee (or similar heads) were correctly typed as 'one_time' / 'per_cycle'.
     * Those older plans have the fee-head line repeated in every installment
     * of a semester (and, for one_time heads, repeated in every semester too).
     *
     * This walks every FeeAssignment, finds voucher lines for one_time and
     * per_cycle fee heads that shouldn't exist under the current rules, and
     * removes the extra UNPAID ones (keeping the earliest so nothing the
     * student already owes just vanishes). Anything with a payment recorded
     * against it is left alone and reported for manual review — this command
     * never touches money that's already been paid.
     */
    protected $signature = 'fees:repair-duplicates {--dry-run : Preview changes without saving them}';

    protected $description = 'Remove duplicate one_time/per_cycle fee-head voucher lines left over from before the fee head types were corrected';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');

        $headIds = FeeHead::whereIn('type', ['one_time', 'per_cycle'])->pluck('type', 'id');

        if ($headIds->isEmpty()) {
            $this->info('No one_time or per_cycle fee heads configured — nothing to check.');
            return self::SUCCESS;
        }

        $removedTotal = 0;
        $flaggedForReview = 0;

        FeeAssignment::query()->chunk(50, function ($assignments) use ($headIds, $dryRun, &$removedTotal, &$flaggedForReview) {
            foreach ($assignments as $assignment) {
                $vouchers = $assignment->vouchers()
                    ->whereIn('fee_head_id', $headIds->keys())
                    // A challan split intentionally creates more than one row
                    // for the same fee head/cycle. Those rows are portions of
                    // one original charge, not historical duplicate charges.
                    ->whereNull('split_root_voucher_no')
                    ->orderBy('due_date')
                    ->orderBy('installment_no')
                    ->get();

                if ($vouchers->isEmpty()) continue;

                // per_cycle: at most ONE line per (fee head, cycle_no) within this
                // assignment. one_time heads are checked globally per student below,
                // since they can span multiple fee assignments (renewals).
                foreach ($vouchers->groupBy('fee_head_id') as $feeHeadId => $lines) {
                    if (($headIds[$feeHeadId] ?? null) !== 'per_cycle') continue;

                    foreach ($lines->groupBy('cycle_no') as $cycleLines) {
                        $this->stripExtras($cycleLines, $dryRun, $removedTotal, $flaggedForReview);
                    }
                }
            }
        });

        // one_time heads: check globally per student, independent of assignment.
        foreach ($headIds->filter(fn ($t) => $t === 'one_time')->keys() as $feeHeadId) {
            $lines = Voucher::where('fee_head_id', $feeHeadId)
                ->whereNull('split_root_voucher_no')
                ->orderBy('due_date')->orderBy('installment_no')->get()
                ->groupBy('student_id');

            foreach ($lines as $studentLines) {
                $this->stripExtras($studentLines, $dryRun, $removedTotal, $flaggedForReview);
            }
        }

        if ($dryRun) {
            $this->info("[dry-run] Would remove {$removedTotal} duplicate unpaid voucher line(s).");
        } else {
            $this->info("Removed {$removedTotal} duplicate unpaid voucher line(s).");
        }

        if ($flaggedForReview > 0) {
            $this->warn("{$flaggedForReview} duplicate line(s) had a payment recorded and were left in place — please review those manually.");
        }

        return self::SUCCESS;
    }

    /** Keep the earliest line in $lines, remove the rest if unpaid, flag the rest if paid. */
    protected function stripExtras($lines, bool $dryRun, int &$removedTotal, int &$flaggedForReview): void
    {
        if ($lines->count() <= 1) return;

        $sorted = $lines->sortBy([['due_date', 'asc'], ['installment_no', 'asc']])->values();
        $keep = $sorted->first();
        $extras = $sorted->slice(1);

        foreach ($extras as $extra) {
            if ((float) $extra->paid_amount > 0) {
                $flaggedForReview++;
                $this->line("  [needs review] Voucher {$extra->voucher_no} (student #{$extra->student_id}) has a payment recorded against a duplicate line — not touched.");
                continue;
            }

            $removedTotal++;
            $this->line("  Removing duplicate: voucher {$extra->voucher_no}, student #{$extra->student_id}, Rs. {$extra->amount}, due {$extra->due_date?->toDateString()} (keeping {$keep->voucher_no})");

            if (!$dryRun) {
                DB::transaction(function () use ($extra) {
                    $assignment = $extra->feeAssignment;
                    $delta = round((float) $extra->amount - (float) $extra->discount_amount, 2);
                    $extra->delete();
                    if ($assignment) {
                        $assignment->update(['total_amount' => max(0, round((float) $assignment->total_amount - $delta, 2))]);
                    }
                });
            }
        }
    }
}
