<?php

namespace App\Services;

/**
 * Read-only, per-voucher financial position for the student and admin portals.
 *
 * Pass Student::authoritativeVouchers(), NOT all historical voucher rows: older
 * replaced plan rows must not inflate the current balance. Each voucher number
 * can have multiple fee-head rows; those must be shown as one installment.
 *
 * Amounts here are allocated to fees (Voucher::paid_amount), which is the same
 * balance source as Student::feeSummary(). Cash vs. prior balance-credit is
 * separately derived from verified Payment records in VoucherPaymentHistory.
 */
final class StudentFeeLedger
{
    /**
     * @param iterable $vouchers Authoritative voucher fee-head rows for one student.
     * @return array{rows: array, totals: array, counts: array}
     */
    public static function summarize(iterable $vouchers, ?\DateTimeInterface $asOf = null): array
    {
        $today = ($asOf ? \DateTimeImmutable::createFromInterface($asOf) : new \DateTimeImmutable('today'))
            ->setTime(0, 0);
        $todayStr = $today->format('Y-m-d');
        $soonStr = $today->modify('+7 days')->format('Y-m-d');

        $groups = [];
        foreach ($vouchers as $voucher) {
            // A voucher number is unique within the student whose records were
            // authorized and loaded by the calling controller.
            $rawNumber = $voucher->voucher_no;
            $number = $rawNumber !== null && (string) $rawNumber !== ''
                ? (string) $rawNumber : 'row-'.$voucher->id;
            $groups[$number][] = $voucher;
        }

        $totals = [
            'billed' => 0.0, 'discount' => 0.0, 'paid' => 0.0,
            'pending' => 0.0, 'overdue' => 0.0, 'due_soon' => 0.0,
            'upcoming' => 0.0,
        ];
        $counts = [
            'all' => 0, 'paid' => 0, 'unpaid' => 0, 'partial' => 0,
            'overdue' => 0, 'upcoming' => 0, 'due_soon' => 0, 'cancelled' => 0,
        ];
        $results = [];

        foreach ($groups as $number => $allLines) {
            $number = (string) $number;
            $nonCancelled = array_values(array_filter(
                $allLines, static fn ($v) => $v->status !== 'cancelled'
            ));
            $cancelled = count($nonCancelled) === 0;
            $lines = $cancelled ? $allLines : $nonCancelled;
            $representative = $lines[0];

            $billed = 0.0;
            $discount = 0.0;
            $paid = 0.0;
            $balance = 0.0;
            $items = [];
            $feeHeads = [];
            $dueDate = null;

            foreach ($lines as $v) {
                $feeTotal = max(0, (float) $v->total());
                // Never show an overpayment as money owed or settled twice.
                $applied = min($feeTotal, max(0, (float) $v->paid_amount));
                $outstanding = max(0, (float) $v->balance());
                $title = (string) ($v->feeHead?->name ?: ($v->title ?: 'Fee'));

                $billed += $feeTotal;
                $discount += max(0, (float) $v->discount_amount);
                $paid += $applied;
                $balance += $outstanding;
                $feeHeads[$title] = true;

                $date = $v->due_date;
                if ($date && (!$dueDate || $date->format('Y-m-d') < $dueDate->format('Y-m-d'))) {
                    $dueDate = $date;
                }

                $items[] = (object) [
                    'name' => $title,
                    'gross' => round((float) $v->amount, 2),
                    'discount' => round((float) $v->discount_amount, 2),
                    'amount' => round($feeTotal, 2),
                    'paid' => round($applied, 2),
                    'balance' => round($outstanding, 2),
                ];
            }

            $billed = round($billed, 2);
            $discount = round($discount, 2);
            $paid = round($paid, 2);
            $balance = round($balance, 2);
            $dateStr = $dueDate?->format('Y-m-d');

            $paymentState = $cancelled ? 'Cancelled'
                : ($balance <= 0 ? 'Paid' : ($paid > 0 ? 'Partial' : 'Unpaid'));
            $schedule = '—';
            if (!$cancelled && $balance > 0) {
                $schedule = !$dateStr ? 'Upcoming'
                    : ($dateStr < $todayStr ? 'Overdue'
                    : ($dateStr === $todayStr ? 'Due Today'
                    : ($dateStr <= $soonStr ? 'Due Soon' : 'Upcoming')));
            }

            $filters = ['all'];
            $counts['all']++;
            if ($cancelled) {
                $filters[] = 'cancelled';
                $counts['cancelled']++;
            } else {
                $totals['billed'] += $billed;
                $totals['discount'] += $discount;
                $totals['paid'] += $paid;
                $totals['pending'] += $balance;

                if ($paymentState === 'Paid') {
                    $counts['paid']++;
                    $filters[] = 'paid';
                } else {
                    $filters[] = 'unpaid'; // Includes partial balances.
                    if ($paymentState === 'Partial') {
                        $filters[] = 'partial';
                        $counts['partial']++;
                    } else {
                        $counts['unpaid']++;
                        $filters[] = 'unpaid_only';
                    }
                    if ($schedule === 'Overdue') {
                        $totals['overdue'] += $balance;
                        $counts['overdue']++;
                        $filters[] = 'overdue';
                    } elseif ($schedule === 'Due Today' || $schedule === 'Due Soon') {
                        $totals['due_soon'] += $balance;
                        $counts['due_soon']++;
                        $filters[] = 'due_soon';
                        if ($dateStr > $todayStr) {
                            $filters[] = 'upcoming';
                            $counts['upcoming']++;
                        }
                    } else {
                        $totals['upcoming'] += $balance;
                        $counts['upcoming']++;
                        $filters[] = 'upcoming';
                    }
                }
            }

            $plan = $representative->feeAssignment;
            $cycle = $representative->cycle_no;
            $term = $plan
                ? ($cycle !== null ? $plan->termLabelFor((int) $cycle) : $plan->cycleRangeLabel())
                : 'Other fee';
            $inst = $representative->installment_no;
            $part = $representative->part_no;
            $term .= $inst ? ' · Installment '.$inst : ($part ? ' · Part '.$part : '');

            $results[] = (object) [
                'voucher_no' => $number,
                'voucher' => $representative,
                'term' => $term,
                'items_label' => implode(', ', array_keys($feeHeads)),
                'lines' => $items,
                'amount' => $billed,
                'discount' => $discount,
                'paid' => $paid,
                'balance' => $balance,
                'due_date' => $dueDate,
                'payment_state' => $paymentState,
                'schedule' => $schedule,
                'filters' => implode(' ', array_unique($filters)),
                '_sort_priority' => match ($schedule) {
                    'Overdue' => 0,
                    'Due Today' => 1,
                    'Due Soon' => 2,
                    'Upcoming' => 3,
                    default => $paymentState === 'Paid' ? 4 : 5,
                },
                '_sort_date' => $dateStr ?: '9999-12-31',
            ];
        }

        usort($results, static fn ($a, $b) => [
            $a->_sort_priority, $a->_sort_date, $a->voucher_no,
        ] <=> [
            $b->_sort_priority, $b->_sort_date, $b->voucher_no,
        ]);

        foreach ($totals as $key => $amount) $totals[$key] = round($amount, 2);

        return ['rows' => $results, 'totals' => $totals, 'counts' => $counts];
    }
}
