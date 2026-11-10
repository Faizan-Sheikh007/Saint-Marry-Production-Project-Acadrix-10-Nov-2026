<?php
/** Run with: php tests/Regression/fee_plan_policy.php (no vendor dependencies). */
require dirname(__DIR__, 2) . '/app/Services/FeePlanProgression.php';

use App\Services\FeePlanProgression as Policy;

$checks = 0;
function same(mixed $expected, mixed $actual, string $description): void {
    global $checks;
    $checks++;
    if ($expected !== $actual) {
        fwrite(STDERR, "FAIL: {$description}\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true) . "\n");
        exit(1);
    }
}
function plan(int $id, int $start, int $end, ?string $lastDate): array {
    return ['id' => $id, 'cycle_start' => $start, 'cycle_end' => $end, 'last_due_date' => $lastDate];
}

$first = Policy::fromPlans([], 1, 8);
same(1, $first['next_cycle'], 'first admission starts at enrolled semester 1');
same(null, $first['minimum_date'], 'no arbitrary today/future restriction on first admission');
same(3, Policy::fromPlans([], 3, 8)['next_cycle'], 'direct admission into semester 3 is respected');
same(8, $first['remaining_cycles'], 'program coverage before admission');

$prior = [plan(1, 1, 1, '2026-12-07')];
$next = Policy::fromPlans($prior, 1, 8);
same(2, $next['next_cycle'], 'enrollment remains 1 but next plan is semester 2');
same(2, Policy::fromPlans($prior, 7, 8)['next_cycle'], 'history, not edited enrollment, controls continuation');
same(1, $next['previous_assignment_id'], 'continuation links to previous plan');
same('2027-01-01', $next['minimum_date'], 'entire previous semester month is reserved');
same(7, $next['remaining_cycles'], 'only one semester covered');
same([1], $next['covered_cycles'], 'coverage counts semesters, not template count');
same([], $next['duplicate_cycles'], 'normal history has no duplicates');
same(false, $next['is_complete'], 'semester 2 remains available');
same([], Policy::cycleErrors($next, 2), 'next cycle is allowed');
same(true, isset(Policy::cycleErrors($next, 1)['cycle_no']), 'same-template or different-template semester 1 rejected');
same(true, isset(Policy::cycleErrors($next, 3)['cycle_no']), 'skipping the next semester rejected');

$duplicates = [...$prior, plan(2, 1, 1, '2027-06-08')];
$state = Policy::fromPlans($duplicates, 1, 8);
same(2, $state['next_cycle'], 'legacy duplicate plans do not increment semester count twice');
same([1 => [1, 2]], $state['duplicate_cycles'], 'legacy duplicate IDs are reported');
same('2027-07-01', $state['minimum_date'], 'all legacy plan dates remain reserved until reviewed');
same(2, $state['previous_assignment_id'], 'latest plan at highest cycle is parent');
same($duplicates, [...$prior, plan(2, 1, 1, '2027-06-08')], 'policy never mutates legacy rows');

$bulk = Policy::fromPlans([plan(1, 1, 4, '2028-06-30')], 1, 8);
same(5, $bulk['next_cycle'], 'legacy bulk plan advances to first cycle after covered range');
same([1, 2, 3, 4], $bulk['covered_cycles'], 'legacy bulk coverage expanded');
same('2028-07-01', $bulk['minimum_date'], 'bulk date boundary respected');
$overlap = Policy::fromPlans([plan(1, 1, 4, '2028-06-30'), plan(2, 3, 5, '2028-12-15')], 1, 8);
same([3 => [1, 2], 4 => [1, 2]], $overlap['duplicate_cycles'], 'range overlap detected, not just duplicate starts');
same(6, $overlap['next_cycle'], 'overlapping legacy ranges never get regenerated');

$complete = Policy::fromPlans([plan(1, 1, 8, '2030-12-01')], 1, 8);
same(true, $complete['is_complete'], 'no semester 9 for an 8-semester program');
same(0, $complete['remaining_cycles'], 'zero remaining at program completion');
same(true, isset(Policy::cycleErrors($complete, 9)['cycle_no']), 'tampered completion POST rejected');
same(false, Policy::fromPlans($prior, 1, null)['is_complete'], 'unknown program length is not falsely capped at 1');

foreach ([['2028-02-29', '2028-03-01'], ['2027-01-31', '2027-02-01'], ['2026-12-31', '2027-01-01']] as [$last, $minimum]) {
    same($minimum, Policy::fromPlans([plan(1, 1, 1, $last)], 1, 8)['minimum_date'], "month boundary after {$last}");
}
same(null, Policy::fromPlans([plan(1, 1, 1, null)], 1, 8)['minimum_date'], 'dateless legacy history does not invent a boundary');

$min = $next['minimum_date'];
same([], Policy::dateErrors([2 => ['2027-01-01', '2027-02-16']], 2, 2, $min), 'boundary and future-year ISO dates accepted');
same([], Policy::dateErrors([1 => ['2026-09-16']], 1, 1, null), 'today is valid for first plan even with day greater than 12');
same([], Policy::dateErrors([2 => ['2026-09-16']], 2, 1, '2026-09-01'), 'today is allowed when outside earlier reserved months');
same([], Policy::dateErrors([2 => ['2027-01-15', '2027-01-15']], 2, 2, $min), 'same day within one semester is allowed');
same(true, isset(Policy::dateErrors([2 => ['2026-12-31']], 2, 1, $min)['installment_dates.2.0']), 'last day of earlier month rejected');
same(true, isset(Policy::dateErrors([2 => ['2026-09-16']], 2, 1, $min)['installment_dates.2.0']), 'today rejected with specific earlier-semester conflict');
same(true, isset(Policy::dateErrors([2 => ['2027-02-01', '2027-01-15']], 2, 2, $min)['installment_dates.2.1']), 'out-of-order installments rejected');
same(true, isset(Policy::dateErrors([2 => ['2027-02-29']], 2, 1, $min)['installment_dates.2.0']), 'impossible date rejected');
same([], Policy::dateErrors([2 => ['2028-02-29']], 2, 1, $min), 'leap date accepted');
same(true, isset(Policy::dateErrors([2 => ['16/02/2027']], 2, 1, $min)['installment_dates.2.0']), 'localized display value never misparsed as ISO');
same(true, isset(Policy::dateErrors([2 => ['2027-01-01']], 2, 2, $min)['installment_dates.2.1']), 'missing second installment date rejected');
same(true, isset(Policy::dateErrors([1 => ['2027-01-01']], 2, 1, $min)['installment_dates.2.0']), 'wrong semester date keys rejected');

$defaults = [2 => ['2026-12-15', '2027-01-20', '2027-02-29', '2027-03-01']];
same([2 => [null, '2027-01-20', null, '2027-03-01']], Policy::allowedDefaults($defaults, $min), 'invalid/reserved defaults cleared without shifting indexes');
same('2026-12-15', $defaults[2][0], 'stored session defaults remain unchanged');
same([2 => [null, '2027-01-20']], Policy::allowedDefaults([2 => [null, '2027-01-20']], null), 'blank defaults retain part position');

// Same expected cycle used for both concurrent requests. After first commit,
// the second must fail rather than silently creating semester 3.
$afterCommit = Policy::fromPlans([...$prior, plan(2, 2, 2, '2027-06-08')], 1, 8);
same(3, $afterCommit['next_cycle'], 'fresh form after semester 2 shows semester 3');
same(true, isset(Policy::cycleErrors($afterCommit, 2)['cycle_no']), 'stale/double submission rejected after state changes');
same([], Policy::cycleErrors($afterCommit, 3), 'fresh semester 3 form is allowed');

fwrite(STDOUT, "PASS: {$checks} fee-plan progression/date policy checks.\n");
