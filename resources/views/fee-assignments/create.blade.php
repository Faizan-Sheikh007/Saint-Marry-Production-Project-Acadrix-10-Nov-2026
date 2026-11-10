@extends('layouts.app')
@section('title', 'Assign Fee Plan - ' . $student->name)
@section('page_title', 'Assign Fee Plan')
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
  <a href="{{ route('students.show', $student) }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
  <div style="font-size:13px;color:var(--text-muted)">
    @if($isAdmission)
      <span style="color:var(--success);font-weight:700"><i class="fas fa-check-circle"></i> Step 1 — Admission</span>
      &nbsp;→&nbsp; <strong style="color:var(--primary)">Step 2 — Fee Plan</strong>
    @else
      <strong style="color:var(--primary)">New Fee Plan — {{ $progression['cycle_label'] }} {{ $defaultCycleNo }}</strong>
    @endif
  </div>
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
  <div class="card-body" style="display:flex;align-items:center;gap:16px">
    @if($student->photo)
      <img src="{{ storage_asset($student->photo) }}" style="width:52px;height:52px;border-radius:50%;object-fit:cover">
    @else
      <div style="width:52px;height:52px;border-radius:50%;background:var(--primary);display:flex;align-items:center;justify-content:center;color:#fff;font-weight:800">{{ strtoupper(substr($student->name,0,2)) }}</div>
    @endif
    <div>
      <div style="font-weight:800;font-size:16px">{{ $student->name }} <span style="color:var(--text-muted);font-weight:500">({{ $student->student_id }})</span></div>
      <div style="font-size:12px;color:var(--text-muted)">{{ $student->schoolClass->name ?? '—' }}{{ $student->section ? ' - Section '.$student->section : '' }} &middot; {{ ucfirst($student->level) }}</div>
    </div>
  </div>
</div>

@include('fee-assignments._progression-notice')

@if($progression['is_complete'])
  <a href="{{ route('students.show', $student) }}" class="btn btn-outline">Back to student profile</a>
@elseif($templates->isEmpty())
<div class="card"><div class="card-body">
  <p style="color:var(--text-muted)">No active fee template is available yet. Fee Heads and Fee Templates are managed by the Accounts department — ask them to create or activate one, then come back here.</p>
  @if(auth()->user()?->isAccountant())
  <a href="{{ route('fee-templates.create') }}" class="btn btn-primary btn-sm" style="margin-top:12px"><i class="fas fa-plus"></i> Create Fee Template</a>
  @endif
</div></div>
@else
<form action="{{ route('fee-assignments.store', $student) }}" method="POST" id="fee-plan-form" class="fee-plan-layout">
  @csrf
  <input type="hidden" name="manage_installment_fee_heads" value="1">
  <input type="hidden" name="installment_amount_basis" value="net">
  <input type="hidden" name="admission_flow" value="{{ !empty($isAdmission) ? 1 : 0 }}">
  @if($lmsCredentials ?? null)
    <input type="hidden" name="lms_login_email" value="{{ $lmsCredentials['email'] }}">
    <input type="hidden" name="lms_login_password_plain" value="{{ $lmsCredentials['password'] }}">
    <div class="alert alert-info" style="margin-bottom:16px">
      <i class="fas fa-graduation-cap"></i>
      LMS login <strong>{{ $lmsCredentials['email'] }}</strong> will be printed on this student's installment plan slip.
    </div>
  @endif
  <div class="fee-plan-setup-grid">

    <div class="card">
      <div class="card-header"><div class="card-title"><i class="fas fa-file-invoice" style="color:var(--primary);margin-right:8px"></i>Total Fee (Class / Semester)</div></div>
      <div class="card-body">
        <div class="form-group">
          <label class="form-label">Fee Template *</label>
          <select name="fee_template_id" id="template-select" class="form-control" required>
            <option value="">Select a fee template</option>
            @foreach($templateGroups as $groupLabel => $groupTemplates)
              <optgroup label="{{ $groupLabel }}">
                @foreach($groupTemplates as $t)
                  @php
                    $scopeLabel = $t->schoolClass
                        ? (($t->schoolClass->program->name ?? 'Program').' › '.$t->schoolClass->section_label)
                        : 'All Programs / Classes';
                  @endphp
                  <option value="{{ $t->id }}" data-total="{{ $t->totalAmount() }}" {{ old('fee_template_id') == $t->id ? 'selected' : '' }}>
                    {{ $t->name }} ({{ ucfirst($t->billing_cycle) }}) — {{ $scopeLabel }} — Rs. {{ number_format($t->totalAmount(), 0) }}
                  </option>
                @endforeach
              </optgroup>
            @endforeach
          </select>
          <div style="font-size:11px;color:var(--text-muted);margin-top:5px;line-height:1.45">
            All active fee templates are available here. Templates for <strong>{{ $student->program->name ?? 'this program' }}</strong>
            / <strong>{{ $progression['cycle_label'] }} {{ $defaultCycleNo }}</strong> are listed first.
          </div>
        </div>

        <div id="template-items-preview" style="font-size:13px;color:var(--text-muted);margin-top:6px"></div>
        <small id="template-items-hint" style="display:none;color:var(--text-muted)">Template amounts are the gross fee. The installment editor below automatically converts them to the student's net payable amounts after scholarship and financial aid. You can still edit any installment before saving.</small>
        <div id="template-cycle-info" style="font-size:12px;color:var(--primary);margin-top:8px;display:none;background:rgba(37,99,235,0.06);border-radius:8px;padding:8px 10px"></div>

        {{-- Scholarship and Financial Aid are stored separately on the fee plan
             so both can be reported per student. Legacy increase/discount are
             disabled on this create/admission screen. --}}
        <input type="hidden" name="increase_amount" id="pct-increase" value="0">
        <input type="hidden" name="discount_amount" value="0">

        <div class="grid grid-2" style="gap:12px;margin-top:16px">
          <div class="form-group">
            <label class="form-label">Scholarship (Rs.)</label>
            <input type="number" step="1" min="0" name="scholarship_amount" id="pct-scholarship" class="form-control" value="{{ old('scholarship_amount', 0) }}" placeholder="e.g. 5000">
            <small style="color:var(--text-muted)">Scholarship granted to this student. It is deducted from the fee and saved separately for reporting.</small>
          </div>
          <div class="form-group">
            <label class="form-label">Financial Aid (Rs.)</label>
            <input type="number" step="1" min="0" name="financial_aid_amount" id="pct-financial-aid" class="form-control" value="{{ old('financial_aid_amount', 0) }}" placeholder="e.g. 5000">
            <small style="color:var(--text-muted)">Financial aid granted to this student. It is deducted from the fee and saved separately for reporting.</small>
          </div>
        </div>

        @if($student->financial_aid_requested)
        <div style="margin-top:10px;padding:10px 12px;border:1px dashed var(--warning);border-radius:8px;background:rgba(245,158,11,0.06)">
          <div style="font-size:12px;color:var(--warning);font-weight:700">
            <i class="fas fa-hand-holding-dollar"></i> This applicant requested financial assistance
            @if($student->financial_aid_type) — {{ $student->financial_aid_type }} @endif
          </div>
          @if($student->financial_aid_notes)
            <div style="font-size:12px;color:var(--text-muted);margin-top:5px">{{ $student->financial_aid_notes }}</div>
          @endif
        </div>
        @endif

        <div style="background:var(--bg);border-radius:10px;padding:14px 16px;margin-top:10px">
          <div style="display:flex;justify-content:space-between;font-size:13px;padding:2px 0"><span>Selected installment charges <small style="color:var(--text-muted)">(gross / before relief)</small></span><strong id="sum-base">Rs. 0.00</strong></div>
          <div style="display:flex;justify-content:space-between;font-size:13px;padding:2px 0"><span>After scholarship</span><strong id="sum-scholarship">Rs. 0.00</strong></div>
          <div style="display:flex;justify-content:space-between;font-size:13px;padding:2px 0"><span>After financial aid</span><strong id="sum-financial-aid">Rs. 0.00</strong></div>
          <div style="display:flex;justify-content:space-between;font-size:15px;padding:6px 0 0;border-top:1px dashed var(--border);margin-top:6px"><span>Net payable <span id="sum-total-label">total</span></span><strong id="sum-total" style="color:var(--primary)">Rs. 0.00</strong></div>
        </div>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title"><i class="fas fa-layer-group" style="color:var(--primary);margin-right:8px"></i>Installments &amp; Schedule</div></div>
      <div class="card-body">

        <input type="hidden" name="generate_scope" value="single">
        <input type="hidden" name="cycle_increment_amount" value="0">

        <div class="grid grid-2" style="gap:12px">
          <div class="form-group" id="cycle-no-row">
            <label class="form-label">Creating Fee Plan For *</label>
            <input type="number" min="1" name="cycle_no" id="cycle-no-input" class="form-control" value="{{ $defaultCycleNo }}" readonly aria-readonly="true" required>
            <div style="font-size:11px;color:var(--text-muted);margin-top:4px">{{ $progression['cycle_label'] }} {{ $defaultCycleNo }} is selected automatically from the student's existing plans and cannot be changed here.</div>
          </div>
          <div class="form-group" id="installments-row">
            <label class="form-label" id="installments-label">Number of Installments per Semester/Year *</label>
            <input type="number" min="1" max="{{ $maxInstallments }}" name="installments" id="installments-input" class="form-control" value="{{ old('installments', count($defaultInstallmentDates[$defaultCycleNo] ?? []) ?: 1) }}" required>
            <small style="color:var(--text-muted)">How many payments (parts) <strong>each</strong> semester/year is split into — e.g. 2 for "Part 1" &amp; "Part 2", or 4 for a quarterly plan. Applies only to this semester/year. Max {{ $maxInstallments }} (Settings → Fee Policy).</small>
          </div>
        </div>

        @if($outstandingDues > 0)
        <div class="form-group" style="background:#fef3c7;border:1px solid #f59e0b;border-radius:8px;padding:10px 12px">
          <label style="display:flex;gap:8px;align-items:flex-start;cursor:pointer;margin:0">
            <input type="checkbox" name="carry_forward_dues" value="1" {{ old('carry_forward_dues') ? 'checked' : '' }} style="margin-top:3px">
            <span style="font-size:13px;color:#92400e">
              <strong>Carry forward outstanding dues</strong> — this student currently owes <strong>Rs. {{ number_format($outstandingDues, 0) }}</strong> from earlier fee plans. Tick to add it as a "Previous Dues Carried Forward" line on this plan's first installment.
            </span>
          </label>
        </div>
        @endif

        @php $hasDefaultInstallmentDates = !empty($defaultInstallmentDates ?? []); @endphp
        <div class="form-group" style="border:1px solid {{ $hasDefaultInstallmentDates ? 'rgba(16,185,129,.35)' : 'var(--border)' }};border-radius:10px;padding:11px 12px;background:{{ $hasDefaultInstallmentDates ? 'rgba(16,185,129,.05)' : 'var(--bg)' }}">
          <input type="hidden" name="use_default_installment_dates" value="0">
          <label style="display:flex;gap:8px;align-items:flex-start;cursor:pointer;margin:0">
            <input type="checkbox" id="use-default-installment-dates" name="use_default_installment_dates" value="1" {{ old('use_default_installment_dates', 1) ? 'checked' : '' }} style="margin-top:3px">
            <span style="font-size:12.5px">
              <strong>Use session default installment dates</strong><br>
              @if($hasDefaultInstallmentDates)
                <span style="color:var(--text-muted)">Configured dates for this Session{{ $student->program ? ' / '.$student->program->name : '' }} are loaded directly into the installment calendar below. You can still change any student's date here without changing the saved defaults.</span>
              @else
                <span style="color:var(--text-muted)">No matching default dates are currently configured for this student's Session / Program. Keep this selected to use Session defaults automatically whenever they are available; any missing installment date must be chosen from the calendar below.</span>
                <a href="{{ route('installment-date-defaults.index') }}" style="color:var(--primary);margin-left:4px">Set defaults</a>
                @if($defaultScheduleDiagnostics ?? null)
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
              @endif
            </span>
          </label>
        </div>

        <input type="hidden" name="billing_mode" value="split">
        <input type="hidden" name="first_due_date" id="first-due-date" value="{{ old('first_due_date', $defaultFirstDueDate ?? now()->toDateString()) }}">
        <input type="hidden" name="frequency_days" value="30">
        <input type="hidden" name="cycle_interval_days" value="180">

        <div class="form-group" style="background:rgba(37,99,235,.05);border:1px solid rgba(37,99,235,.18);border-radius:10px;padding:10px 12px">
          <div style="font-size:12.5px;color:var(--text-muted);line-height:1.55">
            <i class="fas fa-calculator" style="color:var(--primary)"></i>
            <strong style="color:var(--text)">Automatic installment split:</strong>
            fee amounts are loaded from the selected Fee Template, divided across the <strong>Number of Installments</strong>, then scholarship and financial aid are applied immediately. The amounts shown in each installment card are the <strong>net payable amounts</strong>. Due dates are loaded from <strong>Installment Date Defaults</strong>; if a saved date is missing, choose it from the calendar below.
          </div>
        </div>


      </div>
    </div>

  </div>

  <section class="card fee-plan-calendar-card" id="installment-calendar-wrap" aria-labelledby="installment-calendar-title">
    <div class="card-header"><h2 class="card-title" id="installment-calendar-title"><i class="fas fa-calendar-days" style="color:var(--primary);margin-right:8px" aria-hidden="true"></i>Installment Due-Date Calendar</h2></div>
    <div class="card-body">
      <p class="fee-plan-help">Review each installment's date and fee heads below. Saved Session dates are loaded from <strong>Installment Date Defaults</strong>; choose any missing dates. Every amount shown is the <strong>net payable after scholarship / aid</strong>.</p>
          <div class="installment-calendar-shell">
            <div class="installment-calendar-toolbar">
              <div id="installment-calendar-status" style="font-size:11.5px;color:var(--text-muted)"></div>
              <button type="button" class="btn btn-outline btn-sm" id="reset-installment-calendar"><i class="fas fa-rotate-left"></i> Reload saved dates</button>
            </div>
            <div id="installment-calendar" class="installment-calendar-list"></div>
          </div>
    </div>
  </section>

  <div class="card fee-plan-details-card">
    <div class="card-body">
      <div class="fee-plan-footer-grid">
        <div class="form-group">
          <label class="form-label">Bank Account (shown on vouchers)</label>
          @if($automaticBankAccount)
            <div class="fee-plan-bank-display">
              <i class="fas fa-building-columns" aria-hidden="true"></i>
              <span>{{ $automaticBankAccount->bank_name }} — {{ $automaticBankAccount->account_title }} ({{ $automaticBankAccount->account_number }})
              @if($automaticBankAccount->is_default)<span style="margin-left:6px;color:var(--text-muted)">· Default</span>@endif</span>
            </div>
            <small style="color:var(--text-muted);display:block;margin-top:5px">Added automatically from Settings. Mark another active account as Default in Settings to change it.</small>
          @else
            <small style="color:var(--warning)">No bank accounts configured yet. <a href="{{ route('settings.index') }}" style="color:var(--primary)">Add one in Settings</a>.</small>
          @endif
        </div>

        <div class="form-group">
          <label class="form-label">Notes</label>
          <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
        </div>
      </div>
      <p class="fee-plan-help" style="margin-bottom:0">The installment amounts must add up to the <strong>Net payable</strong> in the fee summary above. The bank account will appear on the generated vouchers.</p>
    </div>
  </div>

  <div class="fee-plan-actions">
    <a href="{{ route('students.show', $student) }}" class="btn btn-outline">Skip for now</a>
    <button type="submit" class="btn btn-primary"><i class="fas fa-wand-magic-sparkles"></i> Create {{ $progression['cycle_label'] }} {{ $defaultCycleNo }} Fee Plan</button>
  </div>
</form>
@endif

@php
  // Prepare JavaScript payloads in PHP. Keep JSON directives variable-only;
  // commas inside callbacks or helper calls can break Blade's JSON compiler.
  $templatesForJs = $templates->mapWithKeys(function ($t) {
      $items = $t->items->map(function ($i) {
          return [
              'name'        => $i->feeHead->name,
              'amount'      => is_null($i->amount) ? null : (float) $i->amount,
              'type'        => $i->feeHead->type,
              'fee_head_id' => $i->fee_head_id,
          ];
      })->values();

      return [
          $t->id => [
              'total'          => (float) $t->totalAmount(),
              'items'          => $items,
              'billing_cycle'  => $t->billing_cycle,
              'parts_per_cycle'=> $t->parts_per_cycle ?? 1,
              'total_cycles'   => $t->total_cycles,
              'cycle_label'    => $t->cycleLabel(),
          ],
      ];
  });

  $feeHeadsForInstallmentsJs = $feeHeads->map(function ($head) {
      return [
          'id' => $head->id,
          'name' => $head->name,
          'type' => $head->type,
      ];
  })->values();
  $defaultInstallmentDatesJs = $defaultInstallmentDates ?? [];
  $oldInstallmentDatesJs = old('installment_dates', []);
  $oldInstallmentFeeHeadsJs = old('installment_fee_heads', []);
  $oldInstallmentCustomItemsJs = old('installment_custom_items', []);
  $chargedFeeHeadIdsJs = $chargedFeeHeadIds ?? [];
  $oldInstallmentsValueJs = old('installments');
  $hasValidationErrorsJs = $errors->any()
      && (int) old('cycle_no', $defaultCycleNo) === $defaultCycleNo;
@endphp
@push('scripts')
<script>
(() => {
const templates = @json($templatesForJs);
const programTotalCycles = @json($programTotalCycles);
const feeHeadsForInstallments = @json($feeHeadsForInstallmentsJs);
const defaultInstallmentDates = @json($defaultInstallmentDatesJs);
const oldInstallmentDates = @json($oldInstallmentDatesJs);
const oldInstallmentFeeHeads = @json($oldInstallmentFeeHeadsJs);
const oldInstallmentCustomItems = @json($oldInstallmentCustomItemsJs);
const nextCycle = @json($defaultCycleNo);
const minimumDueDate = @json($minimumDueDate);
const chargedFeeHeadIds = new Set(@json($chargedFeeHeadIdsJs).map(id => String(id)));
const oldInstallmentsValue = @json($oldInstallmentsValueJs);
let hasValidationErrors = @json($hasValidationErrorsJs);

const templateSelect = document.getElementById('template-select');
if (!templateSelect) return; // Completed program or no active templates.
const preview = document.getElementById('template-items-preview');
const cycleInfo = document.getElementById('template-cycle-info');
const pctIncrease = document.getElementById('pct-increase');
const pctScholarship = document.getElementById('pct-scholarship');
const pctFinancialAid = document.getElementById('pct-financial-aid');
const sumTotalLabel = document.getElementById('sum-total-label');
const installmentsInput = document.getElementById('installments-input');
const installmentsLabel = document.getElementById('installments-label');
const cycleNoInput = document.getElementById('cycle-no-input');
const cycleNoRow = document.getElementById('cycle-no-row');
const firstDueInput = document.querySelector('input[name="first_due_date"]');
const useDefaultsInput = document.getElementById('use-default-installment-dates');
const calendarWrap = document.getElementById('installment-calendar-wrap');
const calendar = document.getElementById('installment-calendar');
const calendarStatus = document.getElementById('installment-calendar-status');
const resetCalendarButton = document.getElementById('reset-installment-calendar');
const cycleIncrementAmountInput = document.querySelector('input[name="cycle_increment_amount"]');

let installmentDateState = JSON.parse(JSON.stringify(oldInstallmentDates || {}));
let installmentFeeHeadState = JSON.parse(JSON.stringify(oldInstallmentFeeHeads || {}));
let installmentCustomItemState = JSON.parse(JSON.stringify(oldInstallmentCustomItems || {}));
// Tracks which plan-level adjustments are already reflected in the visible
// installment amounts. The editor now shows NET payable values, not the gross
// Fee Template values, so changing scholarship/aid must re-balance the cards
// without deducting the same relief repeatedly.
let appliedAdjustmentsByCycle = {};


function fmt(n){ return 'Rs. ' + n.toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2}); }
function currentTemplate() { return templates[templateSelect.value]; }
function clampInstallments(value) {
  return Math.max(1, Math.min(parseInt(installmentsInput.max || '5', 10), parseInt(value || '1', 10) || 1));
}
function configuredDates(cycle) {
  if (useDefaultsInput && !useDefaultsInput.checked) return [];
  return (defaultInstallmentDates[String(cycle)] || defaultInstallmentDates[cycle] || [])
    .map(date => date && (!minimumDueDate || date >= minimumDueDate) ? date : null);
}
function syncFirstDueToSessionDefault(cycle) {
  const defaults = configuredDates(cycle);
  if (defaults.length && firstDueInput) {
    firstDueInput.value = defaults[0];
  }
  return defaults;
}
function syncHiddenFirstDueDate() {
  if (!firstDueInput || !calendar) return;
  const first = calendar.querySelector('input[type="date"].installment-date-input');
  firstDueInput.value = first?.value || '';
}
function cycleRange() {
  // One server-selected cycle. Template changes cannot restart at Semester 1.
  return [nextCycle];
}
function captureCalendar() {
  calendar?.querySelectorAll('input[type="date"].installment-date-input[data-cycle][data-part]').forEach(input => {
    const cycle = String(input.dataset.cycle);
    const partIndex = parseInt(input.dataset.part, 10) - 1;
    if (!Array.isArray(installmentDateState[cycle])) installmentDateState[cycle] = [];
    installmentDateState[cycle][partIndex] = input.value;
  });
}
function escapeInstallmentHtml(value) {
  return String(value ?? '').replace(/[&<>'"]/g, ch => ({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[ch]));
}
function slotState(container, cycle, part, create = false) {
  const c = String(cycle), p = String(part);
  if (create && (!container[c] || typeof container[c] !== 'object')) container[c] = {};
  if (create && (!container[c][p] || typeof container[c][p] !== 'object')) container[c][p] = {};
  return container[c]?.[p];
}
function captureInstallmentFees() {
  if (!calendar?.querySelector('.installment-fee-head-row, .installment-custom-row')) return;
  const nextHeads = {};
  calendar.querySelectorAll('.installment-fee-head-row').forEach(row => {
    const box = row.querySelector('.installment-fee-checkbox');
    const amount = row.querySelector('.installment-fee-amount');
    if (!box?.checked || !amount || !(parseFloat(amount.value) > 0)) return;
    const cycle = String(row.dataset.cycle), part = String(row.dataset.part), head = String(row.dataset.head);
    if (!nextHeads[cycle]) nextHeads[cycle] = {};
    if (!nextHeads[cycle][part]) nextHeads[cycle][part] = {};
    nextHeads[cycle][part][head] = amount.value;
  });
  installmentFeeHeadState = nextHeads;

  const nextCustom = {};
  calendar.querySelectorAll('.installment-custom-row').forEach(row => {
    const amount = row.querySelector('.installment-custom-amount');
    if (!(parseFloat(amount?.value) > 0)) return;
    const cycle = String(row.dataset.cycle), part = String(row.dataset.part);
    if (!nextCustom[cycle]) nextCustom[cycle] = {};
    if (!nextCustom[cycle][part]) nextCustom[cycle][part] = [];
    nextCustom[cycle][part].push({
      fee_head_id: row.querySelector('.installment-custom-head')?.value || '',
      label: row.querySelector('.installment-custom-label')?.value || '',
      amount: amount.value,
    });
  });
  installmentCustomItemState = nextCustom;
}
function splitAmountForPart(base, parts, part) {
  const totalCents = Math.round((parseFloat(base) || 0) * 100);
  if (totalCents <= 0 || parts < 1 || part < 1 || part > parts) return null;
  const centsPerPart = Math.floor(totalCents / parts);
  const remainder = totalCents - (centsPerPart * parts);
  return (centsPerPart + (part <= remainder ? 1 : 0)) / 100;
}
function defaultHeadForSlot(item, cycle, part, parts, rangeStart) {
  if (item.amount === null || item.amount === undefined || item.amount === '') return null;
  const base = parseFloat(item.amount);
  if (!(base > 0)) return null;

  // One-time heads belong only to the first generated cycle. Every applicable
  // template amount is then divided across that cycle's selected due dates.
  if (item.type === 'one_time') {
    if (chargedFeeHeadIds.has(String(item.fee_head_id))) return null;
    if (cycle !== rangeStart) return null;
  }
  return splitAmountForPart(base, parts, part);
}
function customRowHtml(cycle, part, index, values = {}) {
  const selectedId = values.fee_head_id == null ? '' : String(values.fee_head_id);
  const options = feeHeadsForInstallments.map(h => `<option value="${h.id}" ${String(h.id) === selectedId ? 'selected' : ''}>${escapeInstallmentHtml(h.name)}</option>`).join('');
  return `<div class="installment-custom-row" data-cycle="${cycle}" data-part="${part}">
    <select name="installment_custom_items[${cycle}][${part}][${index}][fee_head_id]" class="form-control installment-custom-head" aria-label="Custom fee head"><option value="">Custom label only</option>${options}</select>
    <input type="text" name="installment_custom_items[${cycle}][${part}][${index}][label]" class="form-control installment-custom-label" aria-label="Custom fee description" value="${escapeInstallmentHtml(values.label)}" placeholder="Label">
    <input type="number" step="0.01" min="0" name="installment_custom_items[${cycle}][${part}][${index}][amount]" class="form-control installment-custom-amount" aria-label="Custom fee amount" value="${escapeInstallmentHtml(values.amount)}" placeholder="Amount">
    <button type="button" class="btn btn-outline btn-sm remove-installment-custom" aria-label="Remove custom fee"><i class="fas fa-trash"></i></button>
  </div>`;
}
function templateHeadById(headId) {
  return (currentTemplate()?.items || []).find(head => String(head.fee_head_id) === String(headId));
}
function moneyInputValue(value) {
  if (value === null || value === undefined || !Number.isFinite(Number(value))) return '';
  return Number(Number(value).toFixed(2)).toString();
}
function requestedIncreaseForCycle(cycle) {
  const cycles = cycleRange();
  const rangeStart = cycles[0] || Math.max(1, parseInt(cycleNoInput?.value || '1', 10) || 1);
  const baseIncrease = parseFloat(pctIncrease?.value) || 0;
  const cycleStep = parseFloat(cycleIncrementAmountInput?.value) || 0;
  return Math.max(0, baseIncrease + (cycleStep * Math.max(0, Number(cycle) - rangeStart)));
}
function requestedReliefForCycle() {
  const scholarship = parseFloat(pctScholarship?.value) || 0;
  const aid = parseFloat(pctFinancialAid?.value) || 0;
  const legacyDiscount = parseFloat(document.querySelector('input[name="discount_amount"]')?.value) || 0;
  return Math.max(0, scholarship + aid + legacyDiscount);
}
function moneyInputsForCycle(cycle) {
  return Array.from(calendar?.querySelectorAll('.installment-fee-amount:not(:disabled), .installment-custom-amount') || [])
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
  if (!calendar) return;
  const grouped = new Map();
  calendar.querySelectorAll('.installment-fee-head-row').forEach(row => {
    const key = `${row.dataset.cycle}:${row.dataset.head}`;
    if (!grouped.has(key)) grouped.set(key, []);
    grouped.get(key).push(row);
  });
  grouped.forEach(rows => {
    const total = rows.reduce((sum, row) => {
      if (!row.querySelector('.installment-fee-checkbox')?.checked) return sum;
      return sum + (parseFloat(row.querySelector('.installment-fee-amount')?.value) || 0);
    }, 0);
    rows.forEach(row => { row.dataset.headTarget = String(total); });
  });
}
function updateInstallmentPayableTotals() {
  calendar?.querySelectorAll('.installment-date-card').forEach(card => {
    const total = Array.from(card.querySelectorAll('.installment-fee-amount:not(:disabled), .installment-custom-amount'))
      .reduce((sum, input) => sum + (parseFloat(input.value) || 0), 0);
    const target = card.querySelector('.installment-payable-total strong');
    if (target) target.textContent = fmt(total);
  });
}
function syncCalendarAdjustments() {
  if (!calendar) return;
  const cycles = cycleRange();

  cycles.forEach(cycle => {
    const key = String(cycle);
    const inputs = moneyInputsForCycle(cycle);
    if (!inputs.length) return;

    // After a validation redirect, old installment values already came from
    // this net-payable editor. Mark them as adjusted before doing any scaling.
    if (!appliedAdjustmentsByCycle[key] && hasValidationErrors && (oldInstallmentFeeHeads?.[key] || oldInstallmentCustomItems?.[key])) {
      appliedAdjustmentsByCycle[key] = {
        increase: requestedIncreaseForCycle(cycle),
        relief: requestedReliefForCycle(),
      };
    }

    const previous = appliedAdjustmentsByCycle[key] || {increase: 0, relief: 0};
    const currentNet = inputs.reduce((sum, input) => sum + (parseFloat(input.value) || 0), 0);
    const baseBeforeAdjustments = Math.max(0, currentNet + previous.relief - previous.increase);
    const nextIncrease = requestedIncreaseForCycle(cycle);
    const requestedRelief = requestedReliefForCycle();
    const grossAfterIncrease = Math.max(0, baseBeforeAdjustments + nextIncrease);
    const nextRelief = Math.min(requestedRelief, grossAfterIncrease);
    const targetNet = Math.max(0, grossAfterIncrease - nextRelief);

    if (Math.abs(targetNet - currentNet) > 0.004) allocateTotalAcrossInputs(inputs, targetNet);
    appliedAdjustmentsByCycle[key] = {increase: nextIncrease, relief: nextRelief};
  });

  refreshHeadTargets();
  updateInstallmentPayableTotals();
}
function rebalanceTemplateHeadAcrossCheckedInstallments(cycle, headId) {
  if (!calendar) return;

  const rows = Array.from(calendar.querySelectorAll('.installment-fee-head-row')).filter(row =>
    String(row.dataset.cycle) === String(cycle) && String(row.dataset.head) === String(headId)
  );
  const existingTarget = parseFloat(rows.find(row => row.dataset.headTarget)?.dataset.headTarget || '');
  const currentTotal = rows.reduce((sum, row) => sum + (parseFloat(row.querySelector('.installment-fee-amount')?.value) || 0), 0);
  const targetTotal = Number.isFinite(existingTarget) && existingTarget > 0 ? existingTarget : currentTotal;
  const selectedRows = rows.filter(row => row.querySelector('.installment-fee-checkbox')?.checked);

  rows.forEach(row => {
    const box = row.querySelector('.installment-fee-checkbox');
    const amount = row.querySelector('.installment-fee-amount');
    // Toggling a checkbox re-splits this head evenly from scratch, so any
    // manual per-installment edits made earlier no longer apply.
    delete row.dataset.manualLocked;
    if (!amount) return;
    amount.disabled = !box?.checked;
    if (!box?.checked) amount.value = '';
  });

  selectedRows.forEach((row, index) => {
    const amount = row.querySelector('.installment-fee-amount');
    const split = splitAmountForPart(targetTotal, selectedRows.length, index + 1);
    if (amount && split !== null) amount.value = moneyInputValue(split);
  });
  rows.forEach(row => { row.dataset.headTarget = String(targetTotal); });
  updateInstallmentPayableTotals();
}
// When the user manually edits one installment's amount for a fee head, the
// head's current net-payable total must stay fixed. Instead of letting
// that edit shrink/grow the overall payable total, push the difference onto
// the OTHER installments of that same fee head (within the same cycle) that
// the user hasn't manually touched, splitting it evenly across them.
function redistributeAfterManualEdit(input) {
  const row = input.closest('.installment-fee-head-row');
  if (!row || !calendar) return;
  row.dataset.manualLocked = '1';

  const cycle = row.dataset.cycle, headId = row.dataset.head;
  const base = parseFloat(row.dataset.headTarget || '');
  if (!(base > 0)) return;

  const rows = Array.from(calendar.querySelectorAll('.installment-fee-head-row')).filter(r =>
    String(r.dataset.cycle) === String(cycle) && String(r.dataset.head) === String(headId) && r.querySelector('.installment-fee-checkbox')?.checked
  );
  if (rows.length < 2) return;

  // Clamp the edited value to the template's fixed total for this fee head.
  let changedVal = parseFloat(input.value);
  if (Number.isNaN(changedVal) || changedVal < 0) changedVal = 0;
  if (changedVal > base) changedVal = base;
  const clampedStr = moneyInputValue(changedVal);
  if (input.value !== clampedStr) input.value = clampedStr;

  const lockedRows = rows.filter(r => r.dataset.manualLocked === '1');
  const freeRows = rows.filter(r => r.dataset.manualLocked !== '1');
  if (!freeRows.length) return; // every installment for this head has been hand-edited; nothing left to absorb the difference

  const lockedSum = lockedRows.reduce((sum, r) => sum + (parseFloat(r.querySelector('.installment-fee-amount')?.value) || 0), 0);
  const remaining = Math.max(0, base - lockedSum);

  freeRows.forEach((r, index) => {
    const amount = r.querySelector('.installment-fee-amount');
    const split = splitAmountForPart(remaining, freeRows.length, index + 1);
    if (amount) amount.value = moneyInputValue(split ?? 0);
  });
}
function bindInstallmentFeeCard(item, cycle, part) {
  item.querySelectorAll('.installment-fee-checkbox').forEach(box => box.addEventListener('change', () => {
    const row = box.closest('.installment-fee-head-row');
    if (row) rebalanceTemplateHeadAcrossCheckedInstallments(row.dataset.cycle, row.dataset.head);
    recalc();
  }));
  item.querySelectorAll('.installment-fee-amount').forEach(input => input.addEventListener('input', () => {
    redistributeAfterManualEdit(input);
    recalc();
  }));
  item.querySelectorAll('.installment-custom-amount').forEach(input => input.addEventListener('input', recalc));
  item.querySelectorAll('.remove-installment-custom').forEach(btn => btn.addEventListener('click', () => { btn.closest('.installment-custom-row')?.remove(); recalc(); }));
  item.querySelector('.add-installment-custom')?.addEventListener('click', () => {
    const wrap = item.querySelector('.installment-custom-wrap');
    const idx = Date.now() + Math.floor(Math.random() * 1000);
    wrap.insertAdjacentHTML('beforeend', customRowHtml(cycle, part, idx, {}));
    const row = wrap.lastElementChild;
    row.querySelector('.installment-custom-amount')?.addEventListener('input', recalc);
    row.querySelector('.remove-installment-custom')?.addEventListener('click', () => { row.remove(); recalc(); });
  });
}

function dateForSlot(cycle, partIndex) {
  const defaults = configuredDates(cycle);
  return defaults[partIndex] || '';
}
function renderInstallmentCalendar(forceSeed = false) {
  if (!calendar) return;
  if (!forceSeed) captureCalendar();
  captureInstallmentFees();

  const cycles = cycleRange();
  const parts = clampInstallments(installmentsInput.value);
  installmentsInput.value = parts;
  calendar.innerHTML = '';

  if (!cycles.length) {
    calendarStatus.textContent = 'Select a fee template / valid generation range to build the installment calendar.';
    calendar.innerHTML = '<div style="padding:14px;text-align:center;color:var(--text-muted);font-size:12px">No installment dates to display yet.</div>';
    return;
  }

  let defaultCount = 0;
  const rangeStart = cycles[0];

  cycles.forEach(cycle => {
    const defaults = configuredDates(cycle);
    const cycleKey = String(cycle);
    if (forceSeed || !Array.isArray(installmentDateState[cycleKey])) {
      installmentDateState[cycleKey] = [];
    }

    const t = currentTemplate();
    const cycleLabel = t?.cycle_label || 'Semester / Part';
    const card = document.createElement('div');
    card.className = 'installment-cycle-card';
    card.innerHTML = `
      <div class="installment-cycle-head">
        <div class="installment-cycle-title">${cycleLabel} ${cycle}</div>
        <div class="installment-cycle-meta">${parts} installment${parts === 1 ? '' : 's'}</div>
      </div>
      <div class="installment-date-grid"></div>`;

    const grid = card.querySelector('.installment-date-grid');
    grid.dataset.installments = String(parts);
    for (let i = 0; i < parts; i++) {
      const hasSavedDefault = !!defaults[i];
      if (hasSavedDefault) defaultCount++;

      let value;
      if (!forceSeed && Array.isArray(installmentDateState[cycleKey]) && installmentDateState[cycleKey][i]) {
        value = installmentDateState[cycleKey][i];
      } else {
        value = dateForSlot(cycle, i);
        installmentDateState[cycleKey][i] = value;
      }

      const part = i + 1;
      const item = document.createElement('div');
      item.className = 'installment-date-card';
      item.style.minWidth = '0';

      const tItems = t?.items || [];
      let savedHeads = slotState(installmentFeeHeadState, cycle, part, false);
      if (!savedHeads && !hasValidationErrors) {
        // This cycle is being seeded again from gross template values (for
        // example after changing the selected cycle/range), so any adjustment
        // marker left from an earlier render must not be reused.
        delete appliedAdjustmentsByCycle[cycleKey];
        savedHeads = {};
        tItems.forEach(head => {
          const seeded = defaultHeadForSlot(head, cycle, part, parts, rangeStart);
          if (seeded !== null) savedHeads[String(head.fee_head_id)] = seeded;
        });
        if (!installmentFeeHeadState[String(cycle)]) installmentFeeHeadState[String(cycle)] = {};
        installmentFeeHeadState[String(cycle)][String(part)] = savedHeads;
      }
      savedHeads = savedHeads || {};
      const feeRows = tItems.map(head => {
        const headKey = String(head.fee_head_id);
        const selected = Object.prototype.hasOwnProperty.call(savedHeads, headKey) && parseFloat(savedHeads[headKey]) > 0;
        const amount = selected ? savedHeads[headKey] : '';
        const typeLabel = head.type === 'one_time' ? 'one-time' : (head.type === 'per_cycle' ? 'per semester/year' : 'recurring');
        return `<div class="installment-fee-head-row" data-cycle="${cycle}" data-part="${part}" data-head="${head.fee_head_id}">
          <label class="installment-fee-label"><input type="checkbox" class="installment-fee-checkbox" ${selected ? 'checked' : ''}><span>${escapeInstallmentHtml(head.name)} <small style="color:var(--text-muted)">(${typeLabel})</small></span></label>
          <input type="number" step="0.01" min="0" class="form-control installment-fee-amount" name="installment_fee_heads[${cycle}][${part}][${head.fee_head_id}]" value="${escapeInstallmentHtml(amount)}" ${selected ? '' : 'disabled'} placeholder="Amount">
        </div>`;
      }).join('');
      const savedCustom = slotState(installmentCustomItemState, cycle, part, false);
      const customRows = Array.isArray(savedCustom) ? savedCustom.map((row, idx) => customRowHtml(cycle, part, idx, row)).join('') : '';

      item.innerHTML = `
        <div class="installment-date-heading">
          <span>${part}${[11,12,13].includes(part%100) ? 'th' : ({1:'st',2:'nd',3:'rd'}[part%10] || 'th')} Installment</span>
          <span class="installment-date-source ${hasSavedDefault ? '' : 'manual'}">${hasSavedDefault ? 'Session default' : 'Choose date'}</span>
        </div>
        <input type="date" aria-label="${cycleLabel} ${cycle}, installment ${part} due date" class="form-control installment-date-input" name="installment_dates[${cycle}][]" data-cycle="${cycle}" data-part="${part}" value="${escapeInstallmentHtml(value || '')}" min="${minimumDueDate || ''}" step="1" required>
        <div class="installment-fee-section-title"><i class="fas fa-list-check"></i> Fee heads in this installment</div>
        <div class="installment-fee-heads">${feeRows || '<div style="font-size:10.5px;color:var(--text-muted)">This template has no fee heads; add one below.</div>'}</div>
        <div class="installment-custom-wrap">${customRows}</div>
        <button type="button" class="btn btn-outline btn-sm add-installment-custom"><i class="fas fa-plus"></i> Add other fee head / custom</button>
        <div class="installment-payable-total"><span style="font-weight:800">Installment payable after relief</span><strong style="color:var(--primary)">Rs. 0.00</strong></div>`;
      bindInstallmentFeeCard(item, cycle, part);
      grid.appendChild(item);
    }

    calendar.appendChild(card);
  });

  const totalSlots = cycles.length * parts;
  calendarStatus.innerHTML = `<strong>${totalSlots}</strong> installment${totalSlots === 1 ? '' : 's'} · amounts shown below are <strong>net payable after scholarship / aid</strong>${defaultCount ? ` · <strong>${defaultCount}</strong> dates loaded from Session defaults` : ''}.`;
  syncHiddenFirstDueDate();
  syncCalendarAdjustments();
  recalc();
}

// Template fee amounts seed the installment cards automatically; the browser
// splits each applicable amount across the selected due dates.
let lastRenderedTemplateKey = null;
function renderItemOverrides() {
  const t = currentTemplate();
  const key = templateSelect.value;
  if (!t) {
    preview.innerHTML = '';
    document.getElementById('template-items-hint').style.display = 'none';
    lastRenderedTemplateKey = null;
    return;
  }
  if (key === lastRenderedTemplateKey) return;
  lastRenderedTemplateKey = key;
  preview.innerHTML = t.items.map(i => {
    const typeLabel = i.type === 'one_time' ? 'one-time' : (i.type === 'per_cycle' ? 'per semester/year' : 'recurring');
    return `<div style="display:flex;justify-content:space-between;gap:8px;padding:5px 0;border-bottom:1px dashed var(--border)"><span>${escapeInstallmentHtml(i.name)} <small>(${typeLabel})</small></span><strong>${i.amount === null ? 'Not priced' : fmt(parseFloat(i.amount))}</strong></div>`;
  }).join('') || '<div style="font-size:12px;color:var(--text-muted)">No fee heads are saved in this template.</div>';
  document.getElementById('template-items-hint').style.display = 'block';
}

function currentItemsTotal() {
  let sum = 0;
  calendar?.querySelectorAll('.installment-fee-amount:not(:disabled), .installment-custom-amount').forEach(input => {
    const v = parseFloat(input.value);
    if (!Number.isNaN(v) && v > 0) sum += v;
  });
  return sum;
}

function recalc() {
  const t = currentTemplate();
  if (!t) {
    preview.innerHTML = '';
    cycleInfo.style.display = 'none';
    document.getElementById('sum-base').textContent = fmt(0);
    document.getElementById('sum-scholarship')?.replaceChildren(document.createTextNode(fmt(0)));
    document.getElementById('sum-financial-aid')?.replaceChildren(document.createTextNode(fmt(0)));
    document.getElementById('sum-total').textContent = fmt(0);
    return;
  }

  renderItemOverrides();

  const totalCycles = t.total_cycles || programTotalCycles || null;
  if (totalCycles) {
    cycleInfo.style.display = 'block';
    cycleInfo.innerHTML = t.total_cycles
      ? `<i class="fas fa-circle-info"></i> This template is set up for <strong>${t.total_cycles} ${t.cycle_label}(s)</strong>, ${t.parts_per_cycle} part(s) each.`
      : `<i class="fas fa-circle-info"></i> Template doesn't set a cycle count — using the Program's <strong>${programTotalCycles} ${t.cycle_label}(s)</strong> instead.`;
  } else {
    cycleInfo.style.display = 'none';
  }

  const netPayable = currentItemsTotal();
  const cyclesList = cycleRange();
  const cycleCount = Math.max(1, cyclesList.length);
  const scholarshipAmount = (parseFloat(pctScholarship?.value) || 0) * cycleCount;
  const financialAidAmount = (parseFloat(pctFinancialAid?.value) || 0) * cycleCount;
  const appliedReliefTotal = cyclesList.reduce((sum, cycle) => {
    return sum + (appliedAdjustmentsByCycle[String(cycle)]?.relief || 0);
  }, 0);
  const grossBeforeRelief = netPayable + appliedReliefTotal;
  const afterScholarship = Math.max(0, grossBeforeRelief - scholarshipAmount);
  const afterFinancialAid = Math.max(0, netPayable);

  document.getElementById('sum-base').textContent = fmt(grossBeforeRelief);
  document.getElementById('sum-scholarship').textContent = fmt(afterScholarship);
  document.getElementById('sum-financial-aid').textContent = fmt(afterFinancialAid);
  document.getElementById('sum-total').textContent = fmt(netPayable);
  updateInstallmentPayableTotals();

  sumTotalLabel.textContent = 'total';
}

function applyTemplateDefaults(fromUserChange = false) {
  const t = currentTemplate();
  if (!t) {
    toggleScopeUI();
    renderInstallmentCalendar();
    return;
  }

  const firstCycle = cycleRange()[0] || Math.max(1, parseInt(cycleNoInput?.value || '1', 10) || 1);
  const defaultCount = configuredDates(firstCycle).length;
  if (fromUserChange || oldInstallmentsValue === null) {
    installmentsInput.value = defaultCount || t.parts_per_cycle || 1;
  }


  toggleScopeUI();
  renderInstallmentCalendar(false);
}

function toggleScopeUI() {
  cycleNoInput.value = nextCycle;
  cycleNoRow.style.display = 'block';
  installmentsLabel.textContent = 'Number of Installments in this Semester/Year *';
  calendarWrap.style.display = 'block';
  recalc();
  renderInstallmentCalendar();
}

templateSelect.addEventListener('change', function () {
  hasValidationErrors = false;
  captureCalendar();
  installmentFeeHeadState = {};
  installmentCustomItemState = {};
  appliedAdjustmentsByCycle = {};
  calendar.innerHTML = '';
  lastRenderedTemplateKey = null;
  applyTemplateDefaults(true);
});
const syncAdjustmentsAndRecalc = () => { syncCalendarAdjustments(); recalc(); };
pctIncrease?.addEventListener('input', syncAdjustmentsAndRecalc);
pctScholarship?.addEventListener('input', syncAdjustmentsAndRecalc);
pctFinancialAid?.addEventListener('input', syncAdjustmentsAndRecalc);
cycleIncrementAmountInput?.addEventListener('input', syncAdjustmentsAndRecalc);
installmentsInput.addEventListener('input', function () {
  captureCalendar();
  captureInstallmentFees();
  installmentFeeHeadState = {};
  installmentCustomItemState = {};
  appliedAdjustmentsByCycle = {};
  calendar.innerHTML = '';
  renderInstallmentCalendar();
});
useDefaultsInput?.addEventListener('change', () => {
  const cycle = cycleRange()[0] || Math.max(1, parseInt(cycleNoInput?.value || '1', 10) || 1);
  if (useDefaultsInput.checked) {
    const defaults = syncFirstDueToSessionDefault(cycle);
  }
  renderInstallmentCalendar(true);
});
resetCalendarButton?.addEventListener('click', () => {
  const cycle = cycleRange()[0] || Math.max(1, parseInt(cycleNoInput?.value || '1', 10) || 1);
  if (useDefaultsInput?.checked) {
    const defaults = syncFirstDueToSessionDefault(cycle);
  }
  renderInstallmentCalendar(true);
});

calendar?.addEventListener('change', event => {
  if (event.target?.matches('input[type="date"].installment-date-input')) syncHiddenFirstDueDate();
});

// Initial UI: on a fresh GET, Session defaults are authoritative and should
// be visibly selected immediately. On a validation redirect, preserve the
// operator's entered dates so editing remains safe and predictable.
toggleScopeUI();
applyTemplateDefaults(false);
if (!hasValidationErrors && useDefaultsInput?.checked) {
  const cycle = cycleRange()[0] || Math.max(1, parseInt(cycleNoInput?.value || '1', 10) || 1);
  const defaults = syncFirstDueToSessionDefault(cycle);
  renderInstallmentCalendar(true);
} else {
  renderInstallmentCalendar(false);
}

})();
</script>
@endpush
@endsection