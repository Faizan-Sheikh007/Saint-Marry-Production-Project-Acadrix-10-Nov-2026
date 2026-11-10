@extends('layouts.app')
@section('title', 'Live Payments')
@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
  <div style="font-size:22px;font-weight:800"><i class="fas fa-tower-broadcast" style="color:var(--primary)"></i> Live Payments</div>
  <div style="font-size:12px;color:var(--text-muted)"><span id="live-dot" style="display:inline-block;width:8px;height:8px;border-radius:50%;background:#22c55e;margin-right:6px"></span>Updating automatically</div>
</div>

@if($pendingVerification->isNotEmpty())
<div class="card" style="margin-bottom:24px;border:1px solid var(--warning)">
  <div class="card-header">
    <div class="card-title" style="color:var(--warning)"><i class="fas fa-hourglass-half"></i> Awaiting Verification ({{ $pendingVerification->count() }})</div>
  </div>
  <div style="padding:0 16px 12px;font-size:12px;color:var(--text-muted)">Students submitted these online with a payment screenshot. Review the proof, then verify or reject — the amount cannot be changed here, only accepted or declined.</div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Student</th><th>Voucher</th><th style="text-align:right">Amount</th><th>Submitted</th><th>Proof</th><th style="text-align:center">Action</th></tr></thead>
      <tbody>
        @foreach($pendingVerification as $p)
        <tr>
          <td>{{ $p->voucher->student->name ?? '—' }}</td>
          <td>{{ $p->voucher->voucher_no ?? '—' }}</td>
          <td style="text-align:right;font-weight:700">Rs.{{ number_format($p->amount) }}</td>
          <td>{{ $p->created_at->diffForHumans() }}</td>
          <td>
            @if($p->proof_path)
              <a href="{{ asset('storage/'.$p->proof_path) }}" target="_blank"><i class="fas fa-image"></i> View</a>
            @else — @endif
          </td>
          <td style="text-align:center">
            <form action="{{ route('payments.verify', $p) }}" method="POST" style="display:inline">
              @csrf
              <button class="btn btn-sm btn-primary" onclick="return confirm('Verify this payment? It will be applied to the voucher.')"><i class="fas fa-check"></i> Verify</button>
            </form>
            <form action="{{ route('payments.reject', $p) }}" method="POST" style="display:inline">
              @csrf
              <button class="btn btn-sm btn-outline" onclick="return confirm('Reject this payment submission?')"><i class="fas fa-xmark"></i> Reject</button>
            </form>
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endif

<div class="card">
  <div class="card-header"><div class="card-title"><i class="fas fa-list"></i> Recent Payments</div></div>
  <div class="table-wrap">
    <table id="live-payments-table">
      <thead><tr><th>Student</th><th>Voucher</th><th style="text-align:right">Amount</th><th>Method</th><th>Status</th><th>Received</th></tr></thead>
      <tbody>
        @foreach($payments as $p)
        <tr>
          <td>{{ $p->voucher->student->name ?? '—' }}</td>
          <td>{{ $p->voucher->voucher_no ?? '—' }}</td>
          <td style="text-align:right">Rs.{{ number_format($p->amount) }}</td>
          <td>{{ ucfirst(str_replace('_',' ',$p->method)) }}</td>
          <td><span class="badge {{ ($p->status ?? 'verified') === 'verified' ? 'success' : (($p->status ?? '') === 'pending' ? 'warning' : 'danger') }}">{{ ucfirst($p->status ?? 'verified') }}</span></td>
          <td>{{ optional($p->paid_at)->diffForHumans() }}</td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>

<script>
// Poll the live feed every 8s so newly-recorded payments (from Accounts or
// student online submissions) show up without a manual refresh.
setInterval(function () {
  fetch("{{ route('live-payments.feed') }}").then(r => r.json()).then(function (data) {
    var tbody = document.querySelector('#live-payments-table tbody');
    if (!tbody) return;
    tbody.innerHTML = data.payments.map(function (p) {
      var statusClass = p.status === 'verified' ? 'success' : (p.status === 'pending' ? 'warning' : 'danger');
      return '<tr><td>' + (p.student || '—') + '</td><td>' + (p.voucher_no || '—') + '</td>' +
        '<td style="text-align:right">Rs.' + Number(p.amount).toLocaleString() + '</td>' +
        '<td>' + p.method + '</td>' +
        '<td><span class="badge ' + statusClass + '">' + p.status + '</span></td>' +
        '<td>' + (p.paid_at || '') + '</td></tr>';
    }).join('');
  }).catch(function () {});
}, 8000);
</script>

@endsection
