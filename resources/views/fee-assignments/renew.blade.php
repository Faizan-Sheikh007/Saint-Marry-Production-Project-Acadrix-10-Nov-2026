@extends('layouts.app')
@section('title', 'Renew Fee Plan - ' . $feeAssignment->student->name)
@section('page_title', 'Renew Fee Plan')
@section('content')

@push('styles')
<style>
  /* Page-local layout: preserve the shared application theme and date controls. */
  .fee-plan-layout{width:100%;max-width:none;min-width:0}
  .fee-plan-setup-grid,.fee-plan-footer-grid{display:grid;grid-template-columns:repeat(2,minmax(0,1fr));gap:20px;align-items:start}
  .fee-plan-layout .card,.fee-plan-layout .card-body,.fee-plan-layout .form-group,
  .fee-plan-setup-grid>* , .fee-plan-footer-grid>*{min-width:0}
  .fee-plan-layout .grid-2{grid-template-columns:repeat(2,minmax(0,1fr))}
  .fee-plan-layout .form-control{min-width:0;max-width:100%}
  .fee-plan-calendar-card,.fee-plan-details-card{width:100%;margin-top:20px}
  .fee-plan-calendar-card .card-title{line-height:1.5}
  .fee-plan-help{display:block;font-size:12px;line-height:1.65;color:var(--text-muted);margin-bottom:14px}
  .fee-plan-section-title{font-size:14px;font-weight:800;color:var(--text);margin:0 0 16px}
  .fee-plan-renew-setup{margin-bottom:24px}
  .fee-plan-renew-calendar{padding-top:20px;border-top:1px solid var(--border);margin-bottom:24px}
  .fee-plan-bank-display{display:flex;align-items:flex-start;gap:10px;min-height:44px;padding:12px 14px;background:var(--bg);border:1px solid var(--border);border-radius:8px;line-height:1.6}
  .fee-plan-bank-display>i{flex:0 0 auto;margin-top:4px;color:var(--primary)}
  .fee-plan-bank-display>span{min-width:0;overflow-wrap:anywhere}
  .fee-plan-actions{display:flex;flex-wrap:wrap;gap:12px;justify-content:flex-end;margin-top:20px}
  .fee-plan-actions .btn{white-space:normal;text-align:center}
  .installment-calendar-shell{width:100%;min-width:0;border:1px solid var(--border);border-radius:12px;padding:16px;background:var(--bg);container-type:inline-size;container-name:fee-calendar}
  .installment-calendar-toolbar{display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:16px;flex-wrap:wrap}
  .installment-calendar-toolbar>div{flex:1 1 280px;min-width:0;line-height:1.6}
  .installment-calendar-toolbar>.btn{flex:0 1 auto;white-space:normal}
  /* Use document scrolling, not a clipped 520px-high inner scroll area. */
  .installment-calendar-list{display:flex;flex-direction:column;gap:16px;width:100%;min-width:0;max-height:none;overflow:visible}
  .installment-cycle-card{min-width:0;background:var(--card,#fff);border:1px solid var(--border);border-radius:11px;padding:16px}
  .installment-cycle-head{display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:10px;margin-bottom:16px}
  .installment-cycle-title{font-size:16px;font-weight:800;color:var(--text)}
  .installment-cycle-meta{font-size:12px;color:var(--text-muted)}
  .installment-date-grid{display:grid;grid-template-columns:repeat(auto-fit,minmax(min(100%,280px),1fr));gap:16px;align-items:stretch;min-width:0}
  .installment-date-card{display:flex;flex-direction:column;min-width:0;border:1px solid var(--border);border-radius:10px;padding:14px;background:var(--bg)}
  .installment-date-heading{display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:8px;font-size:13px;font-weight:800;line-height:1.4;margin-bottom:12px}
  .installment-date-source{flex:0 0 auto;font-size:10px;font-weight:700;padding:3px 8px;border-radius:999px;background:rgba(16,185,129,.1);color:var(--success);white-space:nowrap}
  .installment-date-source.manual{background:rgba(100,116,139,.12);color:var(--text-muted)}
  .installment-date-card .form-control{width:100%;min-width:0;max-width:100%;min-height:38px;padding:8px 10px;font-size:13px}
  .installment-date-card .ax-date-control{display:flex;width:100%;min-width:0}
  .installment-fee-section-title{font-size:11px;font-weight:800;margin-top:16px;margin-bottom:8px;line-height:1.5;color:var(--text-muted)}
  .installment-fee-head-row,.renew-fee-head-row{display:grid;grid-template-columns:minmax(0,1fr) 104px;gap:10px;align-items:center;min-width:0;padding:10px 0;border-top:1px dashed var(--border)}
  .installment-fee-label{display:flex;align-items:flex-start;justify-content:flex-start;gap:8px;min-width:0;margin:0;font-size:12px;font-weight:600;line-height:1.45;cursor:pointer}
  .installment-fee-label>input[type="checkbox"]{flex:0 0 auto;width:15px;height:15px;margin-top:2px;accent-color:var(--primary)}
  .installment-fee-label>span{min-width:0;overflow-wrap:anywhere}
  .installment-fee-label small{display:block;font-size:10.5px;font-weight:400;color:var(--text-muted)}
  .installment-fee-amount,.renew-fee-amount{text-align:right;font-variant-numeric:tabular-nums}
  .installment-custom-wrap,.renew-custom-wrap{min-width:0}
  /* A custom fee is a compact three-row block; never four fixed-width columns. */
  .installment-custom-row,.renew-custom-row{display:grid;grid-template-columns:minmax(0,1fr) 38px;gap:8px;min-width:0;margin-top:10px;padding:10px;border:1px dashed var(--border);border-radius:8px;background:var(--card,#fff)}
  .installment-custom-row>select,.renew-custom-row>select{grid-column:1;grid-row:1}
  .installment-custom-row>input[type="text"],.renew-custom-row>input[type="text"]{grid-column:1/-1;grid-row:2}
  .installment-custom-row>input[type="number"],.renew-custom-row>input[type="number"]{grid-column:1/-1;grid-row:3;text-align:right}
  .installment-custom-row>.btn,.renew-custom-row>.btn{grid-column:2;grid-row:1;min-width:0;width:38px;min-height:38px;justify-content:center;padding:8px}
  .add-installment-custom,.add-renew-custom{width:100%;max-width:100%;min-height:38px;justify-content:center;white-space:normal;text-align:center;line-height:1.4;margin:12px 0 14px;padding:8px 10px}
  .installment-payable-total,.renew-installment-payable-total{display:flex;align-items:flex-start;justify-content:space-between;gap:12px;flex-wrap:wrap;margin-top:auto;padding-top:12px;border-top:1px solid var(--border);font-size:12px;line-height:1.5}
  .installment-payable-total>span,.renew-installment-payable-total>span{flex:1 1 120px;font-weight:700}
  .installment-payable-total>strong,.renew-installment-payable-total>strong{flex:0 0 auto;color:var(--primary);white-space:nowrap;font-size:14px;font-variant-numeric:tabular-nums}
  /* Break on available content width, including when the sidebar is present. */
  @container fee-calendar (max-width:619px){.installment-date-grid{grid-template-columns:minmax(0,1fr)}}
  @container fee-calendar (min-width:620px){.installment-date-grid{grid-template-columns:repeat(2,minmax(0,1fr))}}
  @container fee-calendar (min-width:1220px){
    .installment-date-grid{grid-template-columns:repeat(4,minmax(0,1fr))}
    .installment-date-grid[data-installments="2"]{grid-template-columns:repeat(2,minmax(0,1fr))}
    .installment-date-grid[data-installments="3"]{grid-template-columns:repeat(3,minmax(0,1fr))}
  }
  .installment-date-grid[data-installments="1"]{grid-template-columns:minmax(0,1fr)}
  @media(max-width:1100px){.fee-plan-setup-grid{grid-template-columns:minmax(0,1fr)}}
  @media(max-width:768px){
    .fee-plan-layout .grid-2,.fee-plan-footer-grid{grid-template-columns:minmax(0,1fr)}
    .fee-plan-calendar-card>.card-body{padding:14px}
    .installment-calendar-shell,.installment-cycle-card{padding:12px}
    .installment-date-card{padding:12px}
    .fee-plan-actions>.btn{flex:1 1 180px;justify-content:center}
  }
  @media(max-width:480px){
    .fee-plan-layout.card,.fee-plan-layout .card{padding:0}
    .fee-plan-layout .card-body{padding:14px}
    .installment-calendar-shell,.installment-cycle-card{padding:10px}
    .installment-fee-head-row,.renew-fee-head-row{grid-template-columns:minmax(0,1fr) 90px;gap:8px}
    .installment-calendar-toolbar>.btn{width:100%;justify-content:center}
  }
</style>
@endpush

<div style="display:flex;align-items:center;gap:12px;margin-bottom:20px">
  <a href="{{ route('fee-assignments.show', $feeAssignment) }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back to Plan</a>
</div>

@if($errors->any())
  <div style="background:#fee2e2;border:1px solid #ef4444;padding:12px 16px;border-radius:8px;margin-bottom:20px">
    <strong style="color:#dc2626">Please fix the following errors:</strong>
    <ul style="margin:8px 0 0 20px;color:#dc2626">
      @foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach
    </ul>
  </div>
@endif

<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="display:flex;flex-wrap:wrap;gap:24px;align-items:center">
    <div>
      <div style="font-weight:800;font-size:16px">{{ $feeAssignment->student->name }} <span style="color:var(--text-muted);font-weight:500">({{ $feeAssignment->student->student_id }})</span></div>
      <div style="font-size:12px;color:var(--text-muted)">{{ $template->name }} &middot; last generated through <strong>{{ $feeAssignment->cycleRangeLabel() }}</strong></div>
    </div>
  </div>
</div>

@include('fee-assignments._progression-notice')

<div class="card fee-plan-layout">
  <div class="card-header"><div class="card-title"><i class="fas fa-rotate" style="color:var(--primary);margin-right:8px"></i>Create {{ $template->cycleLabel() }} {{ $nextCycle }}</div></div>
  <div class="card-body">
    <div style="font-size:13px;color:var(--text-muted);margin-bottom:16px">
      This creates only {{ $template->cycleLabel() }} {{ $nextCycle }}, following the student's latest planned semester/year. Previous plans and payments are left untouched.
      Any increase you enter here stacks on top of the Rs. {{ number_format($feeAssignment->increase_amount, 0) }} already applied on the plan you're renewing.
    </div>
    <form action="{{ route('fee-assignments.renew.store', $feeAssignment) }}" method="POST">
      @csrf
      <input type="hidden" name="manage_installment_fee_heads" value="1">
      <input type="hidden" name="installment_amount_basis" value="net">
      <input type="hidden" name="cycle_no" value="{{ $nextCycle }}">

      <div class="fee-plan-setup-grid fee-plan-renew-setup">
        <div>
          <h2 class="fee-plan-section-title">Fee Adjustments</h2>
      <div class="form-group">
        <label class="form-label">Increase Fee by (Rs.) *</label>
        <input type="number" step="1" min="0" name="increment_amount" class="form-control" value="{{ old('increment_amount', 0) }}" placeholder="e.g. 1000">
        <small style="color:var(--text-muted)">e.g. 1000 = raise the fee by Rs. 1000 for the new {{ strtolower($template->cycleLabel()) }}(s). Leave 0 to keep the same amount.</small>
      </div>

      <div class="form-group" style="background:var(--bg);border:1px dashed var(--border);border-radius:9px;padding:10px 12px">
        <div style="font-size:12px;color:var(--text-muted)"><i class="fas fa-list-check"></i> Fee amounts start from the <strong>Fee Template gross amount</strong>, then the carried-forward/new increase and the renewal scholarship / discount / financial aid are applied immediately. The installment editor below always shows the <strong>net payable amount</strong>. You can still edit an installment or add a custom item before generating the plan.</div>
      </div>

      <div class="grid grid-2" style="gap:12px">
        <div class="form-group">
          <label class="form-label">Scholarship (Rs.)</label>
          <input type="number" step="1" min="0" name="scholarship_amount" class="form-control" value="{{ old('scholarship_amount', $feeAssignment->scholarship_amount) }}">
          <small style="color:var(--text-muted)">Carried forward from the previous plan by default; change it here if the scholarship changes for the renewed cycle.</small>
        </div>
        <div class="form-group">
          <label class="form-label">Financial Aid (Rs.)</label>
          <input type="number" step="1" min="0" name="financial_aid_amount" class="form-control" value="{{ old('financial_aid_amount', $feeAssignment->financial_aid_amount) }}">
        </div>
      </div>
      <div class="form-group">
        <label class="form-label">Other Discount (Rs.)</label>
        <input type="number" step="1" min="0" name="discount_amount" class="form-control" value="{{ old('discount_amount', $feeAssignment->discount_amount) }}">
        <small style="color:var(--text-muted)">Legacy/general discount, kept separate from scholarship and financial aid for reporting.</small>
      </div>

        </div>
        <div>
          <h2 class="fee-plan-section-title">Installments &amp; Schedule</h2>
      <div class="grid grid-2" style="gap:12px">
        <div class="form-group">
          <label class="form-label">Creating Fee Plan For</label>
          <div class="form-control">{{ $template->cycleLabel() }} {{ $nextCycle }}</div>
          <input type="hidden" name="cycles_to_generate" id="renew-cycles-input" value="1">
          <small style="color:var(--text-muted)">Only the next semester/year is generated. Earlier plans cannot be duplicated.</small>
        </div>
        <div class="form-group">
          <label class="form-label">Number of Installments per {{ $template->cycleLabel() }} *</label>
          <input type="number" min="1" max="{{ $maxInstallments ?? 5 }}" name="installments" id="renew-installments-input" class="form-control" value="{{ old('installments', count($defaultInstallmentDates[$nextCycle] ?? []) ?: ($template->parts_per_cycle ?? 1)) }}" required>
          <small style="color:var(--text-muted)">How many payments this {{ strtolower($template->cycleLabel()) }} is split into.</small>
        </div>
      </div>

      <input type="hidden" name="billing_mode" value="split">
      <div class="form-group" style="background:rgba(37,99,235,.05);border:1px solid rgba(37,99,235,.18);border-radius:10px;padding:10px 12px">
        <div style="font-size:12.5px;color:var(--text-muted);line-height:1.55">
          <i class="fas fa-calculator" style="color:var(--primary)"></i>
          <strong style="color:var(--text)">Automatic installment split:</strong>
          applicable Fee Template amounts are divided across the selected due dates for each renewed semester/year, and every amount shown in the cards is the <strong>net payable after scholarship / discount / aid</strong>.
        </div>
      </div>

      @php $hasDefaultInstallmentDates = !empty($defaultInstallmentDates ?? []); @endphp
      <div class="form-group" style="border:1px solid {{ $hasDefaultInstallmentDates ? 'rgba(16,185,129,.35)' : 'var(--border)' }};border-radius:10px;padding:11px 12px;background:{{ $hasDefaultInstallmentDates ? 'rgba(16,185,129,.05)' : 'var(--bg)' }}">
        <input type="hidden" name="use_default_installment_dates" value="0">
        <label style="display:flex;gap:8px;align-items:flex-start;margin:0">
          <input type="checkbox" id="renew-use-default-installment-dates" name="use_default_installment_dates" value="1" {{ old('use_default_installment_dates', 1) ? 'checked' : '' }} style="margin-top:3px">
          <span style="font-size:12.5px"><strong>Use session default installment dates for the renewed semester(s)</strong><br>
            <span style="color:var(--text-muted)">{{ $hasDefaultInstallmentDates ? 'Configured Session dates are loaded into the calendar below and remain editable for this student.' : 'No matching Session default schedule is currently configured. Keep this selected to use Session defaults automatically when available; any missing installment date must be chosen from the calendar below.' }}</span>
            @if(!$hasDefaultInstallmentDates && ($defaultScheduleDiagnostics ?? null))
              <details style="margin-top:8px">
                <summary style="cursor:pointer;color:var(--text-muted);font-size:11.5px">Why isn't it matching?</summary>
                <div style="margin-top:6px;font-size:11.5px;line-height:1.7;color:var(--text-muted)">
                  <div>Student's Session on record: <code>{{ $defaultScheduleDiagnostics['student_session_text'] ?? '—' }}</code> (academic_year_id: <code>{{ $defaultScheduleDiagnostics['student_academic_year_id'] ?? 'null' }}</code>)</div>
                  <div>Session resolved to: @if($defaultScheduleDiagnostics['session_resolved']) {{ implode(', ', $defaultScheduleDiagnostics['resolved_session_ids']) }} @else <strong style="color:#b91c1c">none — this student's Session couldn't be matched to any Academic Session record</strong> @endif</div>
                  <div>Student's Program (via school_class_id: <code>{{ $defaultScheduleDiagnostics['student_school_class_id'] ?? 'null' }}</code>, program_id: <code>{{ $defaultScheduleDiagnostics['student_program_id'] ?? 'null' }}</code>)</div>
                  <div>Program resolved to: @if($defaultScheduleDiagnostics['program_resolved']) {{ implode(', ', $defaultScheduleDiagnostics['resolved_program_ids']) }} @else <strong style="color:#b91c1c">none — this student has no resolvable Program</strong> @endif</div>
                  @if($defaultScheduleDiagnostics['session_resolved'])
                    <div style="margin-top:4px">Saved defaults found for this Session (cycle {{ $defaultScheduleDiagnostics['cycle_no'] }} needed):</div>
                    @if(empty($defaultScheduleDiagnostics['defaults_for_session']))
                      <div>&nbsp;&nbsp;<strong style="color:#b91c1c">none at all — no InstallmentDateDefault rows exist for this Session</strong></div>
                    @else
                      @foreach($defaultScheduleDiagnostics['defaults_for_session'] as $row)
                        <div>&nbsp;&nbsp;cycle {{ $row['cycle_no'] }} — program_id {{ $row['program_id'] ?? 'null (all programs)' }} {{ $row['program_name'] ? '('.$row['program_name'].')' : '' }}</div>
                      @endforeach
                    @endif
                  @endif
                </div>
              </details>
            @endif
          </span>
        </label>
      </div>

      <input type="hidden" name="first_due_date" id="renew-first-due-date" value="{{ old('first_due_date', $defaultFirstDueDate ?? now()->toDateString()) }}">
      <input type="hidden" name="frequency_days" value="30">
      <input type="hidden" name="cycle_interval_days" value="{{ old('cycle_interval_days', $feeAssignment->cycle_interval_days ?: 180) }}">

        </div>
      </div>

      <section class="fee-plan-renew-calendar" aria-labelledby="renew-calendar-title">
        <h2 class="fee-plan-section-title" id="renew-calendar-title">Installment Due-Date Calendar</h2>
        <small style="color:var(--text-muted);display:block;margin-bottom:8px">
          Saved Session dates are loaded from <strong>Installment Date Defaults</strong>. Fee Template amounts are divided across the selected installment dates and converted to <strong>net payable amounts</strong> after the renewal increase, scholarship, discount and financial aid; any missing date must be chosen from the calendar.
        </small>
        <div class="installment-calendar-shell">
          <div class="installment-calendar-toolbar">
            <div id="renew-calendar-status" style="font-size:11.5px;color:var(--text-muted)"></div>
            <button type="button" class="btn btn-outline btn-sm" id="renew-reset-calendar"><i class="fas fa-rotate-left"></i> Reload saved dates</button>
          </div>
          <div id="renew-installment-calendar" class="installment-calendar-list"></div>
        </div>
      </section>

      <div class="fee-plan-footer-grid">
      <div class="form-group">
        <label class="form-label">Bank Account (shown on vouchers)</label>
        @if($automaticBankAccount)
          <div class="fee-plan-bank-display">
            <i class="fas fa-building-columns" aria-hidden="true"></i>
            <span>{{ $automaticBankAccount->bank_name }} — {{ $automaticBankAccount->account_title }} ({{ $automaticBankAccount->account_number }})</span>
          </div>
          <small style="color:var(--text-muted);display:block;margin-top:5px">Account is carried forward automatically; if the old plan has none, the current default active account is used.</small>
        @else
          <small style="color:var(--warning)">No bank account is available. Add an active account in Settings before printing vouchers.</small>
        @endif
      </div>

      <div class="form-group">
        <label class="form-label">Notes</label>
        <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
      </div>

      </div>

      <div class="fee-plan-actions">
        <a href="{{ route('fee-assignments.show', $feeAssignment) }}" class="btn btn-outline">Cancel</a>
        <button type="submit" class="btn btn-primary"><i class="fas fa-wand-magic-sparkles"></i> Generate Renewed Plan</button>
      </div>
    </form>
  </div>
</div>

@php
  // Prepare JavaScript payloads in PHP. Keep JSON directives variable-only;
  // commas inside callbacks or helper calls can break Blade's JSON compiler.
  $renewCycleLabelJs = $template->cycleLabel();
  $renewDefaultDatesJs = $defaultInstallmentDates ?? [];
  $renewOldDatesJs = old('installment_dates', []);
  $renewHasValidationErrorsJs = $errors->any()
      && (int) old('cycle_no', $nextCycle) === $nextCycle;
  $renewFeeHeadsJs = $feeHeads->map(function ($head) {
      return [
          'id' => $head->id,
          'name' => $head->name,
          'type' => $head->type,
      ];
  })->values();
  $renewTemplateItemsJs = $template->items
      ->filter(function ($item) {
          return (bool) $item->feeHead;
      })
      ->map(function ($item) {
          return [
              'fee_head_id' => $item->fee_head_id,
              'name' => $item->feeHead->name,
              'type' => $item->feeHead->type,
              'amount' => is_null($item->amount) ? null : (float) $item->amount,
          ];
      })->values();
  $renewOldInstallmentFeeHeadsJs = old('installment_fee_heads', []);
  $renewOldInstallmentCustomItemsJs = old('installment_custom_items', []);
  $renewPreviousIncreaseJs = (float) $feeAssignment->increase_amount;
@endphp
@push('scripts')
<script>
(() => {
  const nextCycle = @json($nextCycle);
  const minimumDueDate = @json($minimumDueDate);
  const cycleLabel = @json($renewCycleLabelJs);
  const defaultDates = @json($renewDefaultDatesJs);
  const oldDates = @json($renewOldDatesJs);
  const hasValidationErrors = @json($renewHasValidationErrorsJs);
  const feeHeads = @json($renewFeeHeadsJs);
  const templateItems = @json($renewTemplateItemsJs);
  const oldInstallmentFeeHeads = @json($renewOldInstallmentFeeHeadsJs);
  const oldInstallmentCustomItems = @json($renewOldInstallmentCustomItemsJs);
  const previousIncrease = @json($renewPreviousIncreaseJs);

  const cyclesInput = document.getElementById('renew-cycles-input');
  const installmentsInput = document.getElementById('renew-installments-input');
  const firstDueInput = document.getElementById('renew-first-due-date');
  const useDefaultsInput = document.getElementById('renew-use-default-installment-dates');
  const calendar = document.getElementById('renew-installment-calendar');
  const status = document.getElementById('renew-calendar-status');
  const resetButton = document.getElementById('renew-reset-calendar');
  const incrementInput = document.querySelector('input[name="increment_amount"]');
  const scholarshipInput = document.querySelector('input[name="scholarship_amount"]');
  const discountInput = document.querySelector('input[name="discount_amount"]');
  const aidInput = document.querySelector('input[name="financial_aid_amount"]');
  let state = JSON.parse(JSON.stringify(oldDates || {}));
  let feeState = JSON.parse(JSON.stringify(oldInstallmentFeeHeads || {}));
  let customState = JSON.parse(JSON.stringify(oldInstallmentCustomItems || {}));
  let appliedAdjustmentsByCycle = {};

  function configured(cycle) {
    if (useDefaultsInput && !useDefaultsInput.checked) return [];
    return (defaultDates[String(cycle)] || defaultDates[cycle] || [])
      .map(date => date && (!minimumDueDate || date >= minimumDueDate) ? date : null);
  }

  function syncFirstDueToSessionDefault(cycle) {
    const defaults = configured(cycle);
    if (defaults.length && firstDueInput) {
      firstDueInput.value = defaults[0];
    }
    return defaults;
  }
  function syncHiddenFirstDueDate() {
    if (!firstDueInput || !calendar) return;
    const first = calendar.querySelector('input[type="date"].renew-installment-date-input');
    firstDueInput.value = first?.value || '';
  }

  function capture() {
    calendar.querySelectorAll('input[type="date"].renew-installment-date-input[data-cycle][data-part]').forEach(input => {
      const cycle = String(input.dataset.cycle);
      const index = parseInt(input.dataset.part, 10) - 1;
      if (!Array.isArray(state[cycle])) state[cycle] = [];
      state[cycle][index] = input.value;
    });
  }

  function escapeHtml(value) {
    return String(value ?? '').replace(/[&<>'"]/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[ch]));
  }
  function slotValue(container, cycle, part) {
    return container[String(cycle)]?.[String(part)];
  }
  function captureFees() {
    if (!calendar.querySelector('.renew-fee-head-row, .renew-custom-row')) return;
    const nextFees = {};
    calendar.querySelectorAll('.renew-fee-head-row').forEach(row => {
      const box = row.querySelector('.renew-fee-checkbox');
      const amount = row.querySelector('.renew-fee-amount');
      if (!box?.checked || !(parseFloat(amount?.value) > 0)) return;
      const c=String(row.dataset.cycle), p=String(row.dataset.part), h=String(row.dataset.head);
      if (!nextFees[c]) nextFees[c]={};
      if (!nextFees[c][p]) nextFees[c][p]={};
      nextFees[c][p][h]=amount.value;
    });
    feeState = nextFees;

    const nextCustom = {};
    calendar.querySelectorAll('.renew-custom-row').forEach(row => {
      const amount = row.querySelector('.renew-custom-amount');
      if (!(parseFloat(amount?.value) > 0)) return;
      const c=String(row.dataset.cycle), p=String(row.dataset.part);
      if (!nextCustom[c]) nextCustom[c]={};
      if (!nextCustom[c][p]) nextCustom[c][p]=[];
      nextCustom[c][p].push({
        fee_head_id: row.querySelector('.renew-custom-head')?.value || '',
        label: row.querySelector('.renew-custom-label')?.value || '',
        amount: amount.value,
      });
    });
    customState = nextCustom;
  }
  function splitAmountForPart(base, parts, part) {
    const totalCents = Math.round((parseFloat(base) || 0) * 100);
    if (totalCents <= 0 || parts < 1 || part < 1 || part > parts) return null;
    const centsPerPart = Math.floor(totalCents / parts);
    const remainder = totalCents - (centsPerPart * parts);
    return (centsPerPart + (part <= remainder ? 1 : 0)) / 100;
  }
  function defaultFeeAmount(item, part, parts) {
    if (item.amount === null || item.amount === undefined || !(parseFloat(item.amount) > 0)) return null;
    if (item.type === 'one_time') return null; // never auto-charge one-time heads on renewal.
    return splitAmountForPart(parseFloat(item.amount), parts, part);
  }
  function customRow(cycle, part, index, values={}) {
    const selected = values.fee_head_id == null ? '' : String(values.fee_head_id);
    const options = feeHeads.map(h => `<option value="${h.id}" ${String(h.id)===selected?'selected':''}>${escapeHtml(h.name)}</option>`).join('');
    return `<div class="renew-custom-row" data-cycle="${cycle}" data-part="${part}">
      <select name="installment_custom_items[${cycle}][${part}][${index}][fee_head_id]" class="form-control renew-custom-head" aria-label="Custom fee head"><option value="">Custom label only</option>${options}</select>
      <input type="text" name="installment_custom_items[${cycle}][${part}][${index}][label]" class="form-control renew-custom-label" aria-label="Custom fee description" value="${escapeHtml(values.label)}" placeholder="Label">
      <input type="number" step="0.01" min="0" name="installment_custom_items[${cycle}][${part}][${index}][amount]" class="form-control renew-custom-amount" aria-label="Custom fee amount" value="${escapeHtml(values.amount)}" placeholder="Amount">
      <button type="button" class="btn btn-outline btn-sm remove-renew-custom" aria-label="Remove custom fee"><i class="fas fa-trash"></i></button>
    </div>`;
  }
  function moneyInputValue(value) {
    if (value === null || value === undefined || !Number.isFinite(Number(value))) return '';
    return Number(Number(value).toFixed(2)).toString();
  }
  function requestedIncrease() {
    return Math.max(0, (parseFloat(previousIncrease) || 0) + (parseFloat(incrementInput?.value) || 0));
  }
  function requestedRelief() {
    return Math.max(0,
      (parseFloat(scholarshipInput?.value) || 0)
      + (parseFloat(discountInput?.value) || 0)
      + (parseFloat(aidInput?.value) || 0)
    );
  }
  function moneyInputsForCycle(cycle) {
    return Array.from(calendar?.querySelectorAll('.renew-fee-amount:not(:disabled), .renew-custom-amount') || [])
      .filter(input => String(input.closest('[data-cycle]')?.dataset.cycle || '') === String(cycle))
      .filter(input => Number.isFinite(parseFloat(input.value)) && parseFloat(input.value) > 0);
  }
  function allocateTotalAcrossInputs(inputs, targetTotal) {
    if (!inputs.length) return;
    const weights = inputs.map(input => Math.max(0, parseFloat(input.value) || 0));
    const weightTotal = weights.reduce((sum, value) => sum + value, 0);
    const targetCents = Math.max(0, Math.round((parseFloat(targetTotal) || 0) * 100));
    if (!(weightTotal > 0)) return;

    const raw = weights.map(weight => targetCents * weight / weightTotal);
    const cents = raw.map(value => Math.floor(value));
    let left = targetCents - cents.reduce((sum, value) => sum + value, 0);
    const order = raw.map((value, index) => ({index, fraction: value - Math.floor(value)}))
      .sort((a, b) => b.fraction - a.fraction || a.index - b.index);
    for (let i = 0; i < order.length && left > 0; i++, left--) cents[order[i].index]++;
    inputs.forEach((input, index) => { input.value = moneyInputValue(cents[index] / 100); });
  }
  function refreshHeadTargets() {
    const grouped = new Map();
    calendar?.querySelectorAll('.renew-fee-head-row').forEach(row => {
      const key = `${row.dataset.cycle}:${row.dataset.head}`;
      if (!grouped.has(key)) grouped.set(key, []);
      grouped.get(key).push(row);
    });
    grouped.forEach(rows => {
      const total = rows.reduce((sum, row) => {
        if (!row.querySelector('.renew-fee-checkbox')?.checked) return sum;
        return sum + (parseFloat(row.querySelector('.renew-fee-amount')?.value) || 0);
      }, 0);
      rows.forEach(row => { row.dataset.headTarget = String(total); });
    });
  }
  function updateInstallmentPayableTotals() {
    calendar?.querySelectorAll('.installment-date-card').forEach(card => {
      const total = Array.from(card.querySelectorAll('.renew-fee-amount:not(:disabled), .renew-custom-amount'))
        .reduce((sum, input) => sum + (parseFloat(input.value) || 0), 0);
      const target = card.querySelector('.renew-installment-payable-total strong');
      if (target) target.textContent = `Rs. ${total.toLocaleString(undefined,{minimumFractionDigits:2,maximumFractionDigits:2})}`;
    });
  }
  function syncCalendarAdjustments() {
    const cycles = 1;
    for (let offset = 0; offset < cycles; offset++) {
      const cycle = nextCycle + offset;
      const key = String(cycle);
      const inputs = moneyInputsForCycle(cycle);
      if (!inputs.length) continue;

      // Validation redirects return values that were already submitted from
      // this net-payable editor, so do not apply the same adjustments twice.
      if (!appliedAdjustmentsByCycle[key] && hasValidationErrors && (oldInstallmentFeeHeads?.[key] || oldInstallmentCustomItems?.[key])) {
        appliedAdjustmentsByCycle[key] = {increase: requestedIncrease(), relief: requestedRelief()};
      }

      const previous = appliedAdjustmentsByCycle[key] || {increase: 0, relief: 0};
      const currentNet = inputs.reduce((sum, input) => sum + (parseFloat(input.value) || 0), 0);
      const baseBeforeAdjustments = Math.max(0, currentNet + previous.relief - previous.increase);
      const nextIncrease = requestedIncrease();
      const grossAfterIncrease = Math.max(0, baseBeforeAdjustments + nextIncrease);
      const nextRelief = Math.min(requestedRelief(), grossAfterIncrease);
      const targetNet = Math.max(0, grossAfterIncrease - nextRelief);

      if (Math.abs(targetNet - currentNet) > 0.004) allocateTotalAcrossInputs(inputs, targetNet);
      appliedAdjustmentsByCycle[key] = {increase: nextIncrease, relief: nextRelief};
    }
    refreshHeadTargets();
    updateInstallmentPayableTotals();
  }
  function rebalanceHeadAcrossCheckedInstallments(cycle, headId) {
    const rows = Array.from(calendar.querySelectorAll('.renew-fee-head-row')).filter(row =>
      String(row.dataset.cycle) === String(cycle) && String(row.dataset.head) === String(headId)
    );
    const existingTarget = parseFloat(rows.find(row => row.dataset.headTarget)?.dataset.headTarget || '');
    const currentTotal = rows.reduce((sum, row) => sum + (parseFloat(row.querySelector('.renew-fee-amount')?.value) || 0), 0);
    const targetTotal = Number.isFinite(existingTarget) && existingTarget > 0 ? existingTarget : currentTotal;
    const selectedRows = rows.filter(row => row.querySelector('.renew-fee-checkbox')?.checked);

    rows.forEach(row => {
      const box = row.querySelector('.renew-fee-checkbox');
      const amount = row.querySelector('.renew-fee-amount');
      if (!amount) return;
      amount.disabled = !box?.checked;
      if (!box?.checked) amount.value = '';
    });

    selectedRows.forEach((row, index) => {
      const amount = row.querySelector('.renew-fee-amount');
      const split = splitAmountForPart(targetTotal, selectedRows.length, index + 1);
      if (amount && split !== null) amount.value = moneyInputValue(split);
    });
    rows.forEach(row => { row.dataset.headTarget = String(targetTotal); });
    updateInstallmentPayableTotals();
  }
  function bindFeeCard(item, cycle, part) {
    item.querySelectorAll('.renew-fee-checkbox').forEach(box => box.addEventListener('change', () => {
      const row = box.closest('.renew-fee-head-row');
      if (row) rebalanceHeadAcrossCheckedInstallments(row.dataset.cycle, row.dataset.head);
    }));
    item.querySelectorAll('.renew-fee-amount').forEach(input => input.addEventListener('input', () => {
      refreshHeadTargets();
      updateInstallmentPayableTotals();
    }));
    item.querySelectorAll('.renew-custom-amount').forEach(input => input.addEventListener('input', updateInstallmentPayableTotals));
    item.querySelectorAll('.remove-renew-custom').forEach(btn => btn.addEventListener('click', () => {
      btn.closest('.renew-custom-row')?.remove();
      updateInstallmentPayableTotals();
    }));
    item.querySelector('.add-renew-custom')?.addEventListener('click', () => {
      const wrap = item.querySelector('.renew-custom-wrap');
      const idx = Date.now() + Math.floor(Math.random() * 1000);
      wrap.insertAdjacentHTML('beforeend', customRow(cycle, part, idx, {}));
      const row = wrap.lastElementChild;
      row?.querySelector('.renew-custom-amount')?.addEventListener('input', updateInstallmentPayableTotals);
      row?.querySelector('.remove-renew-custom')?.addEventListener('click', () => { row.remove(); updateInstallmentPayableTotals(); });
    });
  }

  function dateForSlot(cycle, partIndex) {
    const defaults = configured(cycle);
    return defaults[partIndex] || '';
  }

  function render(forceSeed = false) {
    if (!forceSeed) capture();
    captureFees();
    const cycles = 1;
    const parts = Math.max(1, Math.min(parseInt(installmentsInput.max || '5', 10), parseInt(installmentsInput.value || '1', 10) || 1));
    cyclesInput.value = cycles;
    installmentsInput.value = parts;
    calendar.innerHTML = '';
    let loadedDefaults = 0;

    for (let offset = 0; offset < cycles; offset++) {
      const cycle = nextCycle + offset;
      const cycleKey = String(cycle);
      const defaults = configured(cycle);
      if (forceSeed || !Array.isArray(state[cycleKey])) state[cycleKey] = [];

      const card = document.createElement('div');
      card.className = 'installment-cycle-card';
      card.innerHTML = `<div class="installment-cycle-head"><div class="installment-cycle-title">${cycleLabel} ${cycle}</div><div class="installment-cycle-meta">${parts} installment${parts === 1 ? '' : 's'}</div></div><div class="installment-date-grid"></div>`;
      const grid = card.querySelector('.installment-date-grid');
      grid.dataset.installments = String(parts);

      for (let i = 0; i < parts; i++) {
        const hasDefault = !!defaults[i];
        if (hasDefault) loadedDefaults++;
        let value;
        if (!forceSeed && state[cycleKey][i]) value = state[cycleKey][i];
        else {
          value = dateForSlot(cycle, i);
          state[cycleKey][i] = value;
        }

        const n = i + 1;
        const suffix = [11,12,13].includes(n % 100) ? 'th' : ({1:'st',2:'nd',3:'rd'}[n % 10] || 'th');
        const item = document.createElement('div');
        item.className = 'installment-date-card';
        let saved = slotValue(feeState, cycle, n);
        if (!saved && !hasValidationErrors) {
          delete appliedAdjustmentsByCycle[cycleKey];
          saved = {};
          templateItems.forEach(head => {
            const seeded = defaultFeeAmount(head, n, parts);
            if (seeded !== null) saved[String(head.fee_head_id)] = seeded;
          });
          if (!feeState[String(cycle)]) feeState[String(cycle)] = {};
          feeState[String(cycle)][String(n)] = saved;
        }
        saved = saved || {};
        const feeRows = templateItems.map(head => {
          const key = String(head.fee_head_id);
          const selected = Object.prototype.hasOwnProperty.call(saved, key) && parseFloat(saved[key]) > 0;
          const amount = selected ? saved[key] : '';
          const typeLabel = head.type === 'one_time' ? 'one-time' : (head.type === 'per_cycle' ? 'per semester/year' : 'recurring');
          return `<div class="renew-fee-head-row" data-cycle="${cycle}" data-part="${n}" data-head="${head.fee_head_id}">
            <label class="installment-fee-label"><input type="checkbox" class="renew-fee-checkbox" ${selected?'checked':''}><span>${escapeHtml(head.name)} <small style="color:var(--text-muted)">(${typeLabel})</small></span></label>
            <input type="number" step="0.01" min="0" name="installment_fee_heads[${cycle}][${n}][${head.fee_head_id}]" class="form-control renew-fee-amount" value="${escapeHtml(amount)}" ${selected?'':'disabled'} placeholder="Amount">
          </div>`;
        }).join('');
        const savedCustom = slotValue(customState, cycle, n);
        const customRows = Array.isArray(savedCustom) ? savedCustom.map((row,idx)=>customRow(cycle,n,idx,row)).join('') : '';
        item.innerHTML = `<div class="installment-date-heading"><span>${n}${suffix} Installment</span><span class="installment-date-source ${hasDefault ? '' : 'manual'}">${hasDefault ? 'Session default' : 'Choose date'}</span></div>
          <input type="date" aria-label="${cycleLabel} ${cycle}, installment ${n} due date" class="form-control renew-installment-date-input" name="installment_dates[${cycle}][]" data-cycle="${cycle}" data-part="${n}" value="${escapeHtml(value || '')}" min="${minimumDueDate || ''}" step="1" required>
          <div class="installment-fee-section-title"><i class="fas fa-list-check"></i> Fee heads in this installment</div>
          <div>${feeRows || '<div style="font-size:10.5px;color:var(--text-muted)">No template heads. Add one below.</div>'}</div>
          <div class="renew-custom-wrap">${customRows}</div>
          <button type="button" class="btn btn-outline btn-sm add-renew-custom"><i class="fas fa-plus"></i> Add other fee head / custom</button>
          <div class="renew-installment-payable-total"><span style="font-weight:800">Installment payable after relief</span><strong style="color:var(--primary)">Rs. 0.00</strong></div>`;
        bindFeeCard(item, cycle, n);
        grid.appendChild(item);
      }

      calendar.appendChild(card);
    }

    const total = cycles * parts;
    syncCalendarAdjustments();
    status.innerHTML = `<strong>${total}</strong> installment${total === 1 ? '' : 's'} · amounts shown below are <strong>net payable after renewal increase / scholarship / discount / aid</strong>${loadedDefaults ? ` · <strong>${loadedDefaults}</strong> dates from Session defaults` : ''}.`;
    syncHiddenFirstDueDate();
  }

  cyclesInput.addEventListener('input', () => render());
  installmentsInput.addEventListener('input', () => {
    capture();
    captureFees();
    feeState = {};
    customState = {};
    appliedAdjustmentsByCycle = {};
    calendar.innerHTML = '';
    render();
  });
  const syncAdjustments = () => { syncCalendarAdjustments(); };
  incrementInput?.addEventListener('input', syncAdjustments);
  scholarshipInput?.addEventListener('input', syncAdjustments);
  discountInput?.addEventListener('input', syncAdjustments);
  aidInput?.addEventListener('input', syncAdjustments);

  useDefaultsInput?.addEventListener('change', () => {
    if (useDefaultsInput.checked) {
      const defaults = syncFirstDueToSessionDefault(nextCycle);
    }
    render(true);
  });
  resetButton.addEventListener('click', () => {
    if (useDefaultsInput?.checked) {
      syncFirstDueToSessionDefault(nextCycle);
    }
    render(true);
  });

  calendar.addEventListener('change', event => {
    if (event.target?.matches('input[type="date"].renew-installment-date-input')) syncHiddenFirstDueDate();
  });

  if (!hasValidationErrors && useDefaultsInput?.checked) {
    const defaults = syncFirstDueToSessionDefault(nextCycle);
    render(true);
  } else {
    render(false);
  }

})();
</script>
@endpush
@endsection
