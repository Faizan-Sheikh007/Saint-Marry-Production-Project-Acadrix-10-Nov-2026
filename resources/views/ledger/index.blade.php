@extends('layouts.app')
@section('title', 'Ledger')
@section('page_title', 'Ledger & Accounting')
@section('content')
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px">
  <div style="font-size:22px;font-weight:800">Student Ledgers</div>
  <a href="{{ route('ledger.general') }}" class="btn btn-outline"><i class="fas fa-chart-line"></i> General Ledger</a>
</div>

@if($overdueCount > 0 || $dueSoonCount > 0)
<div class="grid" style="grid-template-columns:repeat(2,1fr);gap:16px;margin-bottom:20px">
  <a href="{{ route('ledger.general') }}" style="text-decoration:none">
    <div class="card" style="border-left:4px solid var(--danger)">
      <div class="card-body" style="display:flex;align-items:center;justify-content:space-between">
        <div>
          <div style="font-size:20px;font-weight:800;color:var(--danger)">{{ $overdueCount }}</div>
          <div style="font-size:12px;color:var(--text-muted)">Overdue vouchers institution-wide</div>
        </div>
        <i class="fas fa-triangle-exclamation" style="color:var(--danger);font-size:22px"></i>
      </div>
    </div>
  </a>
  <a href="{{ route('ledger.general') }}" style="text-decoration:none">
    <div class="card" style="border-left:4px solid var(--warning)">
      <div class="card-body" style="display:flex;align-items:center;justify-content:space-between">
        <div>
          <div style="font-size:20px;font-weight:800;color:var(--warning)">{{ $dueSoonCount }}</div>
          <div style="font-size:12px;color:var(--text-muted)">Due within the next 7 days</div>
        </div>
        <i class="fas fa-clock" style="color:var(--warning);font-size:22px"></i>
      </div>
    </div>
  </a>
</div>
@endif

<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="padding:14px 20px">
    <form method="GET" style="display:flex;gap:12px" data-ajax-filter>
      <input type="text" name="search" class="form-control" placeholder="Search student..." value="{{ request('search') }}">
      <select name="status" class="form-control"><option value="">All statuses</option>@foreach(['active','inactive','suspended','graduated','dropped','left','transferred','terminated'] as $status)<option value="{{ $status }}" @selected(request('status')===$status)>{{ ucfirst($status) }}</option>@endforeach</select>
      <select name="program_id" class="form-control"><option value="">All programs</option>@foreach(\App\Models\Program::orderBy('name')->get() as $program)<option value="{{ $program->id }}" @selected(request('program_id')==$program->id)>{{ $program->name }}</option>@endforeach</select>
      <button type="submit" class="btn btn-primary"><i class="fas fa-search"></i></button>
    </form>
  </div>
</div>
<div class="card">
  <div class="table-wrap">
    <table>
      <thead><tr><th style="width:28px"></th><th>Student</th><th>Ledger No.</th><th>Part / Semester</th><th>Total Charged</th><th>Total Paid</th><th>Balance</th><th></th></tr></thead>
      <tbody>
        @forelse($students as $s)
        @php $allPayments = $s->vouchers->flatMap(fn($v) => $v->payments->map(fn($p) => ['payment' => $p, 'voucher' => $v]))->sortByDesc(fn($row) => $row['payment']->paid_at)->values(); @endphp
        <tr class="ledger-student-row" data-target="student-payments-{{ $s->id }}" style="cursor:pointer">
          <td><i class="fas fa-chevron-right ledger-toggle-icon" style="font-size:11px;color:var(--text-muted);transition:transform .15s"></i></td>
          <td style="font-weight:600">{{ $s->name }}<br>@include('partials.report-student-status', ['statusStudent' => $s]) <span style="font-size:11px;color:var(--text-muted)">({{ $s->student_id }})</span></td>
          <td>{{ $s->ledger_number ?: '—' }}</td>
          <td>{{ $s->schoolClass->name ?? '—' }}</td>
          <td>Rs. {{ number_format($s->total_charged ?? 0, 0) }}</td>
          <td>Rs. {{ number_format($s->total_paid ?? 0, 0) }}</td>
          <td style="font-weight:700;color:var(--danger)">Rs. {{ number_format(($s->total_charged ?? 0) - ($s->total_paid ?? 0), 0) }}</td>
          <td><a href="{{ route('ledger.student', $s) }}" class="btn btn-outline btn-sm" onclick="event.stopPropagation()"><i class="fas fa-eye"></i> View</a></td>
        </tr>
        <tr id="student-payments-{{ $s->id }}" class="ledger-student-payments" style="display:none">
          <td colspan="8" style="padding:0;background:var(--bg-subtle,#f8f9fb)">
            @if($allPayments->isEmpty())
              <div style="padding:14px 20px;color:var(--text-muted);font-size:13px">No fee payments recorded yet for this student.</div>
            @else
              <table style="width:100%;font-size:13px">
                <thead>
                  <tr style="color:var(--text-muted)">
                    <th style="padding:8px 20px;text-align:left">Voucher</th>
                    <th style="padding:8px;text-align:left">Month</th>
                    <th style="padding:8px;text-align:left">Paid On</th>
                    <th style="padding:8px;text-align:left">Method</th>
                    <th style="padding:8px;text-align:right">Amount</th>
                    <th style="padding:8px 20px;text-align:left">Status</th>
                  </tr>
                </thead>
                <tbody>
                  @foreach($allPayments as $row)
                  <tr style="border-top:1px solid var(--border,#e5e7eb)">
                    <td style="padding:8px 20px">{{ $row['voucher']->voucher_no }}</td>
                    <td style="padding:8px">{{ $row['voucher']->month ?: ($row['voucher']->due_date?->format('F Y') ?? '—') }}</td>
                    <td style="padding:8px">{{ $row['payment']->paid_at?->format('d M Y') ?? '—' }}</td>
                    <td style="padding:8px">{{ ucfirst(str_replace('_', ' ', $row['payment']->method)) }}</td>
                    <td style="padding:8px;text-align:right;font-weight:600">Rs. {{ number_format($row['payment']->amount, 0) }}</td>
                    <td style="padding:8px 20px">
                      @if($row['payment']->isPending())
                        <span class="badge" style="color:var(--warning)">Pending</span>
                      @elseif($row['payment']->isRejected())
                        <span class="badge" style="color:var(--danger)">Rejected</span>
                      @else
                        <span class="badge" style="color:var(--success,#16a34a)">Verified</span>
                      @endif
                    </td>
                  </tr>
                  @endforeach
                </tbody>
              </table>
            @endif
          </td>
        </tr>
        @empty
        <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted)">No students found.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div style="padding:16px 20px">{{ $students->withQueryString()->links() }}</div>
</div>

<script>
document.querySelectorAll('.ledger-student-row').forEach(function (row) {
  row.addEventListener('click', function () {
    var target = document.getElementById(row.dataset.target);
    var icon = row.querySelector('.ledger-toggle-icon');
    if (!target) return;
    var isOpen = target.style.display !== 'none';
    target.style.display = isOpen ? 'none' : 'table-row';
    icon.style.transform = isOpen ? 'rotate(0deg)' : 'rotate(90deg)';
  });
});
</script>
@endsection
