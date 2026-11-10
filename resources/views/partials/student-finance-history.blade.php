{{--
    Read-only fee ledger shared by Admin > Student Profile, Admin > Fee Summary,
    and Student Portal > My Fees. A voucher may contain multiple fee-head rows;
    the ledger and payment history group by voucher number (never allocation row).

    Required: $feeLedger, $paymentHistory.
    Optional: $financeShowTotals, $financeShowPlans, $financePlans,
              $financeVoucherRoute, $financeReceiptRoute.
    Controllers must scope all records to the authorized student.
--}}
@php
  $financeShowTotals = $financeShowTotals ?? true;
  $financeShowPlans = $financeShowPlans ?? false;
  $financePlans = $financePlans ?? collect();
  $financeVoucherRoute = $financeVoucherRoute ?? null;
  $financeReceiptRoute = $financeReceiptRoute ?? null;
  $financePlanRoute = $financePlanRoute ?? null;
  $feeTotals = $feeLedger['totals'];
  $feeCounts = $feeLedger['counts'];
  $historyTotals = [
    'voucher' => round((float) $paymentHistory->sum('voucher_amount'), 2),
    'verified' => round((float) $paymentHistory->sum('received_amount'), 2),
    'direct' => round((float) $paymentHistory->sum('direct_amount'), 2),
    'credit' => round((float) $paymentHistory->sum('credit_amount'), 2),
  ];
@endphp
<style>
 .sfl .sfl-metrics{display:grid;grid-template-columns:repeat(auto-fit,minmax(155px,1fr));gap:10px;margin:0 0 20px}
 .sfl .sfl-metric{border:1px solid var(--border);border-radius:12px;background:var(--card);padding:15px 16px;min-width:0}
 .sfl .sfl-metric-label{color:var(--text-muted);font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.025em;margin-bottom:7px}
 .sfl .sfl-metric-value{font-weight:800;font-size:19px;color:var(--text)}
 .sfl .sfl-metric.is-paid{background:rgba(16,185,129,.055)}
 .sfl .sfl-metric.is-paid .sfl-metric-value{color:#059669}
 .sfl .sfl-metric.is-overdue{background:rgba(220,38,38,.045)}
 .sfl .sfl-metric.is-overdue .sfl-metric-value{color:#dc2626}
 .sfl .sfl-metric.is-upcoming{background:rgba(245,158,11,.045)}
 .sfl .sfl-metric.is-upcoming .sfl-metric-value{color:#b7791f}
 .sfl .sfl-card{margin-top:20px}
 .sfl .sfl-subnote{padding:11px 18px;color:var(--text-muted);font-size:12px;line-height:1.65;border-top:1px solid var(--border)}
 .sfl .sfl-filter-wrap{display:flex;gap:8px;align-items:center;flex-wrap:wrap}
 .sfl .sfl-filter-wrap select{background:var(--card);color:var(--text);border:1px solid var(--border);border-radius:8px;font-size:12px;padding:7px 9px;max-width:210px}
 .sfl .sfl-strong{font-weight:700;white-space:nowrap}
 .sfl .sfl-sub{color:var(--text-muted);font-size:11px;margin-top:3px}
 .sfl .sfl-fee-detail summary{cursor:pointer;color:var(--primary);font-size:11px;font-weight:700;margin-top:5px}
 .sfl .sfl-fee-detail table{font-size:11px;margin:8px 0 0;min-width:520px}
 .sfl .sfl-fee-detail th,.sfl .sfl-fee-detail td{padding:7px 9px}
 .sfl .sfl-status-pair{display:flex;gap:4px;flex-direction:column;align-items:flex-start}
 .sfl .sfl-status-pair .badge{white-space:nowrap}
 .sfl table{width:100%;font-size:12px}
 .sfl td,.sfl th{vertical-align:top}
 .sfl tr[hidden]{display:none!important}
 .sfl .sfl-money{text-align:right;white-space:nowrap;font-variant-numeric:tabular-nums}
 .sfl .sfl-total-row{font-weight:800;background:var(--bg);border-top:2px solid var(--border)}
 @media(max-width:760px){.sfl .card-header{flex-wrap:wrap;gap:10px}.sfl .sfl-metrics{grid-template-columns:repeat(2,minmax(0,1fr))}}
</style>
<div class="sfl" id="student-fee-ledger">
  @if($financeShowTotals)
    <div class="sfl-metrics" aria-label="Student fee summary">
      @foreach([
        ['label'=>'Total Fee Payable','value'=>$feeTotals['billed'],'class'=>''],
        ['label'=>'Paid / Credited','value'=>$feeTotals['paid'],'class'=>'is-paid'],
        ['label'=>'Remaining Balance','value'=>$feeTotals['pending'],'class'=>''],
        ['label'=>'Overdue','value'=>$feeTotals['overdue'],'class'=>'is-overdue'],
        ['label'=>'Due Today / Soon','value'=>$feeTotals['due_soon'],'class'=>'is-upcoming'],
        ['label'=>'Future Installments','value'=>$feeTotals['upcoming'],'class'=>''],
      ] as $metric)
      <div class="sfl-metric {{ $metric['class'] }}">
        <div class="sfl-metric-label">{{ $metric['label'] }}</div>
        <div class="sfl-metric-value">Rs.{{ number_format($metric['value'], 2) }}</div>
      </div>
      @endforeach
    </div>
  @endif

  @if($financeShowPlans)
    <div class="card sfl-card">
      <div class="card-header"><div class="card-title"><i class="fas fa-graduation-cap"></i> Fee Plan Details &amp; Concessions</div></div>
      <div class="table-wrap"><table>
        <thead><tr><th>Fee Plan</th><th>Semester / Year</th><th>Installments</th><th class="sfl-money">Net Planned Fee</th><th class="sfl-money">Scholarship</th><th class="sfl-money">Financial Aid</th><th class="sfl-money">Other Discount</th>@if($financePlanRoute)<th>Plan PDF</th>@endif</tr></thead>
        <tbody>
          @forelse($financePlans as $plan)
          <tr>
            <td class="sfl-strong">{{ $plan->feeTemplate?->name ?? 'Fee Plan' }}</td>
            <td>{{ $plan->cycleRangeLabel() }}</td>
            <td>{{ $plan->installments ?? '—' }}</td>
            <td class="sfl-money">Rs.{{ number_format((float)$plan->total_amount, 2) }}</td>
            <td class="sfl-money">Rs.{{ number_format((float)$plan->scholarship_amount, 2) }}</td>
            <td class="sfl-money">Rs.{{ number_format((float)$plan->financial_aid_amount, 2) }}</td>
            <td class="sfl-money">Rs.{{ number_format((float)$plan->discount_amount, 2) }}</td>
            @if($financePlanRoute)
            <td><a href="{{ route($financePlanRoute, $plan) }}" class="btn btn-outline btn-sm" target="_blank" rel="noopener"><i class="fas fa-download"></i> Download</a></td>
            @endif
          </tr>
          @empty
          <tr><td colspan="{{ $financePlanRoute ? 8 : 7 }}" style="text-align:center;padding:24px;color:var(--text-muted)">No fee plan has been assigned.</td></tr>
          @endforelse
        </tbody>
      </table></div>
      <div class="sfl-subnote">Concessions are shown for reference. The planned and voucher payable totals already reflect the applicable discounts—do not subtract scholarship or aid a second time. Superseded plans may remain visible as historical records.</div>
    </div>
  @endif

  <div class="card sfl-card">
    <div class="card-header" style="display:flex;justify-content:space-between;align-items:center">
      <div class="card-title"><i class="fas fa-file-invoice-dollar"></i> Current Fee Details &amp; Voucher History</div>
      <div class="sfl-filter-wrap">
        <label for="student-fee-status-filter" style="font-size:12px;color:var(--text-muted);font-weight:600">Show</label>
        <select id="student-fee-status-filter" aria-label="Filter fee vouchers by status">
          <option value="all">All Vouchers ({{ $feeCounts['all'] }})</option>
          <option value="paid">Paid ({{ $feeCounts['paid'] }})</option>
          <option value="unpaid">Unpaid / Partially Paid ({{ $feeCounts['unpaid'] + $feeCounts['partial'] }})</option>
          <option value="unpaid_only">Unpaid Only ({{ $feeCounts['unpaid'] }})</option>
          <option value="partial">Partially Paid ({{ $feeCounts['partial'] }})</option>
          <option value="overdue">Overdue ({{ $feeCounts['overdue'] }})</option>
          <option value="due_soon">Due Today / Soon ({{ $feeCounts['due_soon'] }})</option>
          <option value="upcoming">Upcoming ({{ $feeCounts['upcoming'] }})</option>
          @if($feeCounts['cancelled'])<option value="cancelled">Cancelled ({{ $feeCounts['cancelled'] }})</option>@endif
        </select>
      </div>
    </div>
    <div class="table-wrap"><table>
      <thead><tr>
        <th>Voucher #</th><th>Semester / Fee Details</th>
        <th class="sfl-money">Voucher Amount</th>
        <th class="sfl-money">Paid / Credited</th>
        <th class="sfl-money">Balance</th>
        <th>Due Date</th><th>Status</th><th>Voucher</th>
      </tr></thead>
      <tbody id="student-fee-ledger-rows">
        @forelse($feeLedger['rows'] as $entry)
        <tr data-fee-filters="{{ $entry->filters }}">
          <td class="sfl-strong">{{ $entry->voucher_no }}</td>
          <td>
            <div class="sfl-strong" style="white-space:normal">{{ $entry->term }}</div>
            <div class="sfl-sub">{{ $entry->items_label }}</div>
            <details class="sfl-fee-detail"><summary>View fee-head breakdown</summary>
              <div class="table-wrap"><table>
                <thead><tr><th>Fee Head</th><th class="sfl-money">Gross</th><th class="sfl-money">Discount</th><th class="sfl-money">Payable</th><th class="sfl-money">Paid</th><th class="sfl-money">Balance</th></tr></thead>
                <tbody>
                  @foreach($entry->lines as $line)
                  <tr><td>{{ $line->name }}</td>
                    <td class="sfl-money">{{ number_format($line->gross, 2) }}</td>
                    <td class="sfl-money">{{ number_format($line->discount, 2) }}</td>
                    <td class="sfl-money">{{ number_format($line->amount, 2) }}</td>
                    <td class="sfl-money">{{ number_format($line->paid, 2) }}</td>
                    <td class="sfl-money">{{ number_format($line->balance, 2) }}</td>
                  </tr>
                  @endforeach
                </tbody>
              </table></div>
            </details>
          </td>
          <td class="sfl-money">Rs.{{ number_format($entry->amount, 2) }}</td>
          <td class="sfl-money" style="color:#059669;font-weight:700">Rs.{{ number_format($entry->paid, 2) }}</td>
          <td class="sfl-money" style="font-weight:700;color:{{ $entry->balance > 0 ? 'var(--text)' : '#059669' }}">Rs.{{ number_format($entry->balance, 2) }}</td>
          <td style="white-space:nowrap">{{ $entry->due_date?->format('d M Y') ?? '—' }}</td>
          <td><div class="sfl-status-pair">
            <span class="badge {{ $entry->payment_state === 'Paid' ? 'success' : ($entry->payment_state === 'Cancelled' ? 'gray' : ($entry->payment_state === 'Partial' ? 'warning' : 'gray')) }}">{{ $entry->payment_state }}</span>
            @if($entry->balance > 0 && $entry->payment_state !== 'Cancelled')
              <span class="badge {{ $entry->schedule === 'Overdue' ? 'danger' : ($entry->schedule === 'Upcoming' ? 'gray' : 'warning') }}">{{ $entry->schedule }}</span>
            @endif
          </div></td>
          <td>
            @if($financeVoucherRoute)
              <a href="{{ route($financeVoucherRoute, $entry->voucher) }}" target="_blank" rel="noopener" class="btn btn-outline btn-sm" title="View voucher"><i class="fas fa-eye"></i> View</a>
            @else — @endif
          </td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center;padding:25px;color:var(--text-muted)">No fee vouchers have been generated yet.</td></tr>
        @endforelse
        @if(count($feeLedger['rows']))
          <tr id="student-fee-filter-empty" hidden><td colspan="8" style="text-align:center;padding:25px;color:var(--text-muted)">No vouchers match the selected status.</td></tr>
        @endif
      </tbody>
      @if(count($feeLedger['rows']))
      <tfoot><tr class="sfl-total-row">
        <td colspan="2">Current Fee Totals ({{ $feeCounts['all'] - $feeCounts['cancelled'] }} vouchers)</td>
        <td class="sfl-money">Rs.{{ number_format($feeTotals['billed'], 2) }}</td>
        <td class="sfl-money">Rs.{{ number_format($feeTotals['paid'], 2) }}</td>
        <td class="sfl-money">Rs.{{ number_format($feeTotals['pending'], 2) }}</td>
        <td colspan="3"></td>
      </tr></tfoot>
      @endif
    </table></div>
    <div class="sfl-subnote">One row per voucher, even when it contains multiple fee heads or split installment vouchers. Payment state and due-date state are shown separately: a partially paid voucher can still be Upcoming or Overdue. Totals represent current non-cancelled charges; replaced/superseded plans are excluded. The footer remains the full total when filtering.</div>
  </div>

  {{-- Payment activity can exist on a voucher that is not settled. Keep it
       in this history with both the amount received and its unpaid balance. --}}
  @php
    $financePaidAndPartialRows = collect($feeLedger['rows'])
        ->filter(fn ($row) => $row->payment_state !== 'Cancelled' && $row->paid > 0.005)
        ->sortByDesc(fn ($row) => $row->due_date?->format('Y-m-d') ?? '')
        ->values();
  @endphp
  <div class="card sfl-card" id="student-paid-fee-history">
    <div class="card-header"><div class="card-title"><i class="fas fa-clock-rotate-left"></i> Fee History — Paid &amp; Partially Paid Installments</div></div>
    <div class="table-wrap"><table>
      <thead><tr>
        <th>Voucher #</th><th>Fee Details</th>
        <th class="sfl-money">Voucher Amount</th><th class="sfl-money">Amount Paid / Credited</th>
        <th class="sfl-money">Remaining</th><th>Due Date</th><th>Status</th><th>Voucher</th>
      </tr></thead>
      <tbody>
        @forelse($financePaidAndPartialRows as $entry)
        <tr>
          <td class="sfl-strong">{{ $entry->voucher_no }}</td>
          <td><div class="sfl-strong" style="white-space:normal">{{ $entry->term }}</div><div class="sfl-sub">{{ $entry->items_label }}</div></td>
          <td class="sfl-money">Rs.{{ number_format($entry->amount, 2) }}</td>
          <td class="sfl-money" style="color:#059669;font-weight:800">Rs.{{ number_format($entry->paid, 2) }}</td>
          <td class="sfl-money">Rs.{{ number_format($entry->balance, 2) }}</td>
          <td style="white-space:nowrap">{{ $entry->due_date?->format('d M Y') ?? '—' }}</td>
          <td>
            <span class="badge {{ $entry->payment_state === 'Paid' ? 'success' : 'warning' }}">{{ $entry->payment_state === 'Partial' ? 'Partially Paid' : $entry->payment_state }}</span>
            @if($entry->balance > 0)
              <span class="badge {{ $entry->schedule === 'Overdue' ? 'danger' : 'gray' }}">{{ $entry->schedule }}</span>
            @endif
          </td>
          <td>
            @if($financeVoucherRoute)
              <a href="{{ route($financeVoucherRoute, $entry->voucher) }}" target="_blank" rel="noopener" class="btn btn-outline btn-sm"><i class="fas fa-eye"></i> View</a>
            @else — @endif
          </td>
        </tr>
        @empty
        <tr><td colspan="8" style="text-align:center;padding:24px;color:var(--text-muted)">No paid or partially paid vouchers yet.</td></tr>
        @endforelse
      </tbody>
      @if($financePaidAndPartialRows->isNotEmpty())
      <tfoot><tr class="sfl-total-row">
        <td colspan="2">Total ({{ $financePaidAndPartialRows->count() }} vouchers with allocated payments)</td>
        <td class="sfl-money">Rs.{{ number_format($financePaidAndPartialRows->sum('amount'), 2) }}</td>
        <td class="sfl-money">Rs.{{ number_format($financePaidAndPartialRows->sum('paid'), 2) }}</td>
        <td class="sfl-money">Rs.{{ number_format($financePaidAndPartialRows->sum('balance'), 2) }}</td>
        <td colspan="3"></td>
      </tr></tfoot>
      @endif
    </table></div>
    <div class="sfl-subnote">Includes all current vouchers with an allocated payment, even when part of the fee is still unpaid. Amount Paid includes verified allocations and previously received credit applied to fees. Remaining is still due according to its listed date. Vouchers with no payments remain under Current Fee Details.</div>
  </div>

  <div class="card sfl-card" id="student-payment-history">
    <div class="card-header"><div class="card-title"><i class="fas fa-clock-rotate-left"></i> Complete Payment History — By Voucher</div></div>
    <div class="table-wrap"><table>
      <thead><tr>
        <th>Voucher #</th><th class="sfl-money">Voucher Amount</th>
        <th class="sfl-money">Paid / Credited</th>
        <th class="sfl-money">Direct Payments</th>
        <th class="sfl-money">Applied Credit</th>
        <th>Method</th><th>Status</th><th>Received By / Date</th><th>Receipt / Proof</th>
      </tr></thead>
      <tbody>
        @forelse($paymentHistory as $history)
        <tr>
          <td class="sfl-strong">{{ $history->voucher_no }}</td>
          <td class="sfl-money">Rs.{{ number_format($history->voucher_amount, 2) }}</td>
          <td class="sfl-money" style="color:#059669;font-weight:800">Rs.{{ number_format($history->received_amount, 2) }}</td>
          <td class="sfl-money">Rs.{{ number_format($history->direct_amount, 2) }}</td>
          <td class="sfl-money">Rs.{{ number_format($history->credit_amount, 2) }}</td>
          <td>{{ $history->method }}</td>
          <td><span class="badge {{ $history->status === 'Verified' ? 'success' : ($history->status === 'Rejected' ? 'danger' : 'warning') }}">{{ $history->status }}</span></td>
          <td>
            <div>{{ $history->received_by }}</div>
            <div class="sfl-sub">{{ $history->paid_at?->format('d M Y') ?? '—' }}</div>
          </td>
          <td>
            @if($financeReceiptRoute && $history->received_amount > 0 && $history->voucher)
              <a href="{{ route($financeReceiptRoute, $history->voucher) }}" class="btn btn-outline btn-sm" target="_blank" rel="noopener" style="margin:0 5px 5px 0">Receipt</a>
            @endif
            @foreach($history->proof_paths as $proofPath)
              <a href="{{ asset('storage/'.$proofPath) }}" target="_blank" rel="noopener" title="View submitted proof" style="display:inline-block;margin:0 4px 5px 0"><i class="fas fa-file-arrow-down"></i> Proof</a>
            @endforeach
            @if($history->received_amount <= 0 && $history->proof_paths->isEmpty()) — @endif
          </td>
        </tr>
        @empty
        <tr><td colspan="9" style="text-align:center;padding:25px;color:var(--text-muted)">No payment submissions have been recorded yet.</td></tr>
        @endforelse
      </tbody>
      @if($paymentHistory->isNotEmpty())
      <tfoot><tr class="sfl-total-row">
        <td>Total ({{ $paymentHistory->count() }} voucher{{ $paymentHistory->count() === 1 ? '' : 's' }} with payment activity)</td>
        <td class="sfl-money">Rs.{{ number_format($historyTotals['voucher'], 2) }}</td>
        <td class="sfl-money">Rs.{{ number_format($historyTotals['verified'], 2) }}</td>
        <td class="sfl-money">Rs.{{ number_format($historyTotals['direct'], 2) }}</td>
        <td class="sfl-money">Rs.{{ number_format($historyTotals['credit'], 2) }}</td>
        <td colspan="4"></td>
      </tr></tfoot>
      @endif
    </table></div>
    <div class="sfl-subnote"><strong>Paid / Credited</strong> totals verified allocations for each voucher. <strong>Direct Payments</strong> excludes existing balance credit; <strong>Applied Credit</strong> is money received earlier and used against fees (not another cash receipt). Unverified/rejected payment proofs never increase paid totals. The current fee plan totals <strong>Rs.{{ number_format($feeTotals['billed'], 2) }}</strong> across all active vouchers, including those not yet paid. The payment-history voucher amount covers <strong>only vouchers with activity</strong> (and can include older superseded vouchers); do not mistake it for the full fee plan or new cash collected.</div>
  </div>

  @if(isset($financeCreditTransactions) && $financeCreditTransactions->isNotEmpty())
    <div class="card sfl-card" id="student-credit-history">
      <div class="card-header"><div class="card-title"><i class="fas fa-wallet"></i> Extra Payments &amp; Credit Movements</div></div>
      <div class="table-wrap"><table>
        <thead><tr><th>Date</th><th>Movement</th><th class="sfl-money">Credit Change</th><th class="sfl-money">Credit After</th><th>Payment Method / Reference</th><th>Notes</th></tr></thead>
        <tbody>
          @foreach($financeCreditTransactions as $movement)
            <tr>
              <td style="white-space:nowrap">{{ $movement->created_at?->format('d M Y H:i') ?? '—' }}</td>
              <td>{{ ucwords(str_replace('_', ' ', $movement->type)) }}</td>
              <td class="sfl-money">Rs.{{ number_format((float)$movement->amount, 2) }}</td>
              <td class="sfl-money">Rs.{{ number_format((float)$movement->balance_after, 2) }}</td>
              <td>{{ ucwords(str_replace('_', ' ', $movement->method ?? '')) }} {{ $movement->reference_no }}</td>
              <td>{{ $movement->notes ?: '—' }}</td>
            </tr>
          @endforeach
        </tbody>
      </table></div>
      <div class="sfl-subnote">Latest {{ $financeCreditTransactions->count() }} credit movement(s). An extra-payment receipt records money received; applying that credit to a voucher is not an additional collection. Current available credit: <strong>Rs.{{ number_format((float) ($financeAvailableCredit ?? 0), 2) }}</strong>.</div>
    </div>
  @endif
</div>
<script>
(function(){
  var filter = document.getElementById('student-fee-status-filter');
  var body = document.getElementById('student-fee-ledger-rows');
  if (!filter || !body) return;
  var rows = Array.from(body.querySelectorAll('tr[data-fee-filters]'));
  var empty = document.getElementById('student-fee-filter-empty');
  filter.addEventListener('change', function(){
    var shown = 0;
    rows.forEach(function(row){
      var tags = (row.getAttribute('data-fee-filters') || '').split(' ');
      var visible = filter.value === 'all' || tags.indexOf(filter.value) >= 0;
      row.hidden = !visible;
      if (visible) shown++;
    });
    if (empty) empty.hidden = shown !== 0;
  });
})();
</script>
