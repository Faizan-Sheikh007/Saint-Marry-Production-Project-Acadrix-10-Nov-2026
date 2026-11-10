<?php

namespace App\Services;

use App\Models\FeeAssignment;
use Illuminate\Contracts\View\View;

/**
 * Build the existing full fee-installment-plan slip for an authorized viewer.
 * Authorization must be enforced by the calling controller: admin/accounts,
 * student ownership, or guardian-child relationship as applicable.
 *
 * This uses the existing fee-assignments.summary Blade design, not the
 * installment-by-installment fee-assignments.plan challan/receipt template.
 */
class FeePlanSummary
{
    public function render(FeeAssignment $feeAssignment, ?int $cycle = null): View
    {
        $feeAssignment->load('student.schoolClass', 'student.program.institution', 'student.academicYear', 'feeTemplate.items.feeHead', 'bankAccount');
        $groups  = $feeAssignment->installmentGroups();
        $student = $feeAssignment->student;

        $cycleLabel = null;
        if ($cycle !== null) {
            abort_unless($cycle >= $feeAssignment->cycle_start && $cycle <= $feeAssignment->cycle_end, 404);
            $groups = $groups->filter(fn ($vouchers) => (int) ($vouchers->first()->cycle_no ?? $feeAssignment->cycle_start) === $cycle);
            abort_if($groups->isEmpty(), 404, 'No installments exist yet for this semester/year on this plan.');
            $cycleLabel = $feeAssignment->termLabelFor($cycle);
        }

        // Gross totals (before discount) plus the actual discount/financial-aid
        // already recorded on each voucher — the same authoritative fields used
        // everywhere else in the app — rather than re-deriving them from the
        // plan's generic percentages, which drift after a semester is re-split
        // or a voucher's discount is edited directly.
        $tuitionGross   = 0;
        $otherDuesGross = 0;
        $otherDuesByHead = []; // Fee Head name => gross amount, so the summary can list exact heads instead of one lump "Other Dues" line
        $discountTotal  = 0;
        $rows = [];

        // Starting balance for the ledger's running total — the whole plan's
        // total when printing every semester, or just this semester's own
        // payable total when printing a single semester's summary.
        $scopeTotal = round($groups->flatten()->sum(fn ($v) => (float) $v->amount - (float) $v->discount_amount), 2);
        $runningBalance = $cycle !== null ? $scopeTotal : (float) $feeAssignment->total_amount;

        foreach ($groups as $no => $vouchers) {
            $amount   = (float) $vouchers->sum('amount');
            $discount = (float) $vouchers->sum('discount_amount');
            $payable  = round($amount - $discount, 2);
            $runningBalance = round($runningBalance - $payable, 2);

            foreach ($vouchers as $v) {
                if (is_null($v->amount)) continue;
                $isTuition = ($v->feeHead->category ?? null) === 'tuition';
                if ($isTuition) {
                    $tuitionGross += (float) $v->amount;
                } else {
                    $otherDuesGross += (float) $v->amount;
                    $headName = $v->feeHead->name ?? 'Other Dues';
                    $otherDuesByHead[$headName] = ($otherDuesByHead[$headName] ?? 0) + (float) $v->amount;
                }
                $discountTotal += (float) $v->discount_amount;
            }

            $rows[] = [
                'no'         => $no,
                // An installment may have multiple printable challan numbers
                // after Accounts splits it into a desired amount + remainder.
                'voucher_no' => $vouchers->pluck('voucher_no')->unique()->implode(' / '),
                'payable'    => $payable,
                'balance'    => max(0, $runningBalance),
                'due_date'   => $vouchers->first()->due_date,
                'status'     => $vouchers->sortBy(fn ($v) => match ($v->status) {
                    'overdue' => 0, 'partial' => 1, 'unpaid' => 2, 'paid' => 3, default => 4,
                })->first()->status,
            ];
        }

        $installmentsCount = $cycle !== null ? count($rows) : $feeAssignment->installments;
        $perInstallment = $installmentsCount > 0 ? round($scopeTotal / $installmentsCount, 2) : 0;

        $declaration = \App\Models\Setting::get('fee_summary_declaration');
        $feeNote     = \App\Models\Setting::get('fee_summary_note', 'This fee is neither refundable nor adjustable.');

        return view('fee-assignments.summary', compact(
            'feeAssignment', 'student', 'rows', 'perInstallment', 'installmentsCount',
            'tuitionGross', 'otherDuesGross', 'otherDuesByHead', 'discountTotal', 'scopeTotal', 'declaration', 'feeNote', 'cycleLabel'
        ));
    }
}
