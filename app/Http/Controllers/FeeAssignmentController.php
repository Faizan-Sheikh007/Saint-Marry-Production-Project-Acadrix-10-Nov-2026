<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\BankAccount;
use App\Models\FeeAssignment;
use App\Models\FeeHead;
use App\Models\InstallmentDateDefault;
use App\Models\FeeTemplate;
use App\Models\Setting;
use App\Models\Student;
use App\Models\Voucher;
use App\Services\FeePlanProgression;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\URL;

class FeeAssignmentController extends Controller
{
    /**
     * Pick the active account that should be stamped onto new fee plans.
     * BankAccount::active() already puts the Settings "Default" account
     * first; when none is marked default, the first active account is used.
     */
    protected function automaticBankAccountId(): ?int
    {
        return BankAccount::active()->first()?->id;
    }

    /**
     * Step 2 of the admission wizard: assign the class/semester fee
     * template to the freshly-admitted student, with optional percentage
     * increase, discount, installment count, and exact installment due dates.
     */
    public function create(Request $request, Student $student)
    {
        $progression = app(FeePlanProgression::class)->forStudent($student);
        $programTotalCycles = $progression['total_cycles'];
        $alreadyCoveredUpTo = $progression['last_covered_cycle'];
        $defaultCycleNo = $progression['next_cycle'];
        $minimumDueDate = $progression['minimum_date'];

        $programId = (int) ($student->program_id ?: $student->schoolClass?->program_id);
        $matchesTargetCycle = fn (FeeTemplate $template) => $template->schoolClass
            && (int) $template->schoolClass->program_id === $programId
            && (int) $template->schoolClass->term_number === $defaultCycleNo;

        // Show every active fee template at admission time.  The old
        // query only returned global templates plus templates attached to the
        // student's exact Part/Semester, which made perfectly valid templates
        // from the same Program (or another class) disappear from the dropdown.
        //
        // We still keep the safest choices first: exact semester, same program,
        // global templates, then templates from other programs.  The dropdown
        // labels make the scope explicit so Accounts can intentionally choose a
        // different template when needed without anything being silently hidden.
        $templates = FeeTemplate::with(['items.feeHead', 'schoolClass.program'])
            ->where('is_active', true)
            ->get()
            ->sortBy(function (FeeTemplate $template) use ($programId, $matchesTargetCycle) {
                $rank = match (true) {
                    $matchesTargetCycle($template) => 0,
                    $template->schoolClass && (int) $template->schoolClass->program_id === $programId => 1,
                    is_null($template->school_class_id) => 2,
                    default => 3,
                };

                return sprintf('%d|%s', $rank, mb_strtolower($template->name));
            })
            ->values();

        $templateGroups = collect([
            "Recommended for {$progression['cycle_label']} {$defaultCycleNo}" => $templates->filter($matchesTargetCycle),
            'Other templates in this Program' => $templates->filter(
                fn (FeeTemplate $template) => $template->schoolClass
                    && (int) $template->schoolClass->program_id === $programId
                    && !$matchesTargetCycle($template)
            ),
            'General / All Programs templates' => $templates->filter(
                fn (FeeTemplate $template) => is_null($template->school_class_id)
            ),
            'Other active templates' => $templates->filter(
                fn (FeeTemplate $template) => $template->schoolClass
                    && (int) $template->schoolClass->program_id !== $programId
            ),
        ])->filter(fn ($group) => $group->isNotEmpty());

        $bankAccounts    = BankAccount::active();
        $automaticBankAccount = $bankAccounts->first();
        $maxInstallments = (int) Setting::get('installment_max', 5);
        $feeHeads        = FeeHead::where('is_active', true)->orderBy('name')->get();

        $outstandingDues = $this->outstandingDuesFor($student);

        // Prevent one-time template heads (Admission/Registration etc.) from
        // being auto-seeded again when this student already has that fee head
        // on an earlier voucher. The UI still allows an explicit manual change.
        $chargedFeeHeadIds = Voucher::where('student_id', $student->id)
            ->whereNotNull('fee_head_id')
            ->pluck('fee_head_id')
            ->map(fn ($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        // If the student was just admitted this request-cycle, the LMS
        // credentials flashed by StudentController::store() ride along here
        // (as hidden fields) so they can be stamped onto the installment
        // plan slip once this form is submitted.
        $lmsCredentials = session('credentials');

        // The redirect immediately after StudentController::store() marks this
        // request as the admission flow. Admission must create fees only for
        // the Part/Semester the student is presently enrolled in; generating
        // future semesters remains available later from the student profile.
        $isAdmission = $alreadyCoveredUpTo === 0
            && ($request->boolean('admission') || session()->has('credentials'));

        // Both New Fee Plan and legacy ?scope=all_cycles links now open
        // exactly the next cycle. Never reuse the admission semester schedule.
        $rawDefaultDates = InstallmentDateDefault::datesForStudentRange($student, $defaultCycleNo, $defaultCycleNo);
        $defaultInstallmentDates = FeePlanProgression::allowedDefaults($rawDefaultDates, $minimumDueDate);
        $blockedDefaultDateCount = collect($rawDefaultDates)->flatten()->filter()->count()
            - collect($defaultInstallmentDates)->flatten()->filter()->count();
        $defaultFirstDueDate = $defaultInstallmentDates[$defaultCycleNo][0] ?? '';

        // Only computed when the lookup above came back empty -- explains
        // *why* on the page itself instead of just showing "not configured".
        $defaultScheduleDiagnostics = empty($defaultInstallmentDates)
            ? InstallmentDateDefault::diagnose($student, $defaultCycleNo)
            : null;

        return view('fee-assignments.create', compact(
            'student', 'templates', 'templateGroups', 'bankAccounts', 'automaticBankAccount', 'maxInstallments', 'lmsCredentials',
            'feeHeads', 'progression', 'minimumDueDate', 'blockedDefaultDateCount', 'programTotalCycles', 'alreadyCoveredUpTo', 'outstandingDues',
            'defaultCycleNo', 'defaultInstallmentDates', 'defaultFirstDueDate',
            'defaultScheduleDiagnostics', 'chargedFeeHeadIds', 'isAdmission'
        ));
    }

    public function store(Request $request, Student $student)
    {
        // Empty-string amounts (a cleared override input) must become null,
        // not "", or the numeric|nullable rule below rejects them.
        if ($request->has('item_overrides')) {
            $request->merge([
                'item_overrides' => collect($request->input('item_overrides'))
                    ->map(fn ($v) => ($v === '' || $v === null) ? null : $v)
                    ->all(),
            ]);
        }

        $maxInstallments = (int) Setting::get('installment_max', 5);

        $validated = $request->validate([
            'fee_template_id'      => 'required|exists:fee_templates,id',
            'increase_amount'      => 'nullable|integer|min:0',
            'scholarship_amount'   => 'nullable|integer|min:0',
            'discount_amount'      => 'nullable|integer|min:0',
            'financial_aid_amount' => 'nullable|integer|min:0',
            // Accept the legacy bulk token, but always create one cycle below.
            'generate_scope'       => 'required|in:single,all_cycles',
            'admission_flow'       => 'nullable|boolean',
            // The cycle shown to the admin. Rechecked under a student row lock.
            'cycle_no'             => 'required|integer|min:1',
            // Parts *within one cycle* (e.g. 2 = Part 1 & Part 2 of the semester).
            // For "single" scope this doubles as the plain installment count, same as before.
            'installments'         => 'required|integer|min:1|max:' . max(1, $maxInstallments),
            'billing_mode'         => 'required|in:split,monthly_recurring',
            'installment_dates'               => 'nullable|array',
            'installment_dates.*'             => 'nullable|array',
            'installment_dates.*.*'           => 'nullable|date_format:Y-m-d',
            // first_due_date is derived from installment_dates, never trusted from the browser.
            'frequency_days'       => 'required|integer|min:1',
            'use_default_installment_dates' => 'nullable|boolean',
            'cycle_interval_days'  => 'nullable|integer|min:1|max:400',
            // Optional flat amount added on top of increase_amount for each
            // additional semester/year when generating the whole program at once
            // (e.g. Rs. 500 more per later semester).
            'cycle_increment_amount' => 'nullable|integer|min:0',
            'notes'                => 'nullable|string',
            // Fee heads the admin adds by hand, on top of the template — e.g. a
            // one-off charge the template doesn't have a line for yet.
            'manual_items'                  => 'nullable|array',
            'manual_items.*.fee_head_id'    => 'nullable|exists:fee_heads,id',
            'manual_items.*.label'          => 'nullable|string|max:255',
            'manual_items.*.amount'         => 'nullable|numeric|min:0.01',
            'manual_items.*.one_time'       => 'nullable|boolean',
            // Per-fee-head overrides of the template's default amount for this
            // specific plan — lets the person generating the plan edit any
            // head's default (Tuition, Library, Sports Fund, etc.) instead of
            // being locked into whatever the template was saved with.
            'item_overrides'                => 'nullable|array',
            'item_overrides.*'              => 'nullable|numeric|min:0',
            // Template fee heads are selectable per plan. The hidden flag lets
            // us distinguish an explicit "none selected" from older forms that
            // did not send fee-head selection controls at all.
            'manage_fee_heads'               => 'nullable|boolean',
            'selected_fee_head_ids'          => 'nullable|array',
            'selected_fee_head_ids.*'        => 'integer|exists:fee_heads,id',
            // New UI: every installment posts its own fee-head => amount map.
            // The amount is the exact pre-adjustment amount to charge in that
            // installment; no fee-head type is forced into a particular slot.
            'manage_installment_fee_heads'          => 'nullable|boolean',
            // New fee-plan screens submit installment values as the exact
            // amount the student must pay AFTER scholarship / financial aid.
            // Older/stale clients did not send this field and therefore keep
            // the legacy gross-before-relief interpretation.
            'installment_amount_basis'              => 'nullable|in:gross,net',
            'installment_fee_heads'                 => 'nullable|array',
            'installment_fee_heads.*'               => 'nullable|array',
            'installment_fee_heads.*.*'             => 'nullable|array',
            'installment_fee_heads.*.*.*'           => 'nullable|numeric|min:0.01',
            'installment_custom_items'              => 'nullable|array',
            'installment_custom_items.*'            => 'nullable|array',
            'installment_custom_items.*.*'          => 'nullable|array',
            'installment_custom_items.*.*.*'        => 'nullable|array',
            'installment_custom_items.*.*.*.fee_head_id' => 'nullable|exists:fee_heads,id',
            'installment_custom_items.*.*.*.label'       => 'nullable|string|max:255',
            'installment_custom_items.*.*.*.amount'      => 'nullable|numeric|min:0.01',
            // Roll the student's currently-outstanding balance (from fee plans
            // outside this one) into this plan's first installment.
            'carry_forward_dues'   => 'nullable|boolean',
            'lms_login_email'            => 'nullable|email|max:255',
            'lms_login_password_plain'   => 'nullable|string|max:255',
        ]);

        // Bank selection is intentionally automatic: use the account marked
        // Default in Settings, otherwise the first active account. This is a
        // server-side decision so the operator never has to choose a number
        // from the fee-plan form and a stale browser form cannot override it.
        $validated['bank_account_id'] = $this->automaticBankAccountId();

        $template = FeeTemplate::with('items.feeHead')->findOrFail($validated['fee_template_id']);

        $templateFeeHeadIds = $template->items->pluck('fee_head_id')->filter()->map(fn ($id) => (int) $id)->unique()->values();
        $selectedFeeHeadIds = $request->boolean('manage_fee_heads')
            ? collect($validated['selected_fee_head_ids'] ?? [])->map(fn ($id) => (int) $id)->intersect($templateFeeHeadIds)->values()->all()
            : $templateFeeHeadIds->all();

        // The same progression policy applies to admission, New Fee Plan,
        // old all_cycles URLs, and renewals. A template change never restarts it.
        $validated['generate_scope'] = 'single';
        if ($request->boolean('admission_flow')) {
            $validated['increase_amount'] = 0;
            $validated['discount_amount'] = 0;
        }
        $partsPerCycle = (int) $validated['installments'];
        $cycleStart = (int) $validated['cycle_no'];
        $cycleEnd = $cycleStart;
        $progression = app(FeePlanProgression::class)->assertNextCycle($student, $template, $cycleStart);

        $cycleInterval   = (int) ($validated['cycle_interval_days'] ?? 180);
        $cycleIncAmount  = 0.0; // This request creates only one semester/year.

        $incAmount         = (float) ($validated['increase_amount'] ?? 0);
        $scholarshipAmount = (float) ($validated['scholarship_amount'] ?? 0);
        $discAmount        = (float) ($validated['discount_amount'] ?? 0);
        $aidAmount         = (float) ($validated['financial_aid_amount'] ?? 0);

        // Vouchers have one legacy discount_amount column for all relief. Keep
        // the plan-level scholarship and financial-aid values separate for
        // reporting, while applying their combined relief to voucher payables.
        $voucherDiscountAmount = $discAmount + $scholarshipAmount;

        $useDefaultInstallmentDates = !$request->exists('use_default_installment_dates')
            || $request->boolean('use_default_installment_dates');
        $defaultInstallmentDates = $useDefaultInstallmentDates
            ? InstallmentDateDefault::datesForStudentRange($student, $cycleStart, $cycleEnd)
            : [];
        $defaultInstallmentDates = FeePlanProgression::allowedDefaults($defaultInstallmentDates, $progression['minimum_date']);
        $enteredInstallmentDates = $this->normalizeInstallmentDateOverrides(
            $validated['installment_dates'] ?? [],
            $cycleStart,
            $cycleEnd,
            $partsPerCycle
        );
        $dueDateOverrides = $this->mergeInstallmentDateOverrides($defaultInstallmentDates, $enteredInstallmentDates);

        $missingDate = $this->firstMissingInstallmentDate($dueDateOverrides, $cycleStart, $cycleEnd, $partsPerCycle);
        if ($missingDate) {
            return back()->withInput()->withErrors([
                'installment_dates' => "Choose a due date from the calendar for {$template->cycleLabel()} {$missingDate['cycle']}, installment {$missingDate['part']}.",
            ]);
        }

        app(FeePlanProgression::class)->assertDates($dueDateOverrides, $cycleStart, $partsPerCycle, $progression['minimum_date']);

        // The posted/default installment calendar is authoritative. first_due_date
        // remains stored for compatibility, but it is derived from the first
        // selected installment date instead of generating any fallback dates.
        $due = Carbon::parse($dueDateOverrides[$cycleStart][0]);

        $slots = $this->buildSlots($template, $cycleStart, $cycleEnd, $partsPerCycle, $due,
            (int) $validated['frequency_days'], $cycleInterval, $dueDateOverrides);

        $manualItems     = $this->normalizeManualItems($validated['manual_items'] ?? []);
        // Keyed by fee_head_id => overridden amount. Only heads the person
        // actually edited/left non-empty are included; anything missing
        // just falls back to the template's own default amount.
        $itemOverrides   = collect($validated['item_overrides'] ?? [])
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->map(fn ($v) => (float) $v)
            ->all();
        $carriedForward  = !empty($validated['carry_forward_dues']) ? $this->outstandingDuesFor($student) : 0.0;

        $useInstallmentSpecificHeads = $request->boolean('manage_installment_fee_heads');
        $installmentAmountsAreNet = ($validated['installment_amount_basis'] ?? 'gross') === 'net';
        $installmentFeeHeads = $useInstallmentSpecificHeads
            ? $this->normalizeInstallmentFeeHeads($validated['installment_fee_heads'] ?? [], $slots)
            : [];
        $installmentCustomItems = $useInstallmentSpecificHeads
            ? $this->normalizeInstallmentCustomItems($validated['installment_custom_items'] ?? [], $slots)
            : [];

        if ($useInstallmentSpecificHeads) {
            $emptySlot = $this->firstEmptyInstallmentSlot($slots, $installmentFeeHeads, $installmentCustomItems, $carriedForward);
            if ($emptySlot) {
                return back()->withInput()->withErrors([
                    'installment_fee_heads' => "Choose at least one fee head or custom item for {$emptySlot['label']}.",
                ]);
            }
        }

        $hasBillableTemplateHead = $template->items
            ->whereIn('fee_head_id', $selectedFeeHeadIds)
            ->contains(function ($item) use ($itemOverrides) {
                return array_key_exists($item->fee_head_id, $itemOverrides) || !is_null($item->amount);
            });

        if (!$useInstallmentSpecificHeads && !$hasBillableTemplateHead && empty($manualItems) && $carriedForward <= 0) {
            return back()->withInput()->withErrors([
                'selected_fee_head_ids' => 'Select at least one priced fee head, add a custom fee item, or carry forward outstanding dues before generating the plan.',
            ]);
        }

        $feeAssignment = null;
        $totalAmount   = 0;

        DB::transaction(function () use (
            $template, $slots, $partsPerCycle, $cycleStart, $cycleEnd, $cycleInterval,
            $incAmount, $scholarshipAmount, $discAmount, $voucherDiscountAmount, $aidAmount, $cycleIncAmount, $due, $validated, $student, $manualItems,
            $carriedForward, $itemOverrides, $selectedFeeHeadIds, $useInstallmentSpecificHeads,
            $installmentAmountsAreNet, $installmentFeeHeads, $installmentCustomItems, $dueDateOverrides, &$feeAssignment, &$totalAmount
        ) {
            Student::whereKey($student->id)->lockForUpdate()->firstOrFail()->assertActiveEnrollment();
            $progression = app(FeePlanProgression::class)->assertNextCycle($student, $template, $cycleStart);
            app(FeePlanProgression::class)->assertDates($dueDateOverrides, $cycleStart, $partsPerCycle, $progression['minimum_date']);

            $feeAssignment = FeeAssignment::create([
                'student_id'          => $student->id,
                'parent_fee_assignment_id' => $progression['previous_assignment_id'],
                'fee_template_id'     => $template->id,
                'bank_account_id'     => $validated['bank_account_id'] ?? null,
                'increase_amount'         => $incAmount,
                'scholarship_amount'      => $scholarshipAmount,
                'discount_amount'         => $discAmount,
                'financial_aid_amount'    => $aidAmount,
                'cycle_increment_amount'  => $cycleIncAmount,
                'installments'        => count($slots),
                'parts_per_cycle'     => $partsPerCycle,
                'cycle_start'         => $cycleStart,
                'cycle_end'           => $cycleEnd,
                'billing_mode'        => $validated['billing_mode'],
                'months'              => array_column($slots, 'label'),
                'first_due_date'      => $slots[0]['due_date'] ?? $due,
                'frequency_days'      => $validated['frequency_days'],
                'cycle_interval_days' => $cycleInterval,
                'batch_prefix'        => FeeAssignment::generateBatchPrefix(),
                'notes'               => $validated['notes'] ?? null,
                'manual_items'        => $useInstallmentSpecificHeads ? ($installmentCustomItems ?: null) : ($manualItems ?: null),
                'carried_forward_amount' => $carriedForward,
                'carried_forward_note'   => $carriedForward > 0 ? "Outstanding balance from prior fee plans as of " . now()->toDateString() : null,
                'lms_login_email'          => $validated['lms_login_email'] ?? null,
                'lms_login_password_plain' => $validated['lms_login_password_plain'] ?? null,
            ]);

            if ($useInstallmentSpecificHeads) {
                $totalAmount = $this->generateInstallmentSpecificVouchers(
                    $template, $student, $feeAssignment, $slots, $installmentFeeHeads, $installmentCustomItems,
                    $incAmount, $voucherDiscountAmount, $aidAmount, $cycleIncAmount, $cycleStart,
                    $installmentAmountsAreNet
                );
            } else {
                $totalAmount = $this->generateVouchers(
                    $template, $student, $feeAssignment, $slots, $partsPerCycle,
                    $validated['billing_mode'], $incAmount, $voucherDiscountAmount, $aidAmount, $cycleIncAmount, $cycleStart,
                    $itemOverrides, $selectedFeeHeadIds
                );
                $totalAmount += $this->applyManualItems($student, $feeAssignment, $slots, $manualItems, $partsPerCycle, $validated['billing_mode']);
            }
            $totalAmount += $this->applyCarriedForwardDues($student, $feeAssignment, $slots, $carriedForward);

            $feeAssignment->update(['total_amount' => $totalAmount]);

            // Apply carried student credit only after every charge/discount for this semester exists.
            // This nested transaction shares the generation transaction: allocation failure rolls
            // the new plan back instead of leaving it generated but unpaid. The voucher observer
            // remains a safe fallback for other creation paths; spent credit cannot be spent twice.
            $this->applyCreditToGeneratedSemester($student);

            $cycles = $cycleEnd - $cycleStart + 1;
            $extra  = [];
            if ($useInstallmentSpecificHeads && $installmentCustomItems) {
                $extra[] = collect($installmentCustomItems)->sum(fn ($parts) => collect($parts)->sum(fn ($rows) => count($rows ?? []))) . ' installment-specific extra item(s)';
            } elseif ($manualItems) {
                $extra[] = count($manualItems) . ' manual item(s)';
            }
            if ($carriedForward > 0) $extra[] = 'Rs. ' . number_format($carriedForward, 0) . ' carried forward';
            $extraNote = $extra ? ' + ' . implode(', ', $extra) : '';

            AuditLog::record('created', 'FeeAssignment', $feeAssignment->id,
                "Assigned {$template->name} fee plan to {$student->name} ({$cycles} cycle(s), " . count($slots) . " installment(s)){$extraNote}");
        });

        return redirect()->route('fee-assignments.show', $feeAssignment)
            ->with('success', 'Fee plan for ' . $feeAssignment->cycleRangeLabel() . ' generated. Previous semester plans were not changed.');
    }

    /**
     * Renew form: roll a plan forward into the next semester(s)/year(s),
     * optionally increasing the fee by a percentage.
     */
    public function renew(FeeAssignment $feeAssignment)
    {
        $feeAssignment->load('student', 'feeTemplate');
        $template = $feeAssignment->feeTemplate;

        abort_if(!$template, 404, 'This plan has no fee template to renew from.');

        $progression = app(FeePlanProgression::class)->forStudent($feeAssignment->student, $template);
        if ($progression['is_complete']) {
            return redirect()->route('fee-assignments.create', $feeAssignment->student);
        }
        $remainingCycles = 1;
        $minimumDueDate = $progression['minimum_date'];

        $bankAccounts    = BankAccount::active();
        // Keep the prior plan's account when it has one; otherwise pick the
        // current automatic/default active account for the renewal.
        $automaticBankAccount = $feeAssignment->bankAccount ?: $bankAccounts->first();
        $maxInstallments = (int) Setting::get('installment_max', 5);
        $feeHeads        = FeeHead::where('is_active', true)->orderBy('name')->get();
        $renewSelectedFeeHeadIds = $feeAssignment->vouchersForCycle($feeAssignment->cycle_end)
            ->pluck('fee_head_id')->filter()->map(fn ($id) => (int) $id)->unique()->values()->all();

        $nextCycle = $progression['next_cycle'];
        $rawDefaultDates = InstallmentDateDefault::datesForStudentRange($feeAssignment->student, $nextCycle, $nextCycle);
        $defaultInstallmentDates = FeePlanProgression::allowedDefaults($rawDefaultDates, $minimumDueDate);
        $blockedDefaultDateCount = collect($rawDefaultDates)->flatten()->filter()->count()
            - collect($defaultInstallmentDates)->flatten()->filter()->count();
        $defaultFirstDueDate = $defaultInstallmentDates[$nextCycle][0] ?? '';

        $defaultScheduleDiagnostics = empty($defaultInstallmentDates)
            ? InstallmentDateDefault::diagnose($feeAssignment->student, $nextCycle)
            : null;

        return view('fee-assignments.renew', compact(
            'feeAssignment', 'template', 'remainingCycles', 'bankAccounts', 'automaticBankAccount', 'maxInstallments',
            'defaultInstallmentDates', 'defaultFirstDueDate', 'nextCycle', 'defaultScheduleDiagnostics',
            'feeHeads', 'renewSelectedFeeHeadIds', 'progression', 'minimumDueDate', 'blockedDefaultDateCount'
        ));
    }

    public function renewStore(Request $request, FeeAssignment $feeAssignment)
    {
        if ($request->has('item_overrides')) {
            $request->merge([
                'item_overrides' => collect($request->input('item_overrides'))
                    ->map(fn ($v) => ($v === '' || $v === null) ? null : $v)
                    ->all(),
            ]);
        }

        $feeAssignment->load('student', 'feeTemplate.items.feeHead');
        $template = $feeAssignment->feeTemplate;
        abort_if(!$template, 404, 'This plan has no fee template to renew from.');

        $maxInstallments = (int) Setting::get('installment_max', 5);

        $validated = $request->validate([
            'cycles_to_generate'   => 'required|integer|in:1',
            'cycle_no'             => 'required|integer|min:1',
            'increment_amount'     => 'nullable|integer|min:0',
            'scholarship_amount'    => 'nullable|integer|min:0',
            'discount_amount'       => 'nullable|integer|min:0',
            'financial_aid_amount'  => 'nullable|integer|min:0',
            'installments'         => 'required|integer|min:1|max:' . max(1, $maxInstallments),
            'billing_mode'         => 'required|in:split,monthly_recurring',
            // first_due_date is derived from installment_dates, never trusted from the browser.
            'frequency_days'       => 'required|integer|min:1',
            'use_default_installment_dates' => 'nullable|boolean',
            'cycle_interval_days'  => 'nullable|integer|min:1|max:400',
            'notes'                => 'nullable|string',
            'installment_dates'               => 'nullable|array',
            'installment_dates.*'             => 'nullable|array',
            'installment_dates.*.*'           => 'nullable|date_format:Y-m-d',
            'manual_items'                  => 'nullable|array',
            'manual_items.*.fee_head_id'    => 'nullable|exists:fee_heads,id',
            'manual_items.*.label'          => 'nullable|string|max:255',
            'manual_items.*.amount'         => 'nullable|numeric|min:0.01',
            'manual_items.*.one_time'       => 'nullable|boolean',
            // Editable per-fee-head amounts for this renewal, same as at
            // initial plan creation — lets the person adjust any head's
            // amount (Tuition, Library, Sports Fund, etc.) for the new cycle.
            'item_overrides'                => 'nullable|array',
            'item_overrides.*'              => 'nullable|numeric|min:0',
            'manage_fee_heads'               => 'nullable|boolean',
            'selected_fee_head_ids'          => 'nullable|array',
            'selected_fee_head_ids.*'        => 'integer|exists:fee_heads,id',
            'manage_installment_fee_heads'          => 'nullable|boolean',
            'installment_amount_basis'              => 'nullable|in:gross,net',
            'installment_fee_heads'                 => 'nullable|array',
            'installment_fee_heads.*'               => 'nullable|array',
            'installment_fee_heads.*.*'             => 'nullable|array',
            'installment_fee_heads.*.*.*'           => 'nullable|numeric|min:0.01',
            'installment_custom_items'              => 'nullable|array',
            'installment_custom_items.*'            => 'nullable|array',
            'installment_custom_items.*.*'          => 'nullable|array',
            'installment_custom_items.*.*.*'        => 'nullable|array',
            'installment_custom_items.*.*.*.fee_head_id' => 'nullable|exists:fee_heads,id',
            'installment_custom_items.*.*.*.label'       => 'nullable|string|max:255',
            'installment_custom_items.*.*.*.amount'      => 'nullable|numeric|min:0.01',
            'carry_forward_dues'   => 'nullable|boolean',
        ]);

        $validated['bank_account_id'] = $feeAssignment->bank_account_id ?: $this->automaticBankAccountId();

        $templateFeeHeadIds = $template->items->pluck('fee_head_id')->filter()->map(fn ($id) => (int) $id)->unique()->values();
        $selectedFeeHeadIds = $request->boolean('manage_fee_heads')
            ? collect($validated['selected_fee_head_ids'] ?? [])->map(fn ($id) => (int) $id)->intersect($templateFeeHeadIds)->values()->all()
            : $templateFeeHeadIds->all();

        $partsPerCycle = (int) $validated['installments'];
        $cycleStart    = (int) $validated['cycle_no'];
        $cycleEnd      = $cycleStart;
        $progression = app(FeePlanProgression::class)->assertNextCycle($feeAssignment->student, $template, $cycleStart);
        $cycleInterval = (int) ($validated['cycle_interval_days'] ?? $feeAssignment->cycle_interval_days ?? 180);

        // Stack the new increase on top of whatever flat increase was already
        // applied on the plan being renewed, so repeated renewals add up
        // correctly (e.g. Rs. 500 already applied + Rs. 300 more now = Rs. 800).
        $incomingAmount = (float) ($validated['increment_amount'] ?? 0);
        $prevAmount     = (float) $feeAssignment->increase_amount;
        $incAmount      = round($prevAmount + $incomingAmount, 2);

        $scholarshipAmount = (float) ($validated['scholarship_amount'] ?? $feeAssignment->scholarship_amount ?? 0);
        $discAmount = (float) ($validated['discount_amount'] ?? $feeAssignment->discount_amount ?? 0);
        $aidAmount  = (float) ($validated['financial_aid_amount'] ?? $feeAssignment->financial_aid_amount ?? 0);
        $voucherDiscountAmount = $discAmount + $scholarshipAmount;

        $useDefaultInstallmentDates = !$request->exists('use_default_installment_dates')
            || $request->boolean('use_default_installment_dates');
        $defaultInstallmentDates = $useDefaultInstallmentDates
            ? InstallmentDateDefault::datesForStudentRange($feeAssignment->student, $cycleStart, $cycleEnd)
            : [];
        $defaultInstallmentDates = FeePlanProgression::allowedDefaults($defaultInstallmentDates, $progression['minimum_date']);
        $enteredInstallmentDates = $this->normalizeInstallmentDateOverrides(
            $validated['installment_dates'] ?? [],
            $cycleStart,
            $cycleEnd,
            $partsPerCycle
        );
        $dueDateOverrides = $this->mergeInstallmentDateOverrides($defaultInstallmentDates, $enteredInstallmentDates);

        $missingDate = $this->firstMissingInstallmentDate($dueDateOverrides, $cycleStart, $cycleEnd, $partsPerCycle);
        if ($missingDate) {
            return back()->withInput()->withErrors([
                'installment_dates' => "Choose a due date from the calendar for {$template->cycleLabel()} {$missingDate['cycle']}, installment {$missingDate['part']}.",
            ]);
        }

        app(FeePlanProgression::class)->assertDates($dueDateOverrides, $cycleStart, $partsPerCycle, $progression['minimum_date']);

        $due = Carbon::parse($dueDateOverrides[$cycleStart][0]);

        $slots = $this->buildSlots($template, $cycleStart, $cycleEnd, $partsPerCycle, $due,
            (int) $validated['frequency_days'], $cycleInterval, $dueDateOverrides);

        $student        = $feeAssignment->student;
        $manualItems    = $this->normalizeManualItems($validated['manual_items'] ?? []);
        $carriedForward = !empty($validated['carry_forward_dues']) ? $this->outstandingDuesFor($student) : 0.0;
        $itemOverrides  = collect($validated['item_overrides'] ?? [])
            ->filter(fn ($v) => $v !== null && $v !== '')
            ->map(fn ($v) => (float) $v)
            ->all();

        $useInstallmentSpecificHeads = $request->boolean('manage_installment_fee_heads');
        $installmentAmountsAreNet = ($validated['installment_amount_basis'] ?? 'gross') === 'net';
        $installmentFeeHeads = $useInstallmentSpecificHeads
            ? $this->normalizeInstallmentFeeHeads($validated['installment_fee_heads'] ?? [], $slots)
            : [];
        $installmentCustomItems = $useInstallmentSpecificHeads
            ? $this->normalizeInstallmentCustomItems($validated['installment_custom_items'] ?? [], $slots)
            : [];

        if ($useInstallmentSpecificHeads) {
            $emptySlot = $this->firstEmptyInstallmentSlot($slots, $installmentFeeHeads, $installmentCustomItems, $carriedForward);
            if ($emptySlot) {
                return back()->withInput()->withErrors([
                    'installment_fee_heads' => "Choose at least one fee head or custom item for {$emptySlot['label']}.",
                ]);
            }
        }

        $hasBillableTemplateHead = $template->items
            ->whereIn('fee_head_id', $selectedFeeHeadIds)
            ->contains(function ($item) use ($itemOverrides) {
                return array_key_exists($item->fee_head_id, $itemOverrides) || !is_null($item->amount);
            });

        if (!$useInstallmentSpecificHeads && !$hasBillableTemplateHead && empty($manualItems) && $carriedForward <= 0) {
            return back()->withInput()->withErrors([
                'selected_fee_head_ids' => 'Select at least one priced fee head, add a custom fee item, or carry forward outstanding dues before generating the renewed plan.',
            ]);
        }

        $newAssignment = null;
        $totalAmount   = 0;

        DB::transaction(function () use (
            $template, $feeAssignment, $student, $slots, $partsPerCycle, $cycleStart, $cycleEnd, $cycleInterval,
            $incAmount, $scholarshipAmount, $discAmount, $voucherDiscountAmount, $aidAmount, $due, $validated, $incomingAmount, $manualItems, $carriedForward,
            $itemOverrides, $selectedFeeHeadIds, $useInstallmentSpecificHeads,
            $installmentAmountsAreNet, $installmentFeeHeads, $installmentCustomItems, $dueDateOverrides, &$newAssignment, &$totalAmount
        ) {
            Student::whereKey($student->id)->lockForUpdate()->firstOrFail()->assertActiveEnrollment();
            $progression = app(FeePlanProgression::class)->assertNextCycle($student, $template, $cycleStart);
            app(FeePlanProgression::class)->assertDates($dueDateOverrides, $cycleStart, $partsPerCycle, $progression['minimum_date']);

            $newAssignment = FeeAssignment::create([
                'student_id'               => $feeAssignment->student_id,
                'fee_template_id'          => $template->id,
                'parent_fee_assignment_id' => $progression['previous_assignment_id'],
                'bank_account_id'          => $validated['bank_account_id'] ?? $feeAssignment->bank_account_id,
                'increase_amount'          => $incAmount,
                'scholarship_amount'       => $scholarshipAmount,
                'discount_amount'          => $discAmount,
                'financial_aid_amount'     => $aidAmount,
                'installments'             => count($slots),
                'parts_per_cycle'          => $partsPerCycle,
                'cycle_start'              => $cycleStart,
                'cycle_end'                => $cycleEnd,
                'billing_mode'             => $validated['billing_mode'],
                'months'                   => array_column($slots, 'label'),
                'first_due_date'           => $slots[0]['due_date'] ?? $due,
                'frequency_days'           => $validated['frequency_days'],
                'cycle_interval_days'      => $cycleInterval,
                'batch_prefix'             => FeeAssignment::generateBatchPrefix(),
                'notes'                    => $validated['notes'] ?? "Renewed from plan #{$feeAssignment->id}",
                'manual_items'             => $useInstallmentSpecificHeads ? ($installmentCustomItems ?: null) : ($manualItems ?: null),
                'carried_forward_amount'   => $carriedForward,
                'carried_forward_note'     => $carriedForward > 0 ? "Outstanding balance from prior fee plans as of " . now()->toDateString() : null,
            ]);

            if ($useInstallmentSpecificHeads) {
                $totalAmount = $this->generateInstallmentSpecificVouchers(
                    $template, $student, $newAssignment, $slots, $installmentFeeHeads, $installmentCustomItems,
                    $incAmount, $voucherDiscountAmount, $aidAmount, 0, $cycleStart,
                    $installmentAmountsAreNet
                );
            } else {
                $totalAmount = $this->generateVouchers(
                    $template, $student, $newAssignment, $slots, $partsPerCycle,
                    $validated['billing_mode'], $incAmount, $voucherDiscountAmount, $aidAmount, 0, $cycleStart,
                    $itemOverrides, $selectedFeeHeadIds
                );
                $totalAmount += $this->applyManualItems($student, $newAssignment, $slots, $manualItems, $partsPerCycle, $validated['billing_mode']);
            }
            $totalAmount += $this->applyCarriedForwardDues($student, $newAssignment, $slots, $carriedForward);

            $newAssignment->update(['total_amount' => $totalAmount]);

            // Apply carried student credit only after every charge/discount for this semester exists.
            // This nested transaction shares the generation transaction: allocation failure rolls
            // the new plan back instead of leaving it generated but unpaid. The voucher observer
            // remains a safe fallback for other creation paths; spent credit cannot be spent twice.
            $this->applyCreditToGeneratedSemester($student);

            AuditLog::record('created', 'FeeAssignment', $newAssignment->id,
                "Renewed {$template->name} fee plan for {$feeAssignment->student->name} " .
                "({$newAssignment->cycleRangeLabel()}, +Rs. " . number_format($incomingAmount, 2) . " over the previous plan)");
        });

        return redirect()->route('fee-assignments.show', $newAssignment)
            ->with('success', 'Renewed! The plan for ' . $newAssignment->cycleRangeLabel() . ' has been generated.');
    }

    private function applyCreditToGeneratedSemester(Student $student): void
    {
        $current = Student::whereKey($student->id)->lockForUpdate()->firstOrFail();
        $current->assertActiveEnrollment();
        if (\App\Services\CreditAllocator::minor((string) ($current->credit_balance ?? '0')) === 0) return;
        $actor = auth('accounts')->user()?->name ?? auth()->user()?->name ?? 'System';
        app(\App\Services\StudentBalanceService::class)->applyAvailable($current, $actor);
    }

    public function show(FeeAssignment $feeAssignment)
    {
        $feeAssignment->load('student.schoolClass', 'feeTemplate', 'bankAccount', 'parentAssignment', 'renewals');
        $groups = $feeAssignment->installmentGroups();
        $feeHeads = FeeHead::where('is_active', true)->orderBy('name')->get();
        return view('fee-assignments.show', compact('feeAssignment', 'groups', 'feeHeads'));
    }

    /** The printable / downloadable installment plan, themed to the institution's branding. */
    public function plan(FeeAssignment $feeAssignment)
    {
        $feeAssignment->load('student.schoolClass', 'student.program.institution', 'student.academicYear', 'feeTemplate', 'bankAccount');
        // Group by voucher number rather than installment number here. A
        // single installment may intentionally have two challans after
        // Accounts splits it into a desired amount + remaining amount.
        $groups       = $feeAssignment->challanGroups();
        $bankAccounts = $feeAssignment->bankAccount ? collect([$feeAssignment->bankAccount]) : BankAccount::active();
        $qrUrl        = $this->qrUrlFor($feeAssignment);
        $feeNote      = \App\Models\Setting::get('fee_summary_note');
        $declaration  = \App\Models\Setting::get('fee_summary_declaration');

        return view('fee-assignments.plan', compact('feeAssignment', 'groups', 'bankAccounts', 'qrUrl', 'feeNote', 'declaration'));
    }

    /**
     * Compact, single-page "Fee Summary" slip — student + father/guardian +
     * session header, one row per installment with a running semester
     * balance, tuition vs. other-dues split, and scholarship/financial aid
     * called out separately, styled to the institution's Acadrix theme
     * instead of a plain office printout.
     *
     * Pass $cycle to scope this to one semester/year of the plan instead of
     * the whole thing (used by the "Download Summary" button on each
     * semester card).
     */
    public function summary(FeeAssignment $feeAssignment, ?int $cycle = null)
    {
        return app(\App\Services\FeePlanSummary::class)->render($feeAssignment, $cycle);
    }

    /** Signed, unauthenticated verification link encoded in the plan's QR code. */
    public function verify(Request $request, FeeAssignment $feeAssignment)
    {
        abort_unless($request->hasValidSignature(), 403, 'This QR code is invalid or has expired.');

        $feeAssignment->load('student.schoolClass', 'feeTemplate');
        return view('fee-assignments.verify', compact('feeAssignment'));
    }

    protected function qrUrlFor(FeeAssignment $feeAssignment): string
    {
        $lastDue = $feeAssignment->vouchers()->max('due_date');
        $expiresAt = $lastDue ? Carbon::parse($lastDue)->addMonths(2) : now()->addYear();

        return URL::temporarySignedRoute(
            'fee-assignments.verify',
            $expiresAt,
            ['feeAssignment' => $feeAssignment->id]
        );
    }

    /**
     * Split a monetary amount across parts in cents so the parts always add
     * back to the exact template amount (no rounding loss/gain).
     */
    protected function splitAmountAcrossParts(float $amount, int $parts, int $part): float
    {
        $parts = max(1, $parts);
        $part = max(1, min($parts, $part));
        $totalCents = (int) round($amount * 100);
        $sign = $totalCents < 0 ? -1 : 1;
        $totalCents = abs($totalCents);
        $base = intdiv($totalCents, $parts);
        $remainder = $totalCents - ($base * $parts);
        $partCents = $base + ($part <= $remainder ? 1 : 0);

        return ($partCents * $sign) / 100;
    }

    /**
     * Build the ordered list of installment "slots" that a plan will contain,
     * one per cycle x part, each with its own due date and a human label.
     * @return array<int, array{installment_no:int, cycle_no:int, part_no:int, due_date:Carbon, label:string}>
     */
    protected function buildSlots(
        FeeTemplate $template, int $cycleStart, int $cycleEnd, int $partsPerCycle,
        Carbon $firstDue, int $frequencyDays, int $cycleIntervalDays,
        array $dueDateOverrides = []
    ): array {
        $cycleLabel = $template->cycleLabel();

        $slots = [];
        $i = 1;
        for ($cycle = $cycleStart; $cycle <= $cycleEnd; $cycle++) {
            $cycleAnchor = $firstDue->copy()->addDays($cycleIntervalDays * ($cycle - $cycleStart));
            $cycleDefaultDates = array_values($dueDateOverrides[$cycle] ?? []);
            $previousDueDate = null;

            for ($part = 1; $part <= $partsPerCycle; $part++) {
                if (!empty($cycleDefaultDates[$part - 1])) {
                    $dueDate = Carbon::parse($cycleDefaultDates[$part - 1]);
                } elseif ($previousDueDate) {
                    // If a default schedule supplies fewer dates than the
                    // selected installment count, continue forward from the
                    // last configured date instead of jumping back to the
                    // generic cycle anchor.
                    $dueDate = $previousDueDate->copy()->addDays($frequencyDays);
                } else {
                    $dueDate = $cycleAnchor->copy();
                }
                $previousDueDate = $dueDate->copy();

                if ($partsPerCycle > 1) {
                    $label = "{$cycleLabel} {$cycle} - Part {$part}";
                } else {
                    $label = "{$cycleLabel} {$cycle}";
                }

                $slots[] = [
                    'installment_no' => $i,
                    'cycle_no'       => $cycle,
                    'part_no'        => $part,
                    'due_date'       => $dueDate,
                    'label'          => $label,
                ];
                $i++;
            }
        }

        return $slots;
    }

    /**
     * Create vouchers for every slot from the Fee Template amounts. Every fee
     * head that applies to a cycle is divided across that cycle's selected
     * installment dates. One-time heads apply only to the first generated cycle;
     * per-cycle and recurring heads apply to each cycle. The legacy
     * monthly_recurring mode is retained only for older callers.
     *
     * $incAmount / $discAmount / $aidAmount are flat Rupee amounts entered
     * for the plan (applied per semester/year, same as before when they were
     * percentages), not percentages — each is distributed across the
     * template's fee-head lines in proportion to that line's own base
     * amount, so a head that makes up e.g. 80% of the template's total also
     * carries 80% of the increase/discount/aid, instead of every head being
     * bumped by an identical flat figure regardless of its size.
     *
     * @return float total payable amount across every slot generated.
     */
    protected function generateVouchers(
        FeeTemplate $template, Student $student, FeeAssignment $feeAssignment, array $slots,
        int $partsPerCycle, string $billingMode, float $incAmount, float $discAmount, float $aidAmount,
        float $cycleIncrementAmount, int $cycleStart, array $itemOverrides = [], ?array $selectedFeeHeadIds = null
    ): float {
        $totalAmount = 0;
        $totalSlots  = count($slots);

        // A fee template is now a catalogue/default, not a forced set of
        // charges. New forms send the exact heads the admin selected; null is
        // retained as a backwards-compatible "all template heads" fallback.
        $selectedTemplateItems = is_null($selectedFeeHeadIds)
            ? $template->items
            : $template->items->filter(fn ($item) => in_array((int) $item->fee_head_id, $selectedFeeHeadIds, true));

        // Resolve each item's effective default: the person's edited amount
        // (from item_overrides at plan-creation time) if they set one,
        // otherwise the template's own saved default for that fee head.
        $effectiveAmount = fn ($item) => array_key_exists($item->fee_head_id, $itemOverrides)
            ? $itemOverrides[$item->fee_head_id]
            : (is_null($item->amount) ? null : (float) $item->amount);

        // Weight of each fee-head line within the template's per-cycle total,
        // used to fairly share the flat increase/discount/aid amounts across
        // lines of very different sizes (e.g. Tuition vs. a small Library fee).
        $templateTotalBase = (float) $selectedTemplateItems->sum(fn ($item) => $effectiveAmount($item) ?? 0);

        // Snapshot one-time history before creating any voucher in this batch.
        // This lets a one-time head be split across multiple installments in the
        // first cycle without installment #2 seeing installment #1 as "history".
        $oneTimeChargedBefore = $selectedTemplateItems
            ->filter(fn ($item) => ($item->feeHead->type ?? 'recurring') === 'one_time')
            ->mapWithKeys(fn ($item) => [
                (int) $item->fee_head_id => Voucher::where('student_id', $student->id)
                    ->where('fee_head_id', $item->fee_head_id)
                    ->exists(),
            ])
            ->all();

        foreach ($slots as $slot) {
            // One voucher number per installment slot — every fee-head line
            // generated below for this slot shares it, so the parent/bank
            // only ever sees a single number for that installment instead
            // of one per fee head.
            $slotVoucherNo = Voucher::generateVoucherNo();

            // Extra flat escalation per cycle beyond the first (used for bulk
            // multi-year generation where the fee is expected to rise each year) —
            // added, not compounded, since these are now plain Rupee amounts.
            $cyclesIn = $slot['cycle_no'] - $cycleStart;
            $effectiveIncAmount = $incAmount + ($cycleIncrementAmount * $cyclesIn);

            foreach ($selectedTemplateItems as $item) {
                // Heads added to the template without an amount yet (and not
                // given one via item_overrides at plan-creation time either)
                // aren't charged or printed — skip them until priced.
                $overriddenAmount = $effectiveAmount($item);
                if (is_null($overriddenAmount)) continue;

                $feeHeadType = $item->feeHead->type ?? 'recurring';
                $isOneTime  = $feeHeadType === 'one_time';
                $isPerCycle = $feeHeadType === 'per_cycle';

                // One-time fee heads belong only to the first cycle of this
                // generation and are skipped entirely if the student was charged
                // that head on any earlier fee plan. Within the first cycle the
                // amount is divided across all selected installment dates.
                if ($isOneTime) {
                    if ($slot['cycle_no'] !== $cycleStart) continue;
                    if (!empty($oneTimeChargedBefore[(int) $item->fee_head_id])) continue;
                }

                // Per-cycle heads apply to every cycle and, like recurring heads,
                // are divided across that cycle's selected installment dates.

                $base = (float) $overriddenAmount;
                $itemWeight = $templateTotalBase > 0 ? ($base / $templateTotalBase) : 0;

                // This fee-head line's share of the flat increase/discount/aid
                // amounts for the whole cycle, before being spread across parts.
                $itemIncreaseForCycle = round($effectiveIncAmount * $itemWeight, 2);
                $itemReliefForCycle   = round(($discAmount + $aidAmount) * $itemWeight, 2);

                if ($billingMode === 'monthly_recurring' && !$isOneTime && !$isPerCycle) {
                    // Backwards-compatible legacy mode only. New fee-plan forms
                    // always post "split" so template totals are divided by the
                    // selected installment count.
                    $amount = $base;
                    $itemIncrease = $itemIncreaseForCycle;
                    $discountAmountForSlot = $itemReliefForCycle;
                } else {
                    $amount = $this->splitAmountAcrossParts($base, $partsPerCycle, (int) $slot['part_no']);
                    $itemIncrease = $this->splitAmountAcrossParts($itemIncreaseForCycle, $partsPerCycle, (int) $slot['part_no']);
                    $discountAmountForSlot = $this->splitAmountAcrossParts($itemReliefForCycle, $partsPerCycle, (int) $slot['part_no']);
                }

                $amount = round($amount + $itemIncrease, 2);
                $discountAmount = min($discountAmountForSlot, $amount);

                Voucher::create([
                    'voucher_no'         => $slotVoucherNo,
                    'student_id'         => $student->id,
                    'fee_head_id'        => $item->fee_head_id,
                    'fee_template_id'    => $template->id,
                    'fee_assignment_id'  => $feeAssignment->id,
                    'bank_account_id'    => $feeAssignment->bank_account_id,
                    'title'              => $item->feeHead->name . " - {$template->name}",
                    'month'              => $slot['label'],
                    'amount'             => $amount,
                    'discount_amount'    => $discountAmount,
                    'due_date'           => $slot['due_date'],
                    'issued_at'          => now(),
                    'installment_no'     => $slot['installment_no'],
                    'installment_total'  => $totalSlots,
                    'cycle_no'           => $slot['cycle_no'],
                    'part_no'            => $slot['part_no'],
                    'batch_id'           => "{$feeAssignment->batch_prefix}-{$slot['installment_no']}",
                    'status'             => 'unpaid',
                ]);

                $totalAmount += ($amount - $discountAmount);
            }
        }

        return $totalAmount;
    }

    /**
     * How many semester/year cycles the whole program runs for. The fee
     * template's own total_cycles is used if someone bothered to set it,
     * but that field is easy to leave blank when a template is created —
     * so it falls back to the student's Program's actual Parts/Semesters
     * count (the real, admin-maintained module), then to the Program's
     * duration_terms column (only relevant if no Parts/Semesters exist
     * yet), and only then to 1.
     */
    protected function computeTotalCycles(FeeTemplate $template, Student $student): int
    {
        $programCycles = $student->program
            ? ($student->program->actualTermCount() ?? $student->program->duration_terms)
            : null;

        return max(1, (int) ($template->total_cycles ?: ($programCycles ?? 1)));
    }

    /**
     * Normalize the new installment-wise fee-head map. The browser posts
     * installment_fee_heads[cycle][part][fee_head_id] = amount. Only slots
     * that actually belong to this generation batch and real FeeHead IDs are
     * retained. The amount is already the exact amount for that installment.
     */
    protected function normalizeInstallmentFeeHeads(array $raw, array $slots): array
    {
        $allowedSlots = collect($slots)->mapWithKeys(
            fn ($slot) => [((int) $slot['cycle_no']) . ':' . ((int) $slot['part_no']) => true]
        )->all();

        $candidateIds = collect($raw)->flatMap(function ($parts) {
            if (!is_array($parts)) return [];
            return collect($parts)->flatMap(fn ($heads) => is_array($heads) ? array_keys($heads) : [])->all();
        })->map(fn ($id) => (int) $id)->filter()->unique()->values();

        $validHeadIds = FeeHead::whereIn('id', $candidateIds)->pluck('id')->map(fn ($id) => (int) $id)->all();
        $validLookup = array_fill_keys($validHeadIds, true);
        $normalized = [];

        foreach ($raw as $cycleKey => $parts) {
            if (!is_array($parts)) continue;
            $cycle = (int) $cycleKey;
            foreach ($parts as $partKey => $heads) {
                if (!is_array($heads)) continue;
                $part = (int) $partKey;
                if (!isset($allowedSlots["{$cycle}:{$part}"])) continue;

                foreach ($heads as $headKey => $amount) {
                    $headId = (int) $headKey;
                    $value = is_numeric($amount) ? round((float) $amount, 2) : 0;
                    if ($headId < 1 || $value <= 0 || !isset($validLookup[$headId])) continue;
                    $normalized[$cycle][$part][$headId] = $value;
                }
            }
        }

        return $normalized;
    }

    /** Normalize custom/additional rows that belong to one exact installment. */
    protected function normalizeInstallmentCustomItems(array $raw, array $slots): array
    {
        $allowedSlots = collect($slots)->mapWithKeys(
            fn ($slot) => [((int) $slot['cycle_no']) . ':' . ((int) $slot['part_no']) => true]
        )->all();

        $headIds = collect($raw)->flatMap(function ($parts) {
            if (!is_array($parts)) return [];
            return collect($parts)->flatMap(function ($rows) {
                if (!is_array($rows)) return [];
                return collect($rows)->pluck('fee_head_id')->filter()->all();
            })->all();
        })->map(fn ($id) => (int) $id)->filter()->unique()->values();
        $heads = FeeHead::whereIn('id', $headIds)->get()->keyBy('id');

        $normalized = [];
        foreach ($raw as $cycleKey => $parts) {
            if (!is_array($parts)) continue;
            $cycle = (int) $cycleKey;
            foreach ($parts as $partKey => $rows) {
                if (!is_array($rows)) continue;
                $part = (int) $partKey;
                if (!isset($allowedSlots["{$cycle}:{$part}"])) continue;

                foreach ($rows as $row) {
                    if (!is_array($row)) continue;
                    $amount = is_numeric($row['amount'] ?? null) ? round((float) $row['amount'], 2) : 0;
                    if ($amount <= 0) continue;

                    $feeHeadId = !empty($row['fee_head_id']) ? (int) $row['fee_head_id'] : null;
                    $feeHead = $feeHeadId ? $heads->get($feeHeadId) : null;
                    if ($feeHeadId && !$feeHead) continue;

                    $label = trim((string) ($row['label'] ?? ''));
                    if ($label === '' && $feeHead) $label = $feeHead->name;
                    if ($label === '') $label = 'Additional Fee';

                    $normalized[$cycle][$part][] = [
                        'fee_head_id' => $feeHeadId,
                        'label'       => $label,
                        'amount'      => $amount,
                    ];
                }
            }
        }

        return $normalized;
    }

    /** Return the first generated slot that has no payable line at all. */
    protected function firstEmptyInstallmentSlot(array $slots, array $heads, array $custom, float $carriedForward = 0): ?array
    {
        foreach ($slots as $index => $slot) {
            $cycle = (int) $slot['cycle_no'];
            $part = (int) $slot['part_no'];
            $hasHead = !empty($heads[$cycle][$part]);
            $hasCustom = !empty($custom[$cycle][$part]);
            $hasCarryForward = $index === 0 && $carriedForward > 0;
            if (!$hasHead && !$hasCustom && !$hasCarryForward) return $slot;
        }
        return null;
    }

    /**
     * Generate vouchers from the exact fee-head selection of each installment.
     * FeeHead type is intentionally not used to move/repeat a line: when the
     * admin chooses a head on installment 2, it is billed on installment 2.
     * Increase/discount/aid remain plan-level controls and are shared across
     * the selected lines of each cycle in proportion to those line amounts.
     *
     * New UI screens submit NET installment amounts: the visible numbers are
     * exactly what the student will pay after relief. In that mode we do not
     * deduct relief a second time. We instead "gross up" each payable line by
     * its allocated relief so Voucher::amount / discount_amount continue to
     * preserve the accounting trail while Voucher::total() remains equal to
     * the amount that was shown in the installment editor.
     */
    protected function generateInstallmentSpecificVouchers(
        FeeTemplate $template, Student $student, FeeAssignment $feeAssignment, array $slots,
        array $installmentFeeHeads, array $installmentCustomItems,
        float $incAmount, float $discAmount, float $aidAmount,
        float $cycleIncrementAmount, int $cycleStart, bool $amountsAreNet = false
    ): float {
        $headIds = collect($installmentFeeHeads)->flatMap(function ($parts) {
            return collect($parts)->flatMap(fn ($heads) => array_keys($heads ?? []))->all();
        })->merge(
            collect($installmentCustomItems)->flatMap(function ($parts) {
                return collect($parts)->flatMap(function ($items) {
                    return collect($items)->pluck('fee_head_id')->filter()->all();
                })->all();
            })
        )->map(fn ($id) => (int) $id)->filter()->unique()->values();
        $heads = FeeHead::whereIn('id', $headIds)->get()->keyBy('id');

        $cycleBaseTotals = [];
        $cycleLineWeights = [];
        foreach ($slots as $slot) {
            $cycle = (int) $slot['cycle_no'];
            $part = (int) $slot['part_no'];
            $cycleBaseTotals[$cycle] = ($cycleBaseTotals[$cycle] ?? 0)
                + (float) collect($installmentFeeHeads[$cycle][$part] ?? [])->sum()
                + (float) collect($installmentCustomItems[$cycle][$part] ?? [])->sum('amount');

            if ($amountsAreNet) {
                foreach (($installmentFeeHeads[$cycle][$part] ?? []) as $feeHeadId => $payableAmount) {
                    $cycleLineWeights[$cycle]["{$part}:head:{$feeHeadId}"] = (float) $payableAmount;
                }
                foreach (($installmentCustomItems[$cycle][$part] ?? []) as $index => $item) {
                    $cycleLineWeights[$cycle]["{$part}:custom:{$index}"] = (float) $item['amount'];
                }
            }
        }

        $netReliefAllocations = [];
        if ($amountsAreNet) {
            foreach ($cycleLineWeights as $cycle => $weights) {
                $netReliefAllocations[$cycle] = $this->allocateCurrencyByWeights(
                    max(0, $discAmount + $aidAmount),
                    $weights
                );
            }
        }

        $totalAmount = 0.0;
        $totalSlots = count($slots);

        foreach ($slots as $slot) {
            $cycle = (int) $slot['cycle_no'];
            $part = (int) $slot['part_no'];
            $slotVoucherNo = Voucher::generateVoucherNo();
            $cycleBase = (float) ($cycleBaseTotals[$cycle] ?? 0);
            $cyclesIn = $cycle - $cycleStart;
            $effectiveIncrease = $incAmount + ($cycleIncrementAmount * $cyclesIn);
            $cycleRelief = $discAmount + $aidAmount;

            $lines = [];
            foreach (($installmentFeeHeads[$cycle][$part] ?? []) as $feeHeadId => $baseAmount) {
                $head = $heads->get((int) $feeHeadId);
                if (!$head) continue;
                $lines[] = [
                    'fee_head_id' => (int) $feeHeadId,
                    'title'       => $head->name . " - {$template->name}",
                    'amount'      => (float) $baseAmount,
                    'notes'       => null,
                    'allocation_key' => "{$part}:head:{$feeHeadId}",
                ];
            }
            foreach (($installmentCustomItems[$cycle][$part] ?? []) as $index => $item) {
                $lines[] = [
                    'fee_head_id' => $item['fee_head_id'] ?? null,
                    'title'       => $item['label'],
                    'amount'      => (float) $item['amount'],
                    'notes'       => 'Installment-specific fee item added by admin',
                    'allocation_key' => "{$part}:custom:{$index}",
                ];
            }

            foreach ($lines as $line) {
                if ($amountsAreNet) {
                    // The browser has already applied increase / per-cycle
                    // escalation / scholarship / aid to this exact line. Keep
                    // that payable figure untouched and only reconstruct gross
                    // + relief columns for reporting and printed vouchers.
                    $payableAmount = round((float) $line['amount'], 2);
                    $discountAmount = round((float) ($netReliefAllocations[$cycle][$line['allocation_key']] ?? 0), 2);
                    $amount = round($payableAmount + $discountAmount, 2);
                } else {
                    // Backwards compatibility for older clients that submit
                    // gross-before-relief installment values.
                    $weight = $cycleBase > 0 ? ($line['amount'] / $cycleBase) : 0;
                    $amount = round($line['amount'] + ($effectiveIncrease * $weight), 2);
                    $discountAmount = min(round($cycleRelief * $weight, 2), max(0, $amount));
                    $payableAmount = round($amount - $discountAmount, 2);
                }

                Voucher::create([
                    'voucher_no'         => $slotVoucherNo,
                    'student_id'         => $student->id,
                    'fee_head_id'        => $line['fee_head_id'],
                    'fee_template_id'    => $template->id,
                    'fee_assignment_id'  => $feeAssignment->id,
                    'bank_account_id'    => $feeAssignment->bank_account_id,
                    'title'              => $line['title'],
                    'month'              => $slot['label'],
                    'amount'             => $amount,
                    'discount_amount'    => $discountAmount,
                    'due_date'           => $slot['due_date'],
                    'issued_at'          => now(),
                    'installment_no'     => $slot['installment_no'],
                    'installment_total'  => $totalSlots,
                    'cycle_no'           => $cycle,
                    'part_no'            => $part,
                    'batch_id'           => "{$feeAssignment->batch_prefix}-{$slot['installment_no']}",
                    'status'             => 'unpaid',
                    'notes'              => $line['notes'],
                ]);

                $totalAmount += $payableAmount;
            }
        }

        return round($totalAmount, 2);
    }

    /**
     * Split a currency amount across arbitrary weighted rows in whole cents.
     * The returned allocations always add back to the exact requested amount,
     * avoiding the one/two-cent drift that proportional round() calls can
     * create across several installment fee heads.
     *
     * @param array<string|int,float|int|string> $weights
     * @return array<string|int,float>
     */
    protected function allocateCurrencyByWeights(float $amount, array $weights): array
    {
        $sign = $amount < 0 ? -1 : 1;
        $totalCents = (int) round(abs($amount) * 100);
        $positive = collect($weights)->map(fn ($weight) => max(0, (float) $weight));
        $weightTotal = (float) $positive->sum();

        $allocations = [];
        foreach ($weights as $key => $_) $allocations[$key] = 0.0;
        if ($totalCents === 0 || $weightTotal <= 0) return $allocations;

        $remainders = [];
        $allocatedCents = 0;
        foreach ($positive as $key => $weight) {
            $raw = ($totalCents * $weight) / $weightTotal;
            $whole = (int) floor($raw);
            $allocations[$key] = $whole;
            $allocatedCents += $whole;
            $remainders[$key] = $raw - $whole;
        }

        $left = $totalCents - $allocatedCents;
        arsort($remainders, SORT_NUMERIC);
        foreach (array_keys($remainders) as $key) {
            if ($left <= 0) break;
            $allocations[$key]++;
            $left--;
        }

        foreach ($allocations as $key => $cents) {
            $allocations[$key] = ($cents * $sign) / 100;
        }

        return $allocations;
    }

    /**
     * Every generated installment must have an explicit date from either the
     * saved Installment Date Defaults or the calendar on the fee-plan form.
     * No synthetic/frequency-based date is allowed for new plans.
     *
     * @return array{cycle:int,part:int}|null
     */
    protected function firstMissingInstallmentDate(
        array $dates, int $cycleStart, int $cycleEnd, int $partsPerCycle
    ): ?array {
        for ($cycle = $cycleStart; $cycle <= $cycleEnd; $cycle++) {
            $cycleDates = array_values($dates[$cycle] ?? []);
            for ($part = 1; $part <= $partsPerCycle; $part++) {
                if (empty($cycleDates[$part - 1])) {
                    return ['cycle' => $cycle, 'part' => $part];
                }
            }
        }

        return null;
    }

    /**
     * Normalize exact due dates posted by the installment calendar. The keys
     * are cycle numbers and each value is an ordered list of part dates.
     */
    protected function normalizeInstallmentDateOverrides(
        array $raw, int $cycleStart, int $cycleEnd, int $partsPerCycle
    ): array {
        $normalized = [];

        foreach ($raw as $cycleKey => $dates) {
            $cycle = (int) $cycleKey;
            if ($cycle < $cycleStart || $cycle > $cycleEnd || !is_array($dates)) {
                continue;
            }

            $clean = collect($dates)
                ->take($partsPerCycle)
                ->map(fn ($date) => $date ? Carbon::parse($date)->toDateString() : null)
                ->values()
                ->all();

            if (collect($clean)->filter()->isNotEmpty()) {
                $normalized[$cycle] = $clean;
            }
        }

        return $normalized;
    }

    /**
     * Browser-entered dates win over session defaults, while any blank/missing
     * browser slot can still inherit the configured session date.
     */
    protected function mergeInstallmentDateOverrides(array $defaults, array $entered): array
    {
        $cycles = array_unique(array_merge(array_keys($defaults), array_keys($entered)));
        $merged = [];

        foreach ($cycles as $cycle) {
            $defaultDates = array_values($defaults[$cycle] ?? []);
            $enteredDates = array_values($entered[$cycle] ?? []);
            $max = max(count($defaultDates), count($enteredDates));
            $dates = [];

            for ($i = 0; $i < $max; $i++) {
                $dates[$i] = $enteredDates[$i] ?? $defaultDates[$i] ?? null;
            }

            if (collect($dates)->filter()->isNotEmpty()) {
                $merged[(int) $cycle] = $dates;
            }
        }

        return $merged;
    }

    /** Clean up the manual-fee-item rows the admin typed into the form; drops any blank/incomplete row. */
    protected function normalizeManualItems(array $rawItems): array
    {
        $items = [];
        foreach ($rawItems as $row) {
            $amount    = (float) ($row['amount'] ?? 0);
            $feeHeadId = $row['fee_head_id'] ?? null;
            $feeHead   = $feeHeadId ? FeeHead::find($feeHeadId) : null;
            $label     = trim((string) ($row['label'] ?? ''));

            if ($label === '' && $feeHead) {
                $label = $feeHead->name;
            }
            if ($amount <= 0 || $label === '') continue;

            $type = !empty($row['one_time'])
                ? 'one_time'
                : ($feeHead?->type ?? 'recurring');

            $items[] = [
                'fee_head_id' => $feeHeadId,
                'label'       => $label,
                'amount'      => $amount,
                'type'        => $type,
                // Kept for compatibility with any existing code/data that
                // still reads this simpler flag.
                'one_time'    => $type === 'one_time',
            ];
        }
        return $items;
    }

    /** The student's currently outstanding balance, from vouchers that don't already belong to the plan being built. */
    protected function outstandingDuesFor(Student $student): float
    {
        return $student->outstandingBalance();
    }

    /**
     * Charge each manually-added fee line: one-time items are billed once,
     * in the very first installment; recurring ones are split evenly across
     * the parts of the first cycle and repeated for every subsequent cycle,
     * same rule the template items already follow.
     */
    protected function applyManualItems(
        Student $student, FeeAssignment $feeAssignment, array $slots,
        array $manualItems, int $partsPerCycle, string $billingMode
    ): float {
        if (!$manualItems) return 0.0;

        $total = 0.0;

        // Reuse the template voucher number for each installment when one
        // exists. If the admin selected only custom heads, create exactly one
        // voucher number per installment and share it across those heads.
        $slotVoucherNos = $feeAssignment->vouchers()->get()
            ->groupBy('installment_no')
            ->map(fn ($rows) => $rows->first()?->voucher_no)
            ->all();

        foreach ($slots as $slot) {
            $slotNo = (int) $slot['installment_no'];
            $slotVoucherNo = $slotVoucherNos[$slotNo] ??= Voucher::generateVoucherNo();

            foreach ($manualItems as $item) {
                $type = $item['type'] ?? (!empty($item['one_time']) ? 'one_time' : 'recurring');
                if ($type === 'one_time' && $slotNo !== 1) continue;
                if ($type === 'per_cycle' && (int) $slot['part_no'] !== 1) continue;

                $amount = in_array($type, ['one_time', 'per_cycle'], true)
                    ? $item['amount']
                    : ($billingMode === 'monthly_recurring' ? $item['amount'] : round($item['amount'] / $partsPerCycle, 2));

                Voucher::create([
                    'voucher_no'         => $slotVoucherNo,
                    'student_id'         => $student->id,
                    'fee_head_id'        => $item['fee_head_id'],
                    'fee_template_id'    => $feeAssignment->fee_template_id,
                    'fee_assignment_id'  => $feeAssignment->id,
                    'bank_account_id'    => $feeAssignment->bank_account_id,
                    'title'              => $item['label'],
                    'month'              => $slot['label'],
                    'amount'             => $amount,
                    'due_date'           => $slot['due_date'],
                    'issued_at'          => now(),
                    'installment_no'     => $slot['installment_no'],
                    'installment_total'  => count($slots),
                    'cycle_no'           => $slot['cycle_no'],
                    'part_no'            => $slot['part_no'],
                    'batch_id'           => "{$feeAssignment->batch_prefix}-{$slot['installment_no']}",
                    'status'             => 'unpaid',
                    'notes'              => 'Manually added by admin',
                ]);

                $total += $amount;
            }
        }

        return $total;
    }

    /**
     * Roll the student's outstanding balance from earlier fee plans into
     * this plan's very first installment as its own line, so it shows up
     * on the same voucher/slip instead of being a separate thing parents
     * have to track down themselves.
     */
    protected function applyCarriedForwardDues(Student $student, FeeAssignment $feeAssignment, array $slots, float $carriedForward): float
    {
        if ($carriedForward <= 0) return 0.0;

        $firstSlot = $slots[0];
        $firstVoucherNo = $feeAssignment->vouchers()
            ->where('installment_no', $firstSlot['installment_no'])
            ->value('voucher_no') ?: Voucher::generateVoucherNo();

        Voucher::create([
            'voucher_no'         => $firstVoucherNo,
            'student_id'         => $student->id,
            'fee_head_id'        => null,
            'fee_template_id'    => $feeAssignment->fee_template_id,
            'fee_assignment_id'  => $feeAssignment->id,
            'bank_account_id'    => $feeAssignment->bank_account_id,
            'title'              => 'Previous Dues Carried Forward',
            'month'              => $firstSlot['label'],
            'amount'             => $carriedForward,
            'due_date'           => $firstSlot['due_date'],
            'issued_at'          => now(),
            'installment_no'     => $firstSlot['installment_no'],
            'installment_total'  => count($slots),
            'cycle_no'           => $firstSlot['cycle_no'],
            'part_no'            => $firstSlot['part_no'],
            'batch_id'           => "{$feeAssignment->batch_prefix}-{$firstSlot['installment_no']}",
            'status'             => 'unpaid',
            'notes'              => 'Carried forward from prior outstanding balance',
        ]);

        return $carriedForward;
    }

    /**
     * "Add Installments" — re-split one cycle of an existing plan into a new
     * number of installments. Only allowed once that cycle's billing period
     * has actually started, nothing in it has been paid yet, and the cycle
     * before it is fully settled — otherwise changing the schedule would be
     * rewriting history a payment already happened against.
     */
    public function splitCycle(Request $request, FeeAssignment $feeAssignment, int $cycle)
    {
        abort_unless($cycle >= $feeAssignment->cycle_start && $cycle <= $feeAssignment->cycle_end, 404);

        $existing = $feeAssignment->vouchersForCycle($cycle);
        abort_if($existing->isEmpty(), 404, 'No installments exist yet for this semester/year on this plan.');

        if (!$feeAssignment->canSplitCycle($cycle)) {
            return back()->withErrors(['installments' => 'Either a payment has already been recorded against this semester/year, or the previous semester/year still has an outstanding balance — settle that first.']);
        }

        $validated = $request->validate([
            'installments'    => 'required|integer|min:1|max:' . max(1, (int) Setting::get('installment_max', 5)),
            'frequency_days'  => 'required|integer|min:1',
            'first_due_date'  => 'nullable|date',
            // Optional — changes this semester/year's fee (every fee head kept
            // in it) by this flat amount before re-splitting into installments.
            // Positive increases the total; negative decreases it (distributed
            // across the kept fee heads in proportion to their existing share
            // of the cycle's total).
            'fee_increase_amount' => 'nullable|integer',
            // Optional — comma-separated custom labels for each installment
            // (e.g. "Jan 2027, Feb 2027"), overriding the auto-generated
            // "Semester N - Part N" label. Extra/missing entries are fine;
            // any part without a matching label just keeps the auto label.
            'month_labels'    => 'nullable|string|max:1000',
            // Which of this cycle's existing fee heads to keep — anything not
            // in this list (i.e. a box the admin unchecked) is dropped from
            // the semester entirely. Rendered pre-checked, so a form submitted
            // without touching this section keeps everything, same as before.
            'manage_fee_heads'    => 'nullable|boolean',
            'keep_fee_head_ids'   => 'nullable|array',
            'keep_fee_head_ids.*' => 'nullable|string',
            // New fee heads to add to just this semester/year.
            'add_items'                => 'nullable|array',
            'add_items.*.fee_head_id'  => 'nullable|exists:fee_heads,id',
            'add_items.*.label'        => 'nullable|string|max:255',
            'add_items.*.amount'       => 'nullable|numeric|min:0.01',
            // New editor: exact fee heads/amounts for each new installment.
            'manage_installment_fee_heads'          => 'nullable|boolean',
            'installment_amount_basis'              => 'nullable|in:gross,net',
            'installment_fee_heads'                 => 'nullable|array',
            'installment_fee_heads.*'               => 'nullable|array',
            'installment_fee_heads.*.*'             => 'nullable|array',
            'installment_fee_heads.*.*.*'           => 'nullable|numeric|min:0.01',
            'installment_custom_items'              => 'nullable|array',
            'installment_custom_items.*'            => 'nullable|array',
            'installment_custom_items.*.*'          => 'nullable|array',
            'installment_custom_items.*.*.*'        => 'nullable|array',
            'installment_custom_items.*.*.*.fee_head_id' => 'nullable|exists:fee_heads,id',
            'installment_custom_items.*.*.*.label'       => 'nullable|string|max:255',
            'installment_custom_items.*.*.*.amount'      => 'nullable|numeric|min:0.01',
        ]);

        $newParts = (int) $validated['installments'];
        $due      = $validated['first_due_date'] ? Carbon::parse($validated['first_due_date']) : $existing->min('due_date');
        $feeIncreaseAmount = (float) ($validated['fee_increase_amount'] ?? 0);
        $monthLabels = collect(explode(',', $validated['month_labels'] ?? ''))
            ->map(fn ($m) => trim($m))
            ->filter()
            ->values();

        $useInstallmentSpecificHeads = $request->boolean('manage_installment_fee_heads');
        $installmentAmountsAreNet = ($validated['installment_amount_basis'] ?? 'gross') === 'net';
        $selectionSlots = [];
        for ($part = 1; $part <= $newParts; $part++) {
            $selectionSlots[] = [
                'cycle_no' => $cycle,
                'part_no' => $part,
                'label' => $monthLabels->get($part - 1, ($feeAssignment->feeTemplate?->cycleLabel() ?? 'Term') . " {$cycle} - Part {$part}"),
            ];
        }
        $installmentFeeHeads = $useInstallmentSpecificHeads
            ? $this->normalizeInstallmentFeeHeads($validated['installment_fee_heads'] ?? [], $selectionSlots)
            : [];
        $installmentCustomItems = $useInstallmentSpecificHeads
            ? $this->normalizeInstallmentCustomItems($validated['installment_custom_items'] ?? [], $selectionSlots)
            : [];

        if ($useInstallmentSpecificHeads) {
            $emptySlot = $this->firstEmptyInstallmentSlot($selectionSlots, $installmentFeeHeads, $installmentCustomItems);
            if ($emptySlot) {
                return back()->withInput()->withErrors([
                    'installment_fee_heads' => "Choose at least one fee head or custom item for {$emptySlot['label']}.",
                ]);
            }
        }

        // Which existing fee heads to keep. Checkboxes are rendered pre-checked
        // in the form, so an admin who doesn't touch this section keeps every
        // head untouched. Anything unchecked (i.e. missing from this array)
        // gets dropped from the semester entirely. Vouchers whose fee_head_id
        // is null (manual one-off items with no fee-head selected) are grouped
        // together under the '__none__' sentinel, matching the checkbox value.
        $defaultKeepIds = $existing->groupBy('fee_head_id')->keys()->map(
            fn ($key) => ($key === '' || $key === null) ? '__none__' : (string) $key
        )->values();
        $keepIds = $request->boolean('manage_fee_heads')
            ? collect($validated['keep_fee_head_ids'] ?? [])->map(fn ($v) => (string) $v)->intersect($defaultKeepIds)->values()->all()
            : $defaultKeepIds->all();

        // New fee heads the admin is adding to just this semester/year.
        $addItems = collect($validated['add_items'] ?? [])
            ->map(function ($row) {
                $amount = (float) ($row['amount'] ?? 0);
                if ($amount <= 0) return null;
                $feeHeadId = $row['fee_head_id'] ?? null;
                $feeHead = $feeHeadId ? FeeHead::find($feeHeadId) : null;
                $label = trim((string) ($row['label'] ?? ''));
                if ($label === '' && $feeHead) {
                    $label = $feeHead->name;
                }
                if ($label === '') $label = 'Additional Fee';
                return [
                    'fee_head_id' => $feeHeadId,
                    'label'       => $label,
                    'amount'      => $amount,
                    'type'        => $feeHead?->type ?? 'recurring',
                ];
            })
            ->filter()
            ->values();

        if (!$useInstallmentSpecificHeads && empty($keepIds) && $addItems->isEmpty()) {
            return back()->withInput()->withErrors([
                'keep_fee_head_ids' => 'Keep or add at least one fee head for this semester.',
            ]);
        }

        DB::transaction(function () use (
            $feeAssignment, $cycle, $existing, $newParts, $due, $validated, $feeIncreaseAmount,
            $monthLabels, $keepIds, $addItems, $useInstallmentSpecificHeads,
            $installmentAmountsAreNet, $installmentFeeHeads, $installmentCustomItems
        ) {
            if ($useInstallmentSpecificHeads) {
                $oldCycleTotal = round((float) $existing->sum('amount') - (float) $existing->sum('discount_amount'), 2);
                $oldDiscountTotal = round((float) $existing->sum('discount_amount'), 2);
                $cycleBase = (float) collect($installmentFeeHeads[$cycle] ?? [])->flatMap(fn ($heads) => array_values($heads ?? []))->sum()
                    + (float) collect($installmentCustomItems[$cycle] ?? [])->flatMap(fn ($items) => $items ?? [])->sum('amount');

                $selectedHeadIds = collect($installmentFeeHeads[$cycle] ?? [])->flatMap(fn ($heads) => array_keys($heads ?? []))
                    ->merge(collect($installmentCustomItems[$cycle] ?? [])->flatMap(fn ($items) => collect($items ?? [])->pluck('fee_head_id')->filter()->all()))
                    ->map(fn ($id) => (int) $id)->filter()->unique()->values();
                $heads = FeeHead::whereIn('id', $selectedHeadIds)->get()->keyBy('id');
                $oldHeadIds = $existing->pluck('fee_head_id')->filter()->map(fn ($id) => (int) $id)->unique()->values();

                // New split editors submit the current NET payable amounts.
                // fee_increase_amount is still a separate explicit control, so
                // add/decrease it to the posted net schedule before rebuilding
                // voucher gross + discount columns. Legacy forms keep the old
                // gross-before-discount behavior below.
                $netIncreaseAllocations = [];
                $netReliefAllocations = [];
                if ($installmentAmountsAreNet) {
                    if ($cycleBase + $feeIncreaseAmount < -0.001) {
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            'fee_increase_amount' => 'The fee decrease cannot be greater than the semester payable amount.',
                        ]);
                    }

                    $lineWeights = [];
                    foreach (($installmentFeeHeads[$cycle] ?? []) as $part => $rows) {
                        foreach (($rows ?? []) as $feeHeadId => $amount) {
                            $lineWeights["{$part}:head:{$feeHeadId}"] = (float) $amount;
                        }
                    }
                    foreach (($installmentCustomItems[$cycle] ?? []) as $part => $rows) {
                        foreach (($rows ?? []) as $index => $item) {
                            $lineWeights["{$part}:custom:{$index}"] = (float) $item['amount'];
                        }
                    }

                    $netIncreaseAllocations = $this->allocateCurrencyByWeights($feeIncreaseAmount, $lineWeights);
                    $adjustedNetWeights = [];
                    foreach ($lineWeights as $key => $amount) {
                        $adjustedNetWeights[$key] = max(0, round($amount + ($netIncreaseAllocations[$key] ?? 0), 2));
                    }
                    $netReliefAllocations = $this->allocateCurrencyByWeights(max(0, $oldDiscountTotal), $adjustedNetWeights);
                }

                Voucher::whereIn('id', $existing->pluck('id'))->delete();
                $newCycleTotal = 0.0;

                for ($part = 1; $part <= $newParts; $part++) {
                    $dueDate = $due->copy()->addDays((int) $validated['frequency_days'] * ($part - 1));
                    $voucherNo = Voucher::generateVoucherNo();
                    $monthLabel = $monthLabels->get($part - 1, ($feeAssignment->feeTemplate?->cycleLabel() ?? 'Term') . " {$cycle} - Part {$part}");

                    $lines = [];
                    foreach (($installmentFeeHeads[$cycle][$part] ?? []) as $feeHeadId => $baseAmount) {
                        $head = $heads->get((int) $feeHeadId);
                        if (!$head) continue;
                        $lines[] = [
                            'fee_head_id' => (int) $feeHeadId,
                            'title' => $head->name . ($feeAssignment->feeTemplate ? " - {$feeAssignment->feeTemplate->name}" : ''),
                            'amount' => (float) $baseAmount,
                            'notes' => null,
                            'allocation_key' => "{$part}:head:{$feeHeadId}",
                        ];
                    }
                    foreach (($installmentCustomItems[$cycle][$part] ?? []) as $index => $item) {
                        $lines[] = [
                            'fee_head_id' => $item['fee_head_id'] ?? null,
                            'title' => $item['label'],
                            'amount' => (float) $item['amount'],
                            'notes' => 'Installment-specific fee item added by admin',
                            'allocation_key' => "{$part}:custom:{$index}",
                        ];
                    }

                    foreach ($lines as $line) {
                        if ($installmentAmountsAreNet) {
                            $payable = max(0, round(
                                $line['amount'] + ($netIncreaseAllocations[$line['allocation_key']] ?? 0),
                                2
                            ));
                            $discount = round((float) ($netReliefAllocations[$line['allocation_key']] ?? 0), 2);
                            $amount = round($payable + $discount, 2);
                        } else {
                            $weight = $cycleBase > 0 ? ($line['amount'] / $cycleBase) : 0;
                            $amount = round($line['amount'] + ($feeIncreaseAmount * $weight), 2);
                            $discount = min(round($oldDiscountTotal * $weight, 2), max(0, $amount));
                            $payable = round($amount - $discount, 2);
                        }

                        Voucher::create([
                            'voucher_no'         => $voucherNo,
                            'student_id'         => $feeAssignment->student_id,
                            'fee_head_id'        => $line['fee_head_id'],
                            'fee_template_id'    => $feeAssignment->fee_template_id,
                            'fee_assignment_id'  => $feeAssignment->id,
                            'bank_account_id'    => $feeAssignment->bank_account_id,
                            'title'              => $line['title'],
                            'month'              => $monthLabel,
                            'amount'             => $amount,
                            'discount_amount'    => $discount,
                            'due_date'           => $dueDate,
                            'issued_at'          => now(),
                            'cycle_no'           => $cycle,
                            'part_no'            => $part,
                            'batch_id'           => "{$feeAssignment->batch_prefix}-c{$cycle}-p{$part}",
                            'status'             => 'unpaid',
                            'notes'              => $line['notes'],
                        ]);
                        $newCycleTotal += $payable;
                    }
                }

                $slots = $feeAssignment->vouchers()->orderBy('due_date')->get()->groupBy('voucher_no');
                $total = $slots->count();
                $no = 1;
                foreach ($slots as $voucherNo => $rows) {
                    Voucher::where('fee_assignment_id', $feeAssignment->id)
                        ->where('voucher_no', $voucherNo)
                        ->update(['installment_no' => $no, 'installment_total' => $total]);
                    $no++;
                }

                $delta = round($newCycleTotal - $oldCycleTotal, 2);
                $feeAssignment->update([
                    'installments' => $total,
                    'total_amount' => round((float) $feeAssignment->total_amount + $delta, 2),
                ]);

                $removedCount = $oldHeadIds->diff($selectedHeadIds)->count();
                $addedCount = $selectedHeadIds->diff($oldHeadIds)->count();
                $notes = [];
                if ($feeIncreaseAmount != 0) {
                    $notes[] = $feeIncreaseAmount > 0
                        ? 'fee increased by Rs. ' . number_format($feeIncreaseAmount, 2)
                        : 'fee decreased by Rs. ' . number_format(abs($feeIncreaseAmount), 2);
                }
                if ($removedCount) $notes[] = "{$removedCount} fee head(s) removed";
                if ($addedCount) $notes[] = "{$addedCount} fee head(s) added";
                if (collect($installmentCustomItems[$cycle] ?? [])->flatten(1)->isNotEmpty()) $notes[] = 'installment-specific custom items updated';
                $noteStr = $notes ? ', ' . implode(', ', $notes) : '';

                AuditLog::record('updated', 'FeeAssignment', $feeAssignment->id,
                    "Rebuilt cycle {$cycle} into {$newParts} installment(s) with installment-wise fee heads{$noteStr} for {$feeAssignment->student->name}");
                return;
            }

            // Re-split by fee head so each head's total for this cycle stays exact.
            $byHead = $existing->groupBy('fee_head_id')->filter(function ($vouchers, $key) use ($keepIds) {
                $normalizedKey = ($key === '' || $key === null) ? '__none__' : (string) $key;
                return in_array($normalizedKey, $keepIds, true);
            });
            $oldCycleTotal = round((float) $existing->sum('amount') - (float) $existing->sum('discount_amount'), 2);

            // Share of the flat fee_increase_amount each kept fee head carries,
            // proportional to that head's own amount within the kept total —
            // so a head that is 80% of the semester's fee also absorbs 80%
            // of the flat increase/decrease, instead of every head shifting
            // by an identical amount regardless of size.
            $keptHeadTotal = (float) $byHead->flatten()->sum('amount');

            Voucher::whereIn('id', $existing->pluck('id'))->delete();

            $newCycleTotal = 0.0;

            for ($part = 1; $part <= $newParts; $part++) {
                $dueDate   = $due->copy()->addDays((int) $validated['frequency_days'] * ($part - 1));
                $voucherNo = Voucher::generateVoucherNo(); // one number per installment slot, same as the rest of the app
                $monthLabel = $monthLabels->get($part - 1, ($feeAssignment->feeTemplate?->cycleLabel() ?? 'Term') . " {$cycle} - Part {$part}");

                foreach ($byHead as $feeHeadId => $vouchers) {
                    $sample        = $vouchers->first();
                    $headType      = $sample->feeHead->type ?? 'recurring';
                    $doNotSplit    = in_array($headType, ['one_time', 'per_cycle'], true);

                    // A one-time/per-cycle head keeps its full amount on the first
                    // new part only — splitting it across every new installment
                    // would reintroduce the same "fee repeats every installment" bug.
                    if ($doNotSplit && $part !== 1) continue;

                    $headBase      = (float) $vouchers->sum('amount');
                    $headWeight    = $keptHeadTotal > 0 ? ($headBase / $keptHeadTotal) : 0;
                    $cycleTotal    = round($headBase + ($feeIncreaseAmount * $headWeight), 2);
                    $cycleDiscount = round((float) $vouchers->sum('discount_amount'), 2);
                    $partAmount    = $doNotSplit ? $cycleTotal : round($cycleTotal / $newParts, 2);
                    $partDiscount  = $doNotSplit ? $cycleDiscount : round($cycleDiscount / $newParts, 2);

                    Voucher::create([
                        'voucher_no'         => $voucherNo,
                        'student_id'         => $feeAssignment->student_id,
                        'fee_head_id'        => $feeHeadId === '' ? null : $feeHeadId,
                        'fee_template_id'    => $feeAssignment->fee_template_id,
                        'fee_assignment_id'  => $feeAssignment->id,
                        'bank_account_id'    => $feeAssignment->bank_account_id,
                        'title'              => $sample->title,
                        'month'              => $monthLabel,
                        'amount'             => $partAmount,
                        'discount_amount'    => $partDiscount,
                        'due_date'           => $dueDate,
                        'issued_at'          => now(),
                        'cycle_no'           => $cycle,
                        'part_no'            => $part,
                        'batch_id'           => "{$feeAssignment->batch_prefix}-c{$cycle}-p{$part}",
                        'status'             => 'unpaid',
                    ]);

                    $newCycleTotal += $partAmount - $partDiscount;
                }

                foreach ($addItems as $item) {
                    $doNotSplit = in_array($item['type'], ['one_time', 'per_cycle'], true);
                    if ($doNotSplit && $part !== 1) continue;
                    $partAmount = $doNotSplit ? $item['amount'] : round($item['amount'] / $newParts, 2);

                    Voucher::create([
                        'voucher_no'         => $voucherNo,
                        'student_id'         => $feeAssignment->student_id,
                        'fee_head_id'        => $item['fee_head_id'],
                        'fee_template_id'    => $feeAssignment->fee_template_id,
                        'fee_assignment_id'  => $feeAssignment->id,
                        'bank_account_id'    => $feeAssignment->bank_account_id,
                        'title'              => $item['label'],
                        'month'              => $monthLabel,
                        'amount'             => $partAmount,
                        'discount_amount'    => 0,
                        'due_date'           => $dueDate,
                        'issued_at'          => now(),
                        'cycle_no'           => $cycle,
                        'part_no'            => $part,
                        'batch_id'           => "{$feeAssignment->batch_prefix}-c{$cycle}-p{$part}",
                        'status'             => 'unpaid',
                        'notes'              => 'Added to this semester/year by admin',
                    ]);

                    $newCycleTotal += $partAmount;
                }
            }

            // installment_no runs sequentially across the *whole* plan (every cycle),
            // so re-splitting one cycle means every slot from here on needs renumbering.
            // A "slot" = one voucher_no; walk them in due-date order and reassign.
            $slots = $feeAssignment->vouchers()->orderBy('due_date')->get()->groupBy('voucher_no');
            $total = $slots->count();
            $no = 1;
            foreach ($slots as $voucherNo => $rows) {
                Voucher::where('fee_assignment_id', $feeAssignment->id)
                    ->where('voucher_no', $voucherNo)
                    ->update(['installment_no' => $no, 'installment_total' => $total]);
                $no++;
            }

            // Keep the plan's total_amount accurate after the fee change (if any).
            $delta = round($newCycleTotal - $oldCycleTotal, 2);
            $feeAssignment->update([
                'installments'  => $total,
                'total_amount'  => round((float) $feeAssignment->total_amount + $delta, 2),
            ]);

            $removedCount = $existing->pluck('fee_head_id')->unique()->count() - $byHead->count();
            $notes = [];
            if ($feeIncreaseAmount != 0) {
                $notes[] = $feeIncreaseAmount > 0
                    ? 'fee increased by Rs. ' . number_format($feeIncreaseAmount, 2)
                    : 'fee decreased by Rs. ' . number_format(abs($feeIncreaseAmount), 2);
            }
            if ($removedCount > 0)    $notes[] = "{$removedCount} fee head(s) removed";
            if ($addItems->isNotEmpty()) $notes[] = $addItems->count() . ' fee head(s) added';
            $noteStr = $notes ? ', ' . implode(', ', $notes) : '';

            AuditLog::record('updated', 'FeeAssignment', $feeAssignment->id,
                "Re-split {$feeAssignment->cycleRangeLabel()} cycle {$cycle} into {$newParts} installment(s){$noteStr} for {$feeAssignment->student->name}");
        });

        $successMsg = "Cycle {$cycle} was re-split into {$newParts} installment(s).";
        if ($feeIncreaseAmount > 0) {
            $successMsg .= ' Fee increased by Rs. ' . number_format($feeIncreaseAmount, 2) . '.';
        } elseif ($feeIncreaseAmount < 0) {
            $successMsg .= ' Fee decreased by Rs. ' . number_format(abs($feeIncreaseAmount), 2) . '.';
        }
        if ($addItems->isNotEmpty()) {
            $successMsg .= ' ' . $addItems->count() . ' fee head(s) added.';
        }

        return redirect()->route('fee-assignments.show', $feeAssignment)
            ->with('success', $successMsg);
    }

    /**
     * Change only the due dates for individual installments in a semester.
     * This is deliberately separate from re-splitting the fee: a student can
     * be granted a different payment schedule without changing amounts, fee
     * heads, or the global default dates. Installments that already received
     * any payment are protected from date changes.
     */
    public function updateCycleDueDates(Request $request, FeeAssignment $feeAssignment, int $cycle)
    {
        abort_unless($cycle >= $feeAssignment->cycle_start && $cycle <= $feeAssignment->cycle_end, 404);

        $existing = $feeAssignment->vouchersForCycle($cycle);
        abort_if($existing->isEmpty(), 404, 'No installments exist yet for this semester/year on this plan.');

        $validated = $request->validate([
            'due_dates'   => 'required|array|min:1|max:12',
            'due_dates.*' => 'required|date',
        ]);

        $parts = $existing->groupBy(fn ($voucher) => (int) ($voucher->part_no ?: $voucher->installment_no));
        $updated = 0;

        DB::transaction(function () use ($validated, $parts, $feeAssignment, $cycle, &$updated) {
            foreach ($validated['due_dates'] as $partNo => $date) {
                $partNo = (int) $partNo;
                $lines = $parts->get($partNo);
                if (!$lines) {
                    continue;
                }

                if ($lines->contains(fn ($voucher) => (float) $voucher->paid_amount > 0)) {
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "due_dates.{$partNo}" => "Installment {$partNo} already has a payment recorded, so its due date cannot be changed.",
                    ]);
                }

                $newDate = Carbon::parse($date)->toDateString();
                $lineIds = $lines->pluck('id');
                Voucher::whereIn('id', $lineIds)->update(['due_date' => $newDate]);
                // The visible status is derived from the due date. Reset only
                // normal unpaid/overdue rows; never resurrect a cancelled row.
                Voucher::whereIn('id', $lineIds)
                    ->whereNotIn('status', ['cancelled', 'paid'])
                    ->update(['status' => 'unpaid']);
                $updated++;
            }

            $earliestPlanDue = $feeAssignment->vouchers()->min('due_date');
            if ($earliestPlanDue) {
                $feeAssignment->update(['first_due_date' => $earliestPlanDue]);
            }

            AuditLog::record('updated', 'FeeAssignment', $feeAssignment->id,
                "Changed {$updated} installment due date(s) for cycle {$cycle} of {$feeAssignment->student->name}'s fee plan");
        });

        return back()->with('success', "Updated {$updated} installment due date(s) for cycle {$cycle}.");
    }

    /**
     * Force-unlock or re-lock a semester/year's "Manage Installments" form.
     * Available to both admin and accountant (same shared 'accounts' route
     * group as splitCycle) for the rare case where the automatic
     * splitLockReason() check doesn't match the real situation on a plan —
     * e.g. a stray/legacy voucher with a tiny paid_amount, or a previous
     * cycle that's effectively settled but not reflected yet.
     */
    public function toggleCycleLock(Request $request, FeeAssignment $feeAssignment, int $cycle)
    {
        abort_unless($cycle >= $feeAssignment->cycle_start && $cycle <= $feeAssignment->cycle_end, 404);

        $action = $request->input('action') === 'lock' ? 'lock' : 'unlock';

        if ($action === 'unlock') {
            $feeAssignment->unlockCycleManually($cycle);
            AuditLog::record('updated', 'FeeAssignment', $feeAssignment->id,
                "Manually force-unlocked {$feeAssignment->cycleRangeLabel()} cycle {$cycle} (\"Manage Installments\") for {$feeAssignment->student->name}");
            $message = "Cycle {$cycle} was force-unlocked. You can now re-split its installments.";
        } else {
            $feeAssignment->relockCycleManually($cycle);
            AuditLog::record('updated', 'FeeAssignment', $feeAssignment->id,
                "Restored the automatic lock for {$feeAssignment->cycleRangeLabel()} cycle {$cycle} for {$feeAssignment->student->name}");
            $message = "Cycle {$cycle}'s automatic lock was restored.";
        }

        return back()->with('success', $message);
    }
}