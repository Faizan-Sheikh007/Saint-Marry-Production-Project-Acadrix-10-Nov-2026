@extends('layouts.app')
@section('title', 'Vouchers')
@section('page_title', 'Vouchers & Installments')
@section('content')

@php $mode = $mode ?? 'programs'; @endphp

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:12px">
  <div style="font-size:22px;font-weight:800">Vouchers &amp; Installments</div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <a href="{{ route('vouchers.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> New Voucher</a>
    <button class="btn btn-outline" onclick="document.getElementById('installmentModal').style.display='flex'"><i class="fas fa-layer-group"></i> Installment Plan</button>
    <button class="btn btn-outline" onclick="document.getElementById('bulkReceiptsModal').style.display='flex'"><i class="fas fa-receipt"></i> Bulk Paid Receipts</button>
  </div>
</div>

{{-- Bulk Paid Fee Receipts (export by day / week) --}}
<div id="bulkReceiptsModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);align-items:center;justify-content:center;z-index:500">
  <div class="card" style="max-width:420px;width:92%">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
      <div class="card-title">Export Paid Fee Receipts</div>
      <button onclick="document.getElementById('bulkReceiptsModal').style.display='none'" class="btn btn-outline btn-sm"><i class="fas fa-times"></i></button>
    </div>
    <form method="GET" action="{{ route('vouchers.bulk-receipts') }}" target="_blank" class="card-body">
      <div class="form-group">
        <label>Range</label>
        <select name="range" class="form-control" onchange="document.getElementById('brDayField').style.display=this.value==='day'?'block':'none';document.getElementById('brWeekField').style.display=this.value==='week'?'block':'none'">
          <option value="day">By Day</option>
          <option value="week">By Week</option>
        </select>
      </div>
      <div class="form-group" id="brDayField">
        <label>Date</label>
        <input type="date" name="date" class="form-control" value="{{ now()->format('Y-m-d') }}">
      </div>
      <div class="form-group" id="brWeekField" style="display:none">
        <label>Any day in the week</label>
        <input type="date" name="week_start" class="form-control" value="{{ now()->format('Y-m-d') }}">
      </div>
      <button type="submit" class="btn btn-primary" style="width:100%;margin-top:10px"><i class="fas fa-file-export"></i> Generate Receipts</button>
    </form>
  </div>
</div>

{{-- Tabs: Browse (Program -> Student) is the default; All Vouchers is the classic filterable table --}}
<div class="voucher-tabs">
  <a href="{{ route('vouchers.index') }}" class="voucher-tab {{ $mode !== 'table' ? 'active' : '' }}"><i class="fas fa-sitemap"></i> Browse by Program</a>
  <a href="{{ route('vouchers.index', ['all' => 1]) }}" class="voucher-tab {{ $mode === 'table' ? 'active' : '' }}"><i class="fas fa-table-list"></i> All Vouchers</a>
</div>

@if(session('success'))
  <div class="alert alert-success" style="margin-bottom:16px;display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
    <span>{{ session('success') }}</span>
    @if(session('generated_batch_id'))
      <a href="{{ route('vouchers.generated-batch.print', session('generated_batch_id')) }}" target="_blank" class="btn btn-primary btn-sm" style="white-space:nowrap">
        <i class="fas fa-download"></i> Download / Print Generated Vouchers
      </a>
    @endif
  </div>
@endif
@if(session('error'))<div class="alert alert-danger" style="margin-bottom:16px">{{ session('error') }}</div>@endif
@if($errors->any())
  <div class="alert alert-danger" style="margin-bottom:16px">
    {{ $errors->first() }}
  </div>
@endif

{{-- Split one unpaid challan into a desired first amount + an automatically
     created remaining challan. Both are real voucher numbers and printable. --}}
<div id="splitChallanModal" class="split-challan-modal" style="display:none">
  <div class="split-challan-dialog">
    <div class="split-challan-head">
      <div>
        <div class="split-challan-title"><i class="fas fa-scissors"></i> Split Challan</div>
        <div class="split-challan-sub">Create two separately printable challans without changing the student's total fee.</div>
      </div>
      <button type="button" class="btn btn-outline btn-sm" onclick="closeSplitChallanModal()"><i class="fas fa-times"></i></button>
    </div>
    <form method="POST" id="splitChallanForm" class="split-challan-body">
      @csrf
      <div class="split-challan-current">
        <span>Current challan</span>
        <strong id="splitChallanNo">—</strong>
        <span class="split-challan-total">Rs. <b id="splitChallanTotal">0</b></span>
      </div>
      <label class="split-challan-label">First challan amount (Rs.)
        <input type="number" name="amount" id="splitChallanAmount" min="0.01" step="0.01" class="form-control" required placeholder="e.g. 10000">
      </label>
      <div class="split-challan-preview">
        <div><span>First printable challan</span><strong>Rs. <b id="splitFirstAmount">0</b></strong></div>
        <i class="fas fa-plus"></i>
        <div><span>Remaining printable challan</span><strong>Rs. <b id="splitRemainingAmount">0</b></strong></div>
      </div>
      <div class="split-challan-note"><i class="fas fa-circle-info"></i> The total includes previous unpaid dues. Previous dues stay on the original challan and are not duplicated on the new one. The admin may choose any two positive amounts. Previous dues are allocated between the two challans without creating duplicate debt. No recorded or pending payment is allowed.</div>
      <div class="split-challan-actions">
        <button type="button" class="btn btn-outline" onclick="closeSplitChallanModal()">Cancel</button>
        <button type="submit" class="btn btn-primary"><i class="fas fa-scissors"></i> Split &amp; Create Challans</button>
      </div>
    </form>
  </div>
</div>

{{-- ============ STEPS 1 & 2 - SESSION -> PROGRAM -> STUDENTS (AJAX) ============
     Session-tab clicks, program-card clicks, the Step-2 breadcrumb/search, and
     browser back/forward all swap this panel in place via fetch() — see the
     script block below. No full page reload once you're inside this tab. --}}
@if($mode === 'programs' || $mode === 'students')
<div id="browse-panel">
  @include('vouchers._browse-panel')
</div>
@endif

{{-- ====================== STEP 3 - STUDENT, SEMESTER-WISE ====================== --}}
@if($mode === 'student')

<div class="breadcrumb-row">
  <a href="{{ route('vouchers.index') }}"><i class="fas fa-sitemap"></i> Programs</a>
  <i class="fas fa-chevron-right"></i>
  <a href="{{ route('vouchers.index', ['program_id' => $program->id ?? '']) }}">{{ $program->name ?? '-' }}</a>
  <i class="fas fa-chevron-right"></i>
  <span>{{ $student->name }}</span>
</div>

@php
  // Calculated in VoucherController from the authoritative active plan rows.
  // The fallback keeps the view safe if it is rendered directly in a test.
  $studentPending = $studentPending ?? $student->outstandingBalance();
@endphp

<div class="student-banner">
  <div class="student-banner-body">
    @if($student->photo)
      <img src="{{ storage_asset($student->photo) }}" alt="{{ $student->name }}" class="student-banner-avatar">
    @else
      <div class="student-banner-avatar student-banner-avatar-fallback">{{ strtoupper(substr($student->name, 0, 2)) }}</div>
    @endif
    <div>
      <div class="student-banner-name">{{ $student->name }} <span class="student-banner-id">({{ $student->student_id }})</span></div>
      <div class="student-banner-meta"><i class="fas fa-graduation-cap"></i> {{ $program->name ?? '-' }} &middot; {{ $student->academicYear->name ?? '-' }}</div>
      <div class="student-banner-chip"><i class="fas fa-layer-group"></i> Present: {{ $student->schoolClass->name ?? '-' }}{{ $student->schoolClass?->section ? ' ('.$student->schoolClass->section.')' : '' }}</div>
    </div>
    <div class="student-banner-balance">
      <div class="student-banner-balance-lbl">Outstanding Balance</div>
      <div class="student-banner-balance-val" style="color:{{ $studentPending > 0 ? '#ffd7d7' : '#d4f7dd' }}">Rs. {{ number_format($studentPending, 0) }}</div>
      @if($studentPending > 0)
        <span class="student-banner-badge danger"><i class="fas fa-triangle-exclamation"></i> Payment due</span>
      @else
        <span class="student-banner-badge success"><i class="fas fa-circle-check"></i> All clear</span>
      @endif
      <div style="font-size:12px;margin-top:10px;color:#d4f7dd">Credited Amount (Available): <strong>Rs. {{ number_format((float) $creditOverview['available'], 2) }}</strong></div>
    </div>
  </div>
</div>

<div class="semester-grid">
  @for($cycle = 1; $cycle <= $totalCycles; $cycle++)
    @php
      $asg = $cycleOwner[$cycle] ?? null;
      $cycleVouchers = $asg ? $asg->vouchersForCycle($cycle) : collect();
      $cyclePaid = $cycleVouchers->isNotEmpty() && $cycleVouchers->every(fn ($v) => $v->balance() <= 0);
      $cyclePending = $cycleVouchers->sum(fn ($v) => $v->balance());
      $canSplit = $asg && $cycleVouchers->isNotEmpty() && $asg->canSplitCycle($cycle);
      $label = $asg ? $asg->termLabelFor($cycle) : ('Semester ' . $cycle . ' of ' . $totalCycles);
      $cardStatusClass = !$asg ? '' : ($cyclePaid ? 'status-paid' : ($cyclePending > 0 ? 'status-due' : 'status-pending'));
    @endphp
    <div class="semester-card {{ !$asg && (int) $cycle !== $creditOverview['display_cycle'] ? 'is-empty' : '' }} {{ $cardStatusClass }}">
      <div class="semester-head">
        <div>
          <div class="semester-title">{{ $label }}</div>
          @if($asg)
            <div class="semester-sub">{{ $asg->feeTemplate->name ?? '-' }} &middot; {{ $cycleVouchers->pluck('part_no')->unique()->count() ?: $asg->installments }} installment(s)</div>
          @endif
        </div>
        @if($cycleVouchers->isNotEmpty())
          @if($cyclePaid)
            <span class="badge success">Paid</span>
          @elseif($cyclePending > 0)
            <span class="badge danger">Rs. {{ number_format($cyclePending, 0) }} due</span>
          @else
            <span class="badge gray">-</span>
          @endif
        @elseif($asg)
          <span class="badge warning">Not generated</span>
        @else
          <span class="badge gray">Not assigned</span>
        @endif
      </div>

      @if($cycleVouchers->isNotEmpty())
      <div class="semester-installments">
        @foreach($cycleVouchers->groupBy('part_no')->sortKeys() as $partNo => $partLines)
          @php
            // Normally a part has one voucher_no. After Accounts uses "Split
            // Challan", the same installment intentionally has 2+ voucher
            // numbers, each of which must stay independently viewable/printable.
            $challanGroups = $partLines->groupBy('voucher_no')->sortBy(function ($group) {
              $firstRow = $group->first();
              $sequence = (int) ($firstRow->split_sequence ?: 1);
              return str_pad((string) $sequence, 5, '0', STR_PAD_LEFT) . '-' . str_pad((string) $group->min('id'), 12, '0', STR_PAD_LEFT);
            });
            $partHasSplitChallans = $challanGroups->count() > 1 || $partLines->contains(fn ($v) => !empty($v->split_root_voucher_no));
          @endphp
          @foreach($challanGroups as $voucherNo => $lines)
            @php
              $first = $lines->first();
              $lineTotal = round($lines->sum(fn ($v) => $v->total()), 2);
              $lineReceived = round($lines->sum(fn ($v) => (float) $v->paid_amount), 2);
              $linePending = max(0, round($lineTotal - $lineReceived, 2));
              $lineCarryForward = round($first->carryForwardBalance(), 2);
              // Main voucher amount is what is actually collectible now: the
              // current installment's REMAINING balance, plus arrears only once
              // this installment has reached its due date.
              $lineDisplayAmount = max(0, $linePending + $lineCarryForward);
              $lineStatus = $first->combinedStatusLabel();
              if ($lineCarryForward > 0 && $linePending <= 0) {
                $lineStatus = $first->due_date && $first->due_date->lt(now()->startOfDay()) ? 'Overdue' : 'Due';
              }
              $lineStatusClass = match ($lineStatus) {
                'Paid' => 'success',
                'Overdue' => 'danger',
                'Partially Paid', 'Due', 'Active' => 'warning',
                'Cancelled' => 'gray',
                default => 'warning',
              };
              $splitSequence = (int) ($first->split_sequence ?: 1);
              $canSplitThisChallan = auth()->user()->isAccountant() && $first->canSplitChallan();
            @endphp
            <div class="installment-row {{ $partHasSplitChallans ? 'is-split-challan' : '' }}">
              <div class="installment-row-main">
                <div class="installment-row-title">
                  {{ $asg->installmentLabelFor($cycle, $partNo ?? $first->installment_no) }}
                  @if($partHasSplitChallans)
                    <span class="split-challan-chip"><i class="fas fa-scissors"></i> Split {{ $splitSequence }}</span>
                  @endif
                </div>
                <div class="installment-row-sub">{{ $first->voucher_no }}</div>
                <div class="installment-row-sub">Due {{ optional($first->due_date)->format('d M, Y') }}{{ $first->month ? ' - ' . $first->month : '' }}</div>
                <div class="installment-row-sub">{{ $lines->pluck('feeHead.name')->filter()->implode(', ') ?: $lines->pluck('title')->unique()->implode(', ') }}</div>
                @if($lineCarryForward > 0)
                  <div class="installment-row-sub" style="margin-top:3px;font-weight:700;color:var(--danger)">+ Rs. {{ number_format($lineCarryForward, 0) }} previous balance carried forward</div>
                @endif
                @if($lineStatus === 'Partially Paid')
                  <div class="installment-row-sub" style="margin-top:4px;font-weight:600">
                    Total: Rs. {{ number_format($lineTotal, 0) }} &middot;
                    <span style="color:var(--success)">Received: Rs. {{ number_format($lineReceived, 0) }}</span> &middot;
                    <span style="color:var(--danger)">Pending: Rs. {{ number_format($linePending, 0) }}</span>
                  </div>
                @endif
              </div>
              <div class="installment-row-amount">Rs. {{ number_format($lineDisplayAmount, 0) }}</div>
              <span class="badge {{ $lineStatusClass }} sm">{{ $lineStatus }}</span>
              <div class="installment-row-actions">
                <a href="{{ route('vouchers.show', $first) }}" class="btn btn-outline btn-sm" title="View"><i class="fas fa-eye"></i></a>
                <a href="{{ route('vouchers.print', $first) }}" target="_blank" class="btn btn-outline btn-sm" title="Print"><i class="fas fa-print"></i></a>
                @if($canSplitThisChallan)
                  <button type="button"
                          class="btn btn-outline btn-sm split-challan-trigger"
                          title="Split current installment balance; earlier carried-forward dues stay on the original challan"
                          data-action="{{ route('vouchers.split-challan', $first) }}"
                          data-voucher-no="{{ $first->voucher_no }}"
                          data-total="{{ number_format($lineDisplayAmount, 2, '.', '') }}"
                          data-carry="{{ number_format($lineCarryForward, 2, '.', '') }}">
                    <i class="fas fa-scissors"></i> Split
                  </button>
                @endif
              </div>
            </div>
          @endforeach
        @endforeach
      </div>
      @endif

      @if((int) $cycle === $creditOverview['display_cycle'])
        @include('partials.student-credit')
      @elseif(!$asg && (int) $cycle === $creditOverview['display_cycle'] + 1 && $creditOverview['available_minor'] > 0)
        <div class="student-credit-next">Available student credit will be applied when this semester's vouchers are generated. No second payment is needed for the credited portion.</div>
      @endif

      @if($asg)
        <div class="semester-links">
          <a href="{{ route('fee-assignments.show', $asg) }}"><i class="fas fa-layer-group"></i> Full Plan Details</a>
          <a href="{{ route('fee-assignments.plan', $asg) }}" target="_blank"><i class="fas fa-print"></i> Print Challans</a>
          @if($cycleVouchers->isNotEmpty())
            <a href="{{ route('fee-assignments.cycle-summary', [$asg, $cycle]) }}" target="_blank"><i class="fas fa-file-invoice-dollar"></i> Download Summary</a>
          @endif
        </div>

        @php
          $dateParts = $cycleVouchers->groupBy(fn ($v) => (int) ($v->part_no ?: $v->installment_no))->sortKeys();
          $editableDateParts = $dateParts->filter(function ($lines) {
              $liveLines = $lines->where('status', '!=', 'cancelled');
              return $liveLines->isNotEmpty() && !$liveLines->contains(fn ($v) => (float) $v->paid_amount > 0);
          });
        @endphp
        @if($editableDateParts->isNotEmpty())
        <form action="{{ route('fee-assignments.update-cycle-due-dates', [$asg, $cycle]) }}" method="POST" class="semester-manage-form" style="margin-top:10px">
          @csrf @method('PUT')
          <div class="semester-manage-title"><i class="fas fa-calendar-pen"></i> Student-Specific Due Dates</div>
          <div style="font-size:10.5px;color:var(--text-muted);margin:3px 0 8px">Change due dates for this student only. This does not change the session defaults. Installments with a recorded payment are locked.</div>
          <div class="semester-manage-grid">
            @foreach($editableDateParts as $partNo => $lines)
              @php $dateSample = $lines->firstWhere('status', '!=', 'cancelled') ?? $lines->first(); @endphp
              <label>{{ ordinal($partNo) }} Installment
                <input type="date" name="due_dates[{{ $partNo }}]" value="{{ optional($dateSample->due_date)->toDateString() }}" class="form-control" required>
              </label>
            @endforeach
          </div>
          @if($editableDateParts->count() < $dateParts->count())
            <div style="font-size:10px;color:var(--text-muted);margin-top:6px"><i class="fas fa-lock"></i> Paid/partially-paid or cancelled installments are hidden and cannot have their due dates changed.</div>
          @endif
          <button type="submit" class="btn btn-outline btn-sm" style="margin-top:8px"><i class="fas fa-calendar-check"></i> Save Due Dates</button>
        </form>
        @endif

        @if($canSplit)
        {{-- Change this semester's installment months / count, increase or decrease its fee, and add/remove fee heads --}}
        @php $cycleByHead = $cycleVouchers->groupBy('fee_head_id'); @endphp
        <form action="{{ route('fee-assignments.split-cycle', [$asg, $cycle]) }}" method="POST" class="semester-manage-form">
          @csrf
          <input type="hidden" name="manage_fee_heads" value="1">
          <div class="semester-manage-title"><i class="fas fa-sliders"></i> Manage Installments for this Semester</div>
          <div class="semester-manage-grid">
            <label>Installments
              <div class="stepper">
                <button type="button" class="stepper-btn" data-step="-1">-</button>
                <input type="number" name="installments" min="1" max="12" value="{{ $cycleVouchers->pluck('part_no')->unique()->count() }}" class="form-control">
                <button type="button" class="stepper-btn" data-step="1">+</button>
              </div>
            </label>
            <label>First Due Date
              <input type="date" name="first_due_date" value="{{ optional($cycleVouchers->sortBy('due_date')->first())->due_date?->toDateString() }}" class="form-control">
            </label>
            <input type="hidden" name="frequency_days" value="30">
            <label>Increase / Decrease Fee By (Rs.)
              <input type="number" name="fee_increase_amount" step="1" value="0" class="form-control" title="Positive increases this semester's fee, negative decreases it, by this flat amount.">
            </label>
          </div>
          <label class="semester-manage-months">Month Labels <span>(optional, comma-separated - e.g. Jan 2027, Feb 2027)</span>
            <input type="text" name="month_labels" class="form-control" placeholder="Leave blank to auto-label">
          </label>

          <div class="semester-manage-fee-heads">
            <div class="semester-manage-title" style="margin-top:12px"><i class="fas fa-list-check"></i> Fee Heads in this Semester</div>
            <div class="semester-fee-head-list">
              @forelse($cycleByHead as $feeHeadId => $lines)
                @php
                  $sample = $lines->first();
                  $headLabel = $sample->feeHead->name ?? $sample->title;
                  $checkboxValue = ($feeHeadId === '' || is_null($feeHeadId)) ? '__none__' : $feeHeadId;
                @endphp
                <label class="fee-head-check">
                  <input type="checkbox" name="keep_fee_head_ids[]" value="{{ $checkboxValue }}" checked>
                  {{ $headLabel }} <span>(Rs. {{ number_format($lines->avg('amount'), 0) }}/installment)</span>
                </label>
              @empty
                <div style="font-size:11px;color:var(--text-muted)">No fee heads on this semester yet.</div>
              @endforelse
            </div>
            <div style="font-size:10px;color:var(--text-muted);margin-top:4px">Uncheck a fee head to remove it from this semester entirely. Leave all checked to keep everything as-is.</div>
          </div>

          <div class="semester-manage-add-heads">
            <div class="semester-manage-title" style="margin-top:12px"><i class="fas fa-plus"></i> Add a Fee Head to this Semester</div>
            @for($row = 0; $row < 2; $row++)
            <div class="semester-manage-grid" style="margin-bottom:6px">
              <label>Fee Head
                <select name="add_items[{{ $row }}][fee_head_id]" class="form-control">
                  <option value="">— Custom / none —</option>
                  @foreach($feeHeads as $fh)
                    <option value="{{ $fh->id }}">{{ $fh->name }}</option>
                  @endforeach
                </select>
              </label>
              <label>Label <span style="font-weight:400">(if custom)</span>
                <input type="text" name="add_items[{{ $row }}][label]" class="form-control" placeholder="e.g. Late Fee">
              </label>
              <label>Total Amount for this Semester
                <input type="number" name="add_items[{{ $row }}][amount]" min="0" step="1" class="form-control" placeholder="Rs.">
              </label>
            </div>
            @endfor
            <div style="font-size:10px;color:var(--text-muted)">Split evenly across this semester's installments. Leave amount blank on a row to skip it.</div>
          </div>

          <button type="submit" class="btn btn-primary btn-sm" style="margin-top:10px"><i class="fas fa-wand-magic-sparkles"></i> Apply Changes</button>
        </form>
        @elseif($asg && $cycleVouchers->isNotEmpty())
          <div class="semester-manage-locked">
            <div><i class="fas fa-lock"></i> Locked — {{ $asg->splitLockReason($cycle) }}</div>
            <form action="{{ route('fee-assignments.toggle-cycle-lock', [$asg, $cycle]) }}" method="POST" class="semester-manage-unlock-form">
              @csrf
              <input type="hidden" name="action" value="unlock">
              <button type="submit" class="btn btn-outline btn-sm" title="Force-unlock this semester if the automatic rule above doesn't match reality">
                <i class="fas fa-lock-open"></i> Force Unlock
              </button>
            </form>
          </div>
        @endif

        @if($asg && $cycleVouchers->isNotEmpty() && $asg->isCycleManuallyUnlocked($cycle))
          <div class="semester-manage-manual-unlocked">
            <div><i class="fas fa-unlock"></i> Manually force-unlocked — the automatic check above is being bypassed.</div>
            <form action="{{ route('fee-assignments.toggle-cycle-lock', [$asg, $cycle]) }}" method="POST" class="semester-manage-unlock-form">
              @csrf
              <input type="hidden" name="action" value="lock">
              <button type="submit" class="btn btn-outline btn-sm" title="Restore the automatic lock rule for this semester">
                <i class="fas fa-lock"></i> Restore Auto-Lock
              </button>
            </form>
          </div>
        @endif
      @else
        <div class="semester-links">
          <a href="{{ route('fee-assignments.create', $student) }}?scope=single&increment={{ $cycle }}"><i class="fas fa-plus"></i> Assign Fee Plan</a>
        </div>
      @endif
    </div>
  @endfor
</div>

@if($standalone->isNotEmpty())
<div class="card" style="margin-top:24px">
  <div class="card-header"><div class="card-title"><i class="fas fa-file-invoice" style="color:var(--primary);margin-right:6px"></i>Other / One-off Vouchers</div></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Voucher No</th><th>Title</th><th>Amount</th><th>Due Date</th><th>Status</th><th>Actions</th></tr></thead>
      <tbody>
        @foreach($standalone as $v)
        <tr>
          <td>{{ $v->voucher_no }}</td>
          <td>{{ $v->title }}</td>
          <td>Rs. {{ number_format($v->total(), 0) }}</td>
          <td>{{ optional($v->due_date)->format('M d, Y') }}</td>
          <td><span class="badge {{ $v->status === 'paid' ? 'success' : ($v->status === 'overdue' ? 'danger' : 'warning') }}">{{ $v->autoStatusLabel() }}</span></td>
          <td>
            <div style="display:flex;gap:6px">
              <a href="{{ route('vouchers.show', $v) }}" class="btn btn-outline btn-sm" title="View"><i class="fas fa-eye"></i></a>
              <a href="{{ route('vouchers.print', $v) }}" target="_blank" class="btn btn-outline btn-sm" title="Print"><i class="fas fa-print"></i></a>
            </div>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endif

@endif

{{-- ====================== TABLE MODE - grouped Program -> Student -> Semester ====================== --}}
@if($mode === 'table')

<div class="card filter-card">
  <div class="card-body" style="padding:16px 20px">
    <form method="GET" action="{{ route('vouchers.index') }}" class="filter-form" data-ajax-filter>
      <input type="hidden" name="all" value="1">
      @if(request('student_id'))<input type="hidden" name="student_id" value="{{ request('student_id') }}">@endif
      <div class="form-group" style="margin-bottom:0;flex:1;min-width:200px">
        <label class="form-label">Search</label>
        <input type="text" name="search" class="form-control" placeholder="Voucher no, student..." value="{{ request('search') }}">
      </div>
      <div class="form-group" style="margin-bottom:0;min-width:180px">
        <label class="form-label">Voucher Type</label>
        <select name="fee_state" class="form-control">
          <option value="">All Vouchers</option>
          <option value="pending" {{ request('fee_state') === 'pending' ? 'selected' : '' }}>Pending Vouchers</option>
          <option value="paid" {{ request('fee_state') === 'paid' ? 'selected' : '' }}>Paid Vouchers</option>
        </select>
      </div>
      <div class="form-group" style="margin-bottom:0;min-width:140px">
        <label class="form-label">Status</label>
        <select name="status" class="form-control">
          <option value="">All</option>
          @foreach(['unpaid','partial','paid','overdue','cancelled'] as $st)
          <option value="{{ $st }}" {{ request('status') == $st ? 'selected' : '' }}>{{ ucfirst($st) }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group" style="margin-bottom:0;min-width:170px">
        <label class="form-label">Fee Head</label>
        <select name="fee_head_id" class="form-control">
          <option value="">All</option>
          @foreach($feeHeads as $fh)
          <option value="{{ $fh->id }}" {{ request('fee_head_id') == $fh->id ? 'selected' : '' }}>{{ $fh->name }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group" style="margin-bottom:0;min-width:170px">
        <label class="form-label">Session</label>
        <select name="academic_year_id" id="voucher-table-session" class="form-control">
          <option value="">Select Session</option>
          @foreach($academicYears as $year)
          <option value="{{ $year->id }}" {{ request('academic_year_id') == $year->id ? 'selected' : '' }}>{{ $year->name }}{{ $year->is_default ? ' · Default' : '' }}</option>
          @endforeach
        </select>
      </div>
      <div class="form-group" style="margin-bottom:0;min-width:220px">
        <label class="form-label">Program</label>
        <select name="program_id" id="voucher-table-program" class="form-control" data-selected="{{ request('program_id') }}">
          <option value="">Select Session first</option>
        </select>
      </div>
      <div class="form-group" style="margin-bottom:0;min-width:175px">
        <label class="form-label">Semester / Part</label>
        <select name="school_class_id" id="voucher-table-class" class="form-control" data-selected="{{ request('school_class_id') }}">
          <option value="">Select Program first</option>
        </select>
      </div>
      <div class="form-group" style="margin-bottom:0;min-width:135px">
        <label class="form-label">Due From</label>
        <input type="date" name="date_from" class="form-control" value="{{ request('date_from') }}">
      </div>
      <div class="form-group" style="margin-bottom:0;min-width:135px">
        <label class="form-label">Due To</label>
        <input type="date" name="date_to" class="form-control" value="{{ request('date_to') }}">
      </div>
      <div class="filter-actions">
        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
        <a href="{{ route('vouchers.index', ['all' => 1]) }}" class="btn btn-outline" data-ajax-filter-link>Reset</a>
        @if(auth()->user()?->isAccountant())
        <a href="{{ route('vouchers.export', request()->query()) }}" class="btn btn-outline"><i class="fas fa-file-csv"></i> Export</a>
        @endif
      </div>
    </form>
  </div>
</div>

@php
  $grandTotal   = $items->sum(fn ($v) => $v->total());
  $grandPending = $items->whereIn('status', ['unpaid','partial','overdue'])->sum(fn ($v) => $v->balance());
@endphp

<div class="table-summary-bar">
  <div class="summary-stat">
    <div class="summary-stat-icon" style="background:rgba(59,130,246,.12);color:var(--primary)"><i class="fas fa-receipt"></i></div>
    <div>
      <div class="summary-stat-val">{{ number_format($matchingTotal) }}</div>
      <div class="summary-stat-lbl">voucher{{ $matchingTotal == 1 ? '' : 's' }} matched</div>
    </div>
  </div>
  <div class="summary-stat">
    <div class="summary-stat-icon" style="background:rgba(99,102,241,.12);color:#6366f1"><i class="fas fa-sack-dollar"></i></div>
    <div>
      <div class="summary-stat-val">Rs. {{ number_format($grandTotal, 0) }}</div>
      <div class="summary-stat-lbl">total{{ $matchingTotal > $items->count() ? ' (loaded)' : '' }}</div>
    </div>
  </div>
  <div class="summary-stat">
    <div class="summary-stat-icon" style="background:{{ $grandPending > 0 ? 'rgba(220,38,38,.12)' : 'rgba(22,163,74,.12)' }};color:{{ $grandPending > 0 ? 'var(--danger)' : 'var(--success)' }}"><i class="fas {{ $grandPending > 0 ? 'fa-triangle-exclamation' : 'fa-circle-check' }}"></i></div>
    <div>
      <div class="summary-stat-val" style="color:{{ $grandPending > 0 ? 'var(--danger)' : 'var(--success)' }}">Rs. {{ number_format($grandPending, 0) }}</div>
      <div class="summary-stat-lbl">pending</div>
    </div>
  </div>
</div>

@if($matchingTotal > $cap)
<div class="alert" style="background:rgba(245,158,11,0.1);border:1px solid var(--warning);color:var(--text-muted);margin-bottom:16px;font-size:12.5px">
  <i class="fas fa-triangle-exclamation"></i> {{ number_format($matchingTotal) }} vouchers match these filters — showing the most recent {{ number_format($cap) }}. Narrow the filters above (date range, program, or status) for a complete list.
</div>
@endif

@php
  $byProgram = $items->groupBy(fn ($v) => $v->student->program->id ?? 0);
@endphp

<div class="voucher-groups">
  @forelse($byProgram as $programId => $programVouchers)
    @php
      $programObj    = $programVouchers->first()->student->program ?? null;
      $programName   = $programObj->name ?? 'No Program Assigned';
      $programPending = $programVouchers->whereIn('status', ['unpaid','partial','overdue'])->sum(fn ($v) => $v->balance());
      $byStudent = $programVouchers->groupBy(fn ($v) => $v->student_id);
    @endphp
    <details class="group-program" open>
      <summary>
        <i class="fas fa-chevron-right group-caret"></i>
        <i class="fas fa-graduation-cap group-icon"></i>
        <span class="group-title">{{ $programName }}</span>
        <span class="group-meta">{{ $byStudent->count() }} student{{ $byStudent->count() == 1 ? '' : 's' }} &middot; {{ $programVouchers->count() }} voucher{{ $programVouchers->count() == 1 ? '' : 's' }}</span>
        @if($programPending > 0)
          <span class="badge danger sm">Rs. {{ number_format($programPending, 0) }} pending</span>
        @else
          <span class="badge success sm">All clear</span>
        @endif
      </summary>

      <div class="group-body">
        @foreach($byStudent as $studentId => $studentVouchers)
          @php
            $studentObj = $studentVouchers->first()->student;
            $studentPending = $studentVouchers->reject(fn ($v) => $v->status === 'cancelled')->sum(fn ($v) => $v->balance());
            $bySemester = $studentVouchers->groupBy(fn ($v) => $v->cycle_no ?? 'other');
          @endphp
          <details class="group-student">
            <summary>
              <i class="fas fa-chevron-right group-caret"></i>
              <div class="browse-card-avatar" style="width:28px;height:28px;font-size:11px">{{ strtoupper(substr($studentObj->name ?? '?', 0, 2)) }}</div>
              <span class="group-title">{{ $studentObj->name ?? 'Unknown Student' }}</span>
              <span class="group-meta">{{ $studentObj->student_id ?? '' }} &middot; {{ $studentObj->schoolClass->name ?? '-' }} &middot; {{ $studentVouchers->count() }} voucher{{ $studentVouchers->count() == 1 ? '' : 's' }}</span>
              @if($studentPending > 0)
                <span class="badge danger sm">Rs. {{ number_format($studentPending, 0) }} due</span>
              @else
                <span class="badge success sm">Paid up</span>
              @endif
              @if($studentObj)
              <a href="{{ route('vouchers.index', ['program_id' => $programId, 'student_id' => $studentObj->id]) }}" class="group-link" onclick="event.stopPropagation()"><i class="fas fa-layer-group"></i> Full Semester View</a>
              @endif
            </summary>

            <div class="group-body">
              @foreach($bySemester->sortKeys() as $cycleNo => $cycleVouchers)
                @php
                  $sampleV = $cycleVouchers->first();
                  $semLabel = is_numeric($cycleNo)
                      ? ($sampleV->feeAssignment?->termLabelFor((int) $cycleNo) ?? 'Semester ' . $cycleNo)
                      : 'Other / One-off Vouchers';
                @endphp
                <div class="semester-block">
                  <div class="semester-block-title"><i class="fas fa-calendar-days"></i> {{ $semLabel }}</div>
                  <div class="table-wrap">
                    <table>
                      <thead><tr><th>Voucher No</th><th>Title</th><th>Amount</th><th>Due Date</th><th>Status</th><th>Actions</th></tr></thead>
                      <tbody>
                        @foreach($cycleVouchers->sortByDesc('due_date') as $v)
                        <tr>
                          <td>{{ $v->voucher_no }}</td>
                          <td>
                            {{ $v->title }}
                            @if($v->installment_no)
                              <div style="font-size:10.5px;color:var(--text-muted);margin-top:2px">
                                {{ $v->feeAssignment ? $v->feeAssignment->installmentLabelFor($v->cycle_no ?? $v->feeAssignment->cycle_start, $v->installment_no) : ordinal($v->installment_no) . ' Installment' }}
                              </div>
                            @endif
                          </td>
                          <td>Rs. {{ number_format($v->total(), 0) }}</td>
                          <td>{{ $v->due_date->format('M d, Y') }}</td>
                          <td>
                            @php $s = ['paid'=>'success','partial'=>'warning','overdue'=>'danger','unpaid'=>'gray','cancelled'=>'gray'][$v->status] ?? 'gray'; @endphp
                            <span class="badge {{ $s }}">{{ ucfirst($v->status) }}</span>
                            @if(!in_array($v->status, ['paid','cancelled']))
                              <div style="font-size:10px;color:var(--text-muted);margin-top:2px">{{ $v->autoStatusLabel() }}</div>
                            @endif
                          </td>
                          <td>
                            <div style="display:flex;gap:6px">
                              <a href="{{ route('vouchers.show', $v) }}" class="btn btn-outline btn-sm" title="View"><i class="fas fa-eye"></i></a>
                              <a href="{{ route('vouchers.print', $v) }}" target="_blank" class="btn btn-outline btn-sm" title="Print"><i class="fas fa-print"></i></a>
                            </div>
                          </td>
                        </tr>
                        @endforeach
                      </tbody>
                    </table>
                  </div>
                </div>
              @endforeach
            </div>
          </details>
        @endforeach
      </div>
    </details>
  @empty
    <div class="card"><div class="card-body" style="text-align:center;color:var(--text-muted);padding:40px">No vouchers found. <a href="{{ route('vouchers.create') }}">Generate one</a></div></div>
  @endforelse
</div>

@endif


{{-- Installment plan --}}
<div id="installmentModal" style="display:none;position:fixed;inset:0;background:rgba(0,0,0,.5);align-items:center;justify-content:center;z-index:500">
  <div class="card" style="max-width:460px;width:92%">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
      <div class="card-title">Generate Installment Plan</div>
      <button onclick="document.getElementById('installmentModal').style.display='none'" class="btn btn-outline btn-sm"><i class="fas fa-times"></i></button>
    </div>
    <div class="card-body">
      <form action="{{ route('vouchers.generate-installments') }}" method="POST">
        @csrf
        <div class="form-group"><label class="form-label">Student *</label>
          <select name="student_id" class="form-control" required>
            @foreach(\App\Models\Student::where('status','active')->orderBy('name')->get() as $s)
            <option value="{{ $s->id }}">{{ $s->name }} ({{ $s->student_id }})</option>
            @endforeach
          </select></div>
        <div class="form-group"><label class="form-label">Fee Head *</label>
          <select name="fee_head_id" class="form-control" required>
            @foreach(\App\Models\FeeHead::where('is_active', true)->orderBy('name')->get() as $fh)
            <option value="{{ $fh->id }}">{{ $fh->name }}</option>
            @endforeach
          </select></div>
        <div class="grid grid-2" style="gap:12px">
          <div class="form-group"><label class="form-label">Total Amount *</label>
            <input type="number" step="1" min="1" name="total_amount" class="form-control" required></div>
          <div class="form-group"><label class="form-label">No. of Installments *</label>
            <input type="number" min="2" max="{{ \App\Models\Setting::get('installment_max', 5) }}" name="installments" class="form-control" required value="2"></div>
        </div>
        <div class="form-group"><label class="form-label">First Due Date / Fallback *</label>
          <input type="date" name="first_due_date" class="form-control" required value="{{ now()->addDays(7)->format('Y-m-d') }}"></div>
        <input type="hidden" name="frequency_days" value="30">
        <input type="hidden" name="use_default_installment_dates" value="0">
        <label style="display:flex;gap:8px;align-items:flex-start;font-size:12px;margin:0 0 12px;cursor:pointer">
          <input type="checkbox" name="use_default_installment_dates" value="1" checked style="margin-top:3px">
          <span><strong>Use session default installment dates when configured</strong><br><span style="color:var(--text-muted)">The selected student's current semester/part defaults take priority; First Due Date is used only as fallback.</span></span>
        </label>
        <p style="font-size:12px;color:var(--text-muted);margin-bottom:16px">Max {{ \App\Models\Setting::get('installment_max', 5) }} installments (configurable in Settings).</p>
        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center"><i class="fas fa-layer-group"></i> Generate Installments</button>
      </form>
    </div>
  </div>
</div>

<style>
  .voucher-tabs { display:flex; gap:4px; border-bottom:1px solid var(--border); margin-bottom:20px; }
  .voucher-tab { padding:10px 18px; font-size:13px; font-weight:700; color:var(--text-muted); border-bottom:2px solid transparent; margin-bottom:-1px; display:flex; align-items:center; gap:7px; border-radius:8px 8px 0 0; transition:background .12s ease, color .12s ease; }
  .voucher-tab.active { color:var(--primary); border-bottom-color:var(--primary); background:rgba(59,130,246,0.06); }
  .voucher-tab:hover:not(.active) { color:var(--text); background:var(--bg); }

  /* Session tabs — Session -> Program -> Student browse (AJAX) */
  .session-tabs { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:16px; }
  .session-tab { display:flex; align-items:center; gap:7px; padding:7px 14px; font-size:12.5px; font-weight:700; color:var(--text-muted); background:var(--card); border:1px solid var(--border); border-radius:999px; transition:background .12s ease, color .12s ease, border-color .12s ease; }
  .session-tab:hover:not(.active) { color:var(--text); border-color:var(--primary); }
  .session-tab.active { color:#fff; background:var(--primary); border-color:var(--primary); }
  .session-tab-count { font-size:10.5px; font-weight:800; background:rgba(0,0,0,0.12); border-radius:999px; padding:1px 7px; }
  .session-tab.active .session-tab-count { background:rgba(255,255,255,0.25); }
  #browse-panel.is-loading { opacity:0.5; pointer-events:none; transition:opacity .1s ease; }

  .breadcrumb-row { display:flex; align-items:center; gap:8px; font-size:12.5px; color:var(--text-muted); margin-bottom:16px; flex-wrap:wrap; }
  .breadcrumb-row a { color:var(--primary); font-weight:600; }
  .breadcrumb-row i { font-size:9px; }

  /* Program browse — list/table view */
  .program-list-card { margin-bottom:8px; overflow:hidden; }
  .program-list-table { width:100%; border-collapse:collapse; }
  .program-list-table th { padding:12px 16px; font-size:11.5px; font-weight:800; color:var(--text-muted); text-transform:uppercase; letter-spacing:.025em; white-space:nowrap; background:var(--bg); border-bottom:1px solid var(--border); }
  .program-list-table td { padding:13px 16px; font-size:12.5px; vertical-align:middle; border-bottom:1px solid var(--border); }
  .program-list-table tbody tr:last-child td { border-bottom:0; }
  .program-list-table tbody tr { transition:background .12s ease; }
  .program-list-table tbody tr:hover { background:rgba(137,12,37,.025); }
  .program-list-name { display:flex; align-items:center; gap:11px; min-width:260px; }
  .program-list-icon { width:36px; height:36px; border-radius:9px; flex:0 0 36px; display:flex; align-items:center; justify-content:center; color:var(--primary); background:rgba(137,12,37,.07); font-size:14px; }
  .program-list-title { font-size:13px; font-weight:800; color:var(--text); line-height:1.35; }
  .program-list-session { margin-top:2px; font-size:10.5px; color:var(--text-muted); }
  .program-list-number { font-weight:800; color:var(--text); }
  .program-list-balance { font-weight:800; white-space:nowrap; }
  .program-list-balance.has-due { color:var(--danger); }
  .program-list-balance.is-clear { color:var(--success); }
  .program-view-btn { min-width:78px; justify-content:center; }

  /* ---- Student list (Step 2, "Browse by Program") — flat table, same
     compact type as the admin/accounts Students list ---- */
  .students-table th { font-size: 11.5px; padding: 10px 12px; white-space: nowrap; }
  .students-table td { font-size: 12.5px; padding: 10px 12px; vertical-align: middle; }
  .students-table td, .students-table td div { line-height: 1.35; }

  .browse-card-avatar { width:40px; height:40px; border-radius:10px; background:var(--primary); color:#fff; display:flex; align-items:center; justify-content:center; font-weight:800; font-size:14px; }
  .browse-card-title { font-weight:800; font-size:15px; margin-top:4px; }
  .browse-card-sub { font-size:12px; color:var(--text-muted); }
  .browse-card-footer { display:flex; align-items:center; gap:8px; margin-top:8px; }
  .badge.sm { font-size:10px; padding:2px 7px; }

  /* ---- Student profile banner ---- */
  .student-banner { background:linear-gradient(120deg, var(--primary), #6d28d9); border-radius:var(--radius); box-shadow:var(--shadow-lg, 0 10px 30px rgba(0,0,0,.12)); padding:2px; margin-bottom:22px; }
  .student-banner-body { display:flex; gap:18px; align-items:center; flex-wrap:wrap; padding:20px 24px; color:#fff; }
  .student-banner-avatar { width:64px; height:64px; border-radius:50%; object-fit:cover; flex-shrink:0; border:2.5px solid rgba(255,255,255,.65); box-shadow:0 4px 14px rgba(0,0,0,.15); }
  .student-banner-avatar-fallback { display:flex; align-items:center; justify-content:center; background:rgba(255,255,255,.18); font-weight:800; font-size:21px; }
  .student-banner-name { font-weight:800; font-size:19px; }
  .student-banner-id { font-weight:500; font-size:13px; opacity:.85; }
  .student-banner-meta { font-size:13px; opacity:.9; margin-top:4px; }
  .student-banner-meta i { margin-right:5px; opacity:.85; }
  .student-banner-chip { display:inline-flex; align-items:center; gap:5px; font-size:11.5px; font-weight:600; margin-top:8px; background:rgba(255,255,255,.16); padding:4px 10px; border-radius:20px; }
  .student-banner-balance { margin-left:auto; text-align:right; }
  .student-banner-balance-lbl { font-size:11px; text-transform:uppercase; letter-spacing:.06em; opacity:.85; font-weight:700; }
  .student-banner-balance-val { font-size:25px; font-weight:800; margin-top:2px; }
  .student-banner-badge { display:inline-flex; align-items:center; gap:5px; font-size:10.5px; font-weight:700; padding:3px 10px; border-radius:20px; margin-top:6px; }
  .student-banner-badge.danger { background:rgba(220,38,38,.25); color:#fff; }
  .student-banner-badge.success { background:rgba(22,163,74,.28); color:#fff; }

  .semester-grid { display:grid; grid-template-columns:repeat(auto-fill, minmax(340px, 1fr)); gap:16px; }
  .semester-card { background:var(--card); border:1px solid var(--border); border-left:4px solid var(--border); border-radius:var(--radius); box-shadow:var(--shadow); padding:16px; display:flex; flex-direction:column; gap:10px; transition:box-shadow .15s ease, transform .15s ease; }
  .semester-card:hover { box-shadow:0 10px 24px rgba(0,0,0,.08); transform:translateY(-2px); }
  .semester-card.is-empty { opacity:.65; }
  .semester-card.status-paid { border-left-color:var(--success); }
  .semester-card.status-due { border-left-color:var(--danger); }
  .semester-card.status-pending { border-left-color:var(--warning); }
  .semester-head { display:flex; justify-content:space-between; align-items:flex-start; gap:8px; }
  .semester-title { font-weight:800; font-size:14px; color:var(--primary); }
  .semester-sub { font-size:11px; color:var(--text-muted); margin-top:2px; }
  .semester-installments { display:flex; flex-direction:column; gap:6px; border-top:1px dashed var(--border); padding-top:8px; }
  .installment-row { display:flex; align-items:center; gap:8px; font-size:12px; padding:6px 8px; flex-wrap:wrap; border-radius:8px; transition:background .12s ease; }
  .installment-row:hover { background:var(--bg); }
  .installment-row-main { flex:1; min-width:120px; }
  .installment-row-title { font-weight:700; }
  .installment-row-sub { font-size:10.5px; color:var(--text-muted); }
  .installment-row-amount { font-weight:700; }
  .installment-row-actions { display:flex; gap:4px; }
  .installment-row.is-split-challan { border:1px dashed rgba(137,12,37,.22); background:rgba(137,12,37,.025); }
  .split-challan-chip { display:inline-flex;align-items:center;gap:4px;margin-left:5px;padding:2px 6px;border-radius:999px;background:rgba(137,12,37,.1);color:var(--primary);font-size:9px;font-weight:800;vertical-align:1px; }
  .split-challan-trigger { color:var(--primary); }
  .semester-links { display:flex; gap:14px; font-size:11.5px; border-top:1px dashed var(--border); padding-top:8px; flex-wrap:wrap; }
  .semester-links a { color:var(--primary); font-weight:600; }
  .semester-manage-form { border-top:1px dashed var(--border); padding-top:10px; }
  .semester-manage-title { font-size:11.5px; font-weight:800; color:var(--text-muted); text-transform:uppercase; letter-spacing:.03em; margin-bottom:8px; }
  .semester-manage-grid { display:grid; grid-template-columns:repeat(2, 1fr); gap:8px; }
  .semester-manage-grid label { font-size:10px; color:var(--text-muted); display:flex; flex-direction:column; gap:3px; }
  .semester-manage-months { display:block; font-size:10px; color:var(--text-muted); margin-top:8px; }
  .semester-manage-months span { font-weight:400; }
  .semester-manage-locked { font-size:11px; color:var(--warning); border-top:1px dashed var(--border); padding-top:8px; display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; }
  .semester-manage-manual-unlocked { font-size:11px; color:var(--primary); border-top:1px dashed var(--border); padding-top:8px; margin-top:8px; display:flex; align-items:center; justify-content:space-between; gap:10px; flex-wrap:wrap; }
  .semester-manage-unlock-form { margin:0; }
  .semester-fee-head-list { display:flex; flex-direction:column; gap:6px; }
  .fee-head-check { display:flex; align-items:center; gap:6px; font-size:12px; }
  .fee-head-check input { width:auto; }
  .fee-head-check span { color:var(--text-muted); font-size:10.5px; }
  .semester-manage-add-heads .semester-manage-grid { grid-template-columns:1fr 1fr 1fr; }
  @media (max-width: 640px) { .semester-manage-add-heads .semester-manage-grid { grid-template-columns:1fr; } }
  .stepper { display:flex; align-items:stretch; gap:0; }
  .stepper input { text-align:center; border-radius:0; }
  .stepper-btn { width:28px; border:1px solid var(--border); background:var(--bg); color:var(--text); font-weight:800; cursor:pointer; }
  .stepper-btn:first-child { border-radius:8px 0 0 8px; }
  .stepper-btn:last-child { border-radius:0 8px 8px 0; }

  /* ---- Split challan dialog ---- */
  .split-challan-modal { position:fixed;inset:0;z-index:900;background:rgba(15,23,42,.58);align-items:center;justify-content:center;padding:18px; }
  .split-challan-dialog { width:min(520px, 100%);background:var(--card);border:1px solid var(--border);border-radius:16px;box-shadow:0 24px 70px rgba(0,0,0,.28);overflow:hidden; }
  .split-challan-head { display:flex;align-items:flex-start;justify-content:space-between;gap:14px;padding:18px 20px;border-bottom:1px solid var(--border);background:linear-gradient(180deg,rgba(137,12,37,.05),transparent); }
  .split-challan-title { font-size:16px;font-weight:800;color:var(--primary);display:flex;align-items:center;gap:8px; }
  .split-challan-sub { font-size:11.5px;color:var(--text-muted);margin-top:4px;line-height:1.45; }
  .split-challan-body { padding:20px; }
  .split-challan-current { display:grid;grid-template-columns:1fr auto;gap:3px 12px;padding:11px 13px;border-radius:10px;background:var(--bg);border:1px solid var(--border);margin-bottom:14px; }
  .split-challan-current > span:first-child { font-size:10px;text-transform:uppercase;letter-spacing:.04em;color:var(--text-muted);font-weight:700; }
  .split-challan-current > strong { font-size:12px;text-align:right; }
  .split-challan-current .split-challan-total { grid-column:1 / -1;font-size:19px;font-weight:800;color:var(--text);margin-top:4px; }
  .split-challan-label { display:flex;flex-direction:column;gap:6px;font-size:11px;font-weight:800;color:var(--text-muted);text-transform:uppercase;letter-spacing:.03em; }
  .split-challan-label input { font-size:18px;font-weight:800;color:var(--text); }
  .split-challan-preview { display:grid;grid-template-columns:1fr auto 1fr;gap:10px;align-items:center;margin-top:14px; }
  .split-challan-preview > div { border:1px solid var(--border);border-radius:10px;padding:10px 12px;display:flex;flex-direction:column;gap:3px; }
  .split-challan-preview > div span { font-size:9.5px;color:var(--text-muted);font-weight:700;text-transform:uppercase; }
  .split-challan-preview > div strong { font-size:15px; }
  .split-challan-preview > i { color:var(--text-muted);font-size:11px; }
  .split-challan-note { display:flex;gap:7px;align-items:flex-start;margin-top:14px;padding:9px 10px;border-radius:9px;background:rgba(59,130,246,.07);color:var(--text-muted);font-size:10.5px;line-height:1.45; }
  .split-challan-note i { color:#3b82f6;margin-top:2px; }
  .split-challan-actions { display:flex;justify-content:flex-end;gap:8px;margin-top:18px; }
  @media (max-width:520px) { .split-challan-preview { grid-template-columns:1fr; } .split-challan-preview > i { transform:rotate(90deg);justify-self:center; } }

  /* ---- Table mode: Program -> Student -> Semester accordion ---- */
  .filter-card { margin-bottom:18px; }
  .filter-form { display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end; }
  .filter-actions { display:flex; gap:8px; margin-left:auto; align-self:flex-end; }

  .table-summary-bar { display:flex; gap:14px; flex-wrap:wrap; margin-bottom:18px; }
  .summary-stat { flex:1; min-width:190px; display:flex; align-items:center; gap:12px; background:var(--card); border:1px solid var(--border); border-radius:var(--radius); box-shadow:var(--shadow); padding:14px 18px; transition:transform .15s ease, box-shadow .15s ease; }
  .summary-stat:hover { transform:translateY(-2px); box-shadow:0 8px 20px rgba(0,0,0,0.07); }
  .summary-stat-icon { width:40px; height:40px; border-radius:11px; display:flex; align-items:center; justify-content:center; font-size:16px; flex-shrink:0; }
  .summary-stat-val { font-size:18px; font-weight:800; color:var(--text); line-height:1.2; }
  .summary-stat-lbl { font-size:11.5px; color:var(--text-muted); margin-top:2px; }
  .table-summary-num { font-size:15px; font-weight:800; color:var(--text); margin-right:4px; }

  .voucher-groups { display:flex; flex-direction:column; gap:12px; }

  details.group-program, details.group-student { background:var(--card); border:1px solid var(--border); border-radius:var(--radius); box-shadow:var(--shadow); overflow:hidden; }
  details.group-program { }
  details.group-program > summary { padding:16px 20px; background:linear-gradient(180deg, rgba(59,130,246,0.06), transparent); }
  details.group-student > summary { padding:12px 16px; background:var(--bg); }
  details > summary { list-style:none; cursor:pointer; display:flex; align-items:center; gap:10px; flex-wrap:wrap; font-size:13px; }
  details > summary::-webkit-details-marker { display:none; }
  .group-caret { font-size:10px; color:var(--text-muted); transition:transform .15s ease; flex-shrink:0; }
  details[open] > summary .group-caret { transform:rotate(90deg); }
  .group-icon { color:var(--primary); font-size:15px; }
  .group-title { font-weight:800; font-size:14px; }
  .group-meta { font-size:11.5px; color:var(--text-muted); }
  .group-link { margin-left:auto; font-size:11px; font-weight:700; color:var(--primary); white-space:nowrap; }
  .group-link i { margin-right:4px; }

  .group-body { padding:12px 16px 16px 16px; display:flex; flex-direction:column; gap:10px; border-top:1px dashed var(--border); }
  details.group-program > .group-body { padding-left:20px; padding-right:20px; }

  .semester-block { border:1px dashed var(--border); border-radius:10px; padding:10px 12px; }
  .semester-block-title { font-weight:800; font-size:12px; color:var(--primary); margin-bottom:8px; }
  .semester-block-title i { margin-right:6px; }

  @media (max-width: 640px) {
    .filter-actions { margin-left:0; width:100%; }
    .group-link { margin-left:0; width:100%; }
    .semester-manage-grid { grid-template-columns:1fr; }
  }
</style>

<script>
let splitChallanCurrentTotal = 0;
let splitChallanCarryForward = 0;

function formatSplitMoney(value) {
  const n = Number.isFinite(value) ? value : 0;
  return n.toLocaleString(undefined, { minimumFractionDigits: Number.isInteger(n) ? 0 : 2, maximumFractionDigits: 2 });
}

function refreshSplitChallanPreview() {
  const amountInput = document.getElementById('splitChallanAmount');
  const desired = Math.max(0, parseFloat(amountInput?.value || '0') || 0);
  const remaining = Math.max(0, splitChallanCurrentTotal - desired);
  document.getElementById('splitFirstAmount').textContent = formatSplitMoney(desired);
  document.getElementById('splitRemainingAmount').textContent = formatSplitMoney(remaining);
}

function openSplitChallanModal(button) {
  const modal = document.getElementById('splitChallanModal');
  const form = document.getElementById('splitChallanForm');
  const amountInput = document.getElementById('splitChallanAmount');
  if (!modal || !form || !amountInput) return;

  splitChallanCurrentTotal = parseFloat(button.dataset.total || '0') || 0;
  splitChallanCarryForward = parseFloat(button.dataset.carry || '0') || 0;
  form.action = button.dataset.action;
  document.getElementById('splitChallanNo').textContent = button.dataset.voucherNo || '—';
  document.getElementById('splitChallanTotal').textContent = formatSplitMoney(splitChallanCurrentTotal);
  amountInput.value = '';
  amountInput.min = '0.01';
  amountInput.max = Math.max(0.01, splitChallanCurrentTotal - 0.01).toFixed(2);
  amountInput.placeholder = 'e.g. 24000';
  refreshSplitChallanPreview();
  modal.style.display = 'flex';
  setTimeout(() => amountInput.focus(), 30);
}

function closeSplitChallanModal() {
  const modal = document.getElementById('splitChallanModal');
  if (modal) modal.style.display = 'none';
}

document.addEventListener('click', function (e) {
  const splitBtn = e.target.closest('.split-challan-trigger');
  if (splitBtn) {
    e.preventDefault();
    openSplitChallanModal(splitBtn);
    return;
  }

  const btn = e.target.closest('.stepper-btn');
  if (!btn) return;
  const input = btn.parentElement.querySelector('input[type=number]');
  const step = parseInt(btn.dataset.step, 10);
  const min = parseInt(input.min || '1', 10);
  const max = parseInt(input.max || '99', 10);
  let val = parseInt(input.value || '1', 10) + step;
  if (val < min) val = min;
  if (val > max) val = max;
  input.value = val;
});

document.getElementById('splitChallanAmount')?.addEventListener('input', refreshSplitChallanPreview);
document.getElementById('splitChallanModal')?.addEventListener('click', function (e) {
  if (e.target === this) closeSplitChallanModal();
});
document.addEventListener('keydown', function (e) {
  if (e.key === 'Escape') closeSplitChallanModal();
});

// ── Bulk student selection (Step 2, "Browse by Program") ──────────────
// The Browse panel is replaced via AJAX. Do NOT bind directly to elements
// that may not exist on the initial Programs screen, because those listeners
// disappear when #browse-panel.innerHTML is replaced. Use delegated handlers
// and look up the current controls each time instead.
function bulkStudentCheckboxes() {
  const panel = document.getElementById('browse-panel');
  return Array.from((panel || document).querySelectorAll('.student-select-box:not(:disabled)'));
}

function refreshBulkStudentSelection() {
  const selectAll = document.getElementById('selectAllStudents');
  const bar = document.getElementById('bulkActionsBar');
  const countEl = document.getElementById('bulkSelectedCount');
  if (!selectAll || !bar || !countEl) return;

  const boxes = bulkStudentCheckboxes();
  const checked = boxes.filter(cb => cb.checked);

  countEl.textContent = checked.length;
  bar.style.display = checked.length ? 'flex' : 'none';
  selectAll.checked = boxes.length > 0 && checked.length === boxes.length;
  selectAll.indeterminate = checked.length > 0 && checked.length < boxes.length;
}

document.addEventListener('change', function (e) {
  if (e.target && e.target.id === 'selectAllStudents') {
    bulkStudentCheckboxes().forEach(cb => { cb.checked = e.target.checked; });
    refreshBulkStudentSelection();
    return;
  }

  if (e.target && e.target.classList && e.target.classList.contains('student-select-box')) {
    refreshBulkStudentSelection();
  }
});

window.clearBulkSelection = function () {
  bulkStudentCheckboxes().forEach(cb => { cb.checked = false; });
  refreshBulkStudentSelection();
};

window.submitBulkExport = function () {
  const ids = bulkStudentCheckboxes().filter(cb => cb.checked).map(cb => cb.value);
  if (!ids.length) return;

  const holder = document.getElementById('bulkExportIds');
  const form = document.getElementById('bulkExportForm');
  if (!holder || !form) return;

  holder.innerHTML = ids.map(id => `<input type="hidden" name="student_ids[]" value="${id}">`).join('');
  form.submit();
};

refreshBulkStudentSelection();

// ── Exact Session -> Program -> Semester/Part cascading filters ───────
const voucherAcademicStructure = @json($filterStructure ?? []);

function initVoucherAcademicCascade(prefix) {
  const sessionSelect = document.getElementById(prefix + '-session');
  const programSelect = document.getElementById(prefix + '-program');
  const classSelect   = document.getElementById(prefix + '-class');
  if (!sessionSelect || !programSelect || !classSelect) return;

  const initialProgram = programSelect.dataset.selected || '';
  const initialClass   = classSelect.dataset.selected || '';

  function currentSession() {
    return voucherAcademicStructure[String(sessionSelect.value)] || null;
  }

  function currentProgram() {
    const session = currentSession();
    if (!session) return null;
    return (session.programs || []).find(p => String(p.id) === String(programSelect.value)) || null;
  }

  function populateClasses(selected = '') {
    const program = currentProgram();
    classSelect.innerHTML = '';

    if (!program) {
      classSelect.innerHTML = `<option value="">${sessionSelect.value ? 'Select Program first' : 'Select Session first'}</option>`;
      classSelect.disabled = true;
      return;
    }

    classSelect.disabled = false;
    classSelect.innerHTML = '<option value="">All linked Semester / Parts</option>';
    (program.terms || []).forEach(term => {
      const option = document.createElement('option');
      option.value = term.id;
      option.textContent = term.name;
      option.selected = String(term.id) === String(selected);
      classSelect.appendChild(option);
    });

    if (!(program.terms || []).length) {
      classSelect.innerHTML = '<option value="">No linked Semester / Part</option>';
      classSelect.disabled = true;
    }
  }

  function populatePrograms(selected = '', selectedClass = '') {
    const session = currentSession();
    programSelect.innerHTML = '';

    if (!session) {
      programSelect.innerHTML = '<option value="">Select Session first</option>';
      programSelect.disabled = true;
      populateClasses('');
      return;
    }

    programSelect.disabled = false;
    programSelect.innerHTML = '<option value="">All Programs in this Session</option>';
    (session.programs || []).forEach(program => {
      const option = document.createElement('option');
      option.value = program.id;
      option.textContent = program.name;
      option.selected = String(program.id) === String(selected);
      programSelect.appendChild(option);
    });

    if (!(session.programs || []).length) {
      programSelect.innerHTML = '<option value="">No Programs linked to this Session</option>';
      programSelect.disabled = true;
    }

    populateClasses(selectedClass);
  }

  sessionSelect.onchange = () => {
    programSelect.dataset.selected = '';
    classSelect.dataset.selected = '';
    populatePrograms('', '');
  };
  programSelect.onchange = () => {
    classSelect.dataset.selected = '';
    populateClasses('');
  };

  populatePrograms(initialProgram, initialClass);
}

function initVoucherAcademicCascades() {
  initVoucherAcademicCascade('voucher-browse');
  initVoucherAcademicCascade('voucher-table');
}

initVoucherAcademicCascades();

// ── Session -> Program -> Student browse panel, fully AJAX ────────────
// Session tabs, program cards, and the Step-2 breadcrumb/search all swap
// #browse-panel's contents via fetch() instead of reloading the page.
// Delegated on `document` (not #browse-panel itself) since the panel's
// own innerHTML gets replaced on every navigation.
(function () {
  const panel = document.getElementById('browse-panel');
  if (!panel) return; // not on the Browse by Program tab

  async function loadPanel(url, push = true) {
    panel.classList.add('is-loading');
    try {
      const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      if (!res.ok) throw new Error('Request failed');
      const html = await res.text();
      panel.innerHTML = html;
      if (push) history.pushState({ browsePanel: true }, '', url);
      initVoucherAcademicCascades();
      refreshBulkStudentSelection();
      panel.scrollIntoView({ block: 'nearest' });
    } catch (err) {
      window.location.href = url; // AJAX failed — fall back to a normal navigation
    } finally {
      panel.classList.remove('is-loading');
    }
  }

  document.addEventListener('click', function (e) {
    const link = e.target.closest('[data-ajax-nav]');
    if (!link || !panel.contains(link)) return;
    e.preventDefault();
    loadPanel(link.getAttribute('href'));
  });

  document.addEventListener('submit', function (e) {
    const form = e.target.closest('[data-ajax-form]');
    if (!form || !panel.contains(form)) return;
    e.preventDefault();
    const params = new URLSearchParams(new FormData(form));
    loadPanel(form.getAttribute('action') + '?' + params.toString());
  });

  window.addEventListener('popstate', function () {
    loadPanel(window.location.href, false);
  });
})();
</script>

@endsection