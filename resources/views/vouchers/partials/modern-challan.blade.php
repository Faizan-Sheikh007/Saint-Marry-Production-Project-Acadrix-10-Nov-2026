@php
  $institution = \App\Models\Setting::get('institution_name', 'Your Institution');
  $tagline     = \App\Models\Setting::get('institution_tagline', '');
  $logo        = \App\Models\Setting::get('institution_logo');
  $logoUrl     = $logo ? storage_asset($logo) : asset('logo.png');
  $currency    = \App\Models\Setting::get('currency', 'PKR');

  $student       = $voucher->student;
  $feeAssignment = $voucher->feeAssignment;
  // Prefer the current portal account over an outdated fee-assignment snapshot.
  $portalEmail = $student?->user?->email;
  $voucherLoginEmail = ($portalEmail && preg_match('/^stu-[0-9]{4}(?:-[a-z0-9-]+)?@[^@]+$/i', $portalEmail))
      ? $portalEmail
      : $feeAssignment?->lms_login_email;
  // A manually issued voucher may have a portal user but no fee assignment.
  $voucherLoginPassword = $feeAssignment?->lms_login_password_plain;
  $cycleNo       = (int) ($voucher->cycle_no ?? $feeAssignment?->cycle_start ?? 1);
  $partNo        = $voucher->part_no ?? $voucher->installment_no;
  $splitSequence = $voucher->split_sequence;
  $issued        = $voucher->issued_at;
  $due           = $voucher->due_date;
  $validityDate  = $due ? $due->copy()->addDays(15) : null;

  $currentBalance      = max(0, round((float) $lineItems->sum(fn ($item) => $item->total()) - (float) $lineItems->sum('paid_amount'), 2));
  $carryForwardBalance = $voucher->carryForwardBalance();
  $payableAmount       = max(0, round($currentBalance + $carryForwardBalance, 2));

  if ($feeAssignment) {
      $installmentLabel = $feeAssignment->installmentLabelFor($cycleNo, $partNo);
  } else {
      $ordinal = function ($n) {
          if (!$n) return 'Installment';
          if (in_array($n % 100, [11, 12, 13])) return $n . 'th Installment';
          return $n . match ($n % 10) { 1 => 'st Installment', 2 => 'nd Installment', 3 => 'rd Installment', default => 'th Installment' };
      };
      $installmentLabel = $ordinal((int) ($voucher->installment_no ?: 1));
  }

  $termOrMonth = trim((string) ($voucher->month ?: ($student->schoolClass->name ?? '')));
  $outerLabel  = trim($installmentLabel . ($termOrMonth ? ' · ' . $termOrMonth : ''));
  $semesterPart = $student->schoolClass->name ?? '—';
  if (!empty($student?->schoolClass?->section)) {
      $semesterPart .= ' (' . $student->schoolClass->section . ')';
  }

  $feeHeadLabel = $lineItems
      ->map(fn ($item) => $item->feeHead->name ?? $item->title)
      ->filter()
      ->unique()
      ->implode(', ');
  $copies = ['Student Copy', 'Bank Copy', 'Office Copy'];
  $qrKey = \Illuminate\Support\Str::slug($voucher->voucher_no . '-' . $voucher->id);
@endphp

<div class="installment-block">
  <div class="installment-label">{{ $outerLabel }}</div>

  <div class="receipts-row">
    @foreach($copies as $copyLabel)
      @php $copyKey = \Illuminate\Support\Str::slug($copyLabel); @endphp
      <div class="receipt">
        <div class="copy-tag">{{ $copyLabel }}</div>

        <div class="r-header">
          <div class="r-brand">
            <img src="{{ $logoUrl }}" alt="{{ $institution }} logo">
            <div>
              <div class="r-brand-name">{{ $institution }}</div>
              @if($tagline)<div class="r-brand-tagline">{{ $tagline }}</div>@endif
              <div class="r-brand-voucher-no">{{ $voucher->voucher_no }}</div>
            </div>
          </div>
          <div class="r-receipt-no">
            {{ $issued?->format('M Y') }}
            @if(!empty($qrUrl))
              <div id="qr-{{ $qrKey }}-{{ $copyKey }}" class="r-qr"></div>
            @endif
          </div>
        </div>

        <div class="r-dates">
          <div><span>Fee Due Date</span><strong>{{ $due?->format('d/m/Y') ?? '—' }}</strong></div>
          <div><span>Validity Date</span><strong>{{ $validityDate?->format('d/m/Y') ?? '—' }}</strong></div>
        </div>

        {{-- Informational notice only: does not calculate, store, or apply a fine. --}}
        <div class="r-late-fine-notice" role="note">
          <strong>Late Payment Notice</strong>
          <span>A late payment fine of PKR 2,000 per week will apply after the fee due date.</span>
        </div>

        @if($feeNote)
          <div class="r-fee-note">{{ $feeNote }}</div>
        @endif

        <div class="r-info">
          <div><span>Student Name</span><strong>{{ $student->name ?? '—' }}</strong></div>
          <div><span>Father Name</span><strong>{{ $student?->father_name ?: ($student?->guardian_name ?: '—') }}</strong></div>
          <div><span>G.R. No #</span><strong>{{ $student->student_id ?? '—' }}</strong></div>
          <div><span>Ledger No.</span><strong>{{ $student?->ledger_number ?: '—' }}</strong></div>
          <div><span>Academic Year</span><strong>{{ $student->academicYear->name ?? '—' }}</strong></div>
          <div><span>Program</span><strong>{{ $student->program->name ?? '—' }}</strong></div>
          <div><span>Semester/Part</span><strong>{{ $semesterPart }}</strong></div>
          <div><span>This Installment Is For</span><strong>{{ $installmentLabel }}{{ $splitSequence ? ' · Split Challan ' . $splitSequence : '' }}</strong></div>
        </div>

        <div class="r-fee-breakdown">
          <div class="r-fee-breakdown-title">Fee Details</div>
          <div class="r-fee-breakdown-head">
            <span>Fee Head</span>
            <span>Amount</span>
          </div>
          @foreach($lineItems as $item)
            <div class="r-fee-breakdown-row">
              <strong>{{ $item->feeHead->name ?? $item->title ?? '—' }}</strong>
              <strong>{{ $currency }} {{ number_format((float) $item->total(), 0) }}</strong>
            </div>
          @endforeach
        </div>

        <div class="r-total-box">
          <span>Payable Amount</span>
          <span>{{ $currency }} {{ number_format($payableAmount, 0) }}</span>
        </div>

        @if($bankAccounts->isNotEmpty())
          <div class="r-bank">
            @foreach($bankAccounts as $bank)
              <div>
                <span>{{ $bank->bank_name }}</span>
                <strong>{{ $bank->account_title }} — {{ $bank->account_number }}</strong>
              </div>
              @if($bank->branch_name)
                <div><span></span><span style="color:var(--text-muted,#64748b)">{{ $bank->branch_name }}</span></div>
              @endif
            @endforeach
          </div>
        @endif

        @if($voucherLoginEmail)
          <div class="r-bank r-dashed-primary">
            <div class="r-box-title">LMS Login (keep safe)</div>
            <div><span>Login ID</span><strong>{{ $voucherLoginEmail }}</strong></div>
            @if($voucherLoginPassword)
              <div><span>Password</span><strong>{{ $voucherLoginPassword }}</strong></div>
            @endif
          </div>
        @endif

        {{-- Use the declaration configured in Settings as the single source of
             truth; clearing that field removes this box from every copy. --}}
        @if(filled(trim((string) ($declaration ?? ''))))
          <div class="r-bank r-dashed-warning">
            <div class="r-box-title warning">Declaration</div>
            <div class="r-long-text">{{ $declaration }}</div>
          </div>
        @endif

        <div style="font-size:9px;text-align:center;border-top:1px solid #ccc;padding:6px 0">This is a computer-generated document and is not valid for legal proceedings.</div>

        <div class="r-stamps"><div>Bank Stamp</div></div>
        <div class="r-footer">
          @if(!empty($qrUrl))Scan the QR to verify this installment &middot; @endif
          Generated by {{ $institution }} &middot; Print Date: {{ now()->format('d M Y, h:i A') }}
        </div>
      </div>
    @endforeach
  </div>
</div>

@if(!empty($qrUrl))
<script>
  (function () {
    if (typeof QRCode === 'undefined') return;
    @foreach($copies as $copyLabel)
      @php $copyKey = \Illuminate\Support\Str::slug($copyLabel); @endphp
      var target{{ $loop->index }} = document.getElementById('qr-{{ $qrKey }}-{{ $copyKey }}');
      if (target{{ $loop->index }}) {
        new QRCode(target{{ $loop->index }}, {
          text: @json($qrUrl),
          width: 44,
          height: 44,
          colorDark: '#111111',
          colorLight: '#ffffff',
          correctLevel: QRCode.CorrectLevel.M
        });
      }
    @endforeach
  })();
</script>
@endif
