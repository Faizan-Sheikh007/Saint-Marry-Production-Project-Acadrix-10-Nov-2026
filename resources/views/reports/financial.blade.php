@extends('layouts.app')
@section('title', 'Financial Report')
@section('page_title', 'Financial Report')
@section('content')
<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px">
  <a href="{{ route('admin.reports.index') }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
  <div style="font-size:22px;font-weight:800">Financial Report</div>
</div>

{{-- Summary Cards --}}
<div class="grid grid-4" style="gap:16px;margin-bottom:24px">
  <div class="card" style="padding:20px">
    <div style="font-size:13px;color:var(--text-muted);margin-bottom:6px">Total Billed</div>
    <div style="font-size:28px;font-weight:800;color:var(--primary)">Rs. {{ number_format($totalBilled, 0) }}</div>
  </div>
  <div class="card" style="padding:20px">
    <div style="font-size:13px;color:var(--text-muted);margin-bottom:6px">Collected</div>
    <div style="font-size:28px;font-weight:800;color:var(--success)">Rs. {{ number_format($totalCollected, 0) }}</div>
  </div>
  <div class="card" style="padding:20px">
    <div style="font-size:13px;color:var(--text-muted);margin-bottom:6px">Pending</div>
    <div style="font-size:28px;font-weight:800;color:var(--danger)">Rs. {{ number_format($totalPending, 0) }}</div>
  </div>
  <div class="card" style="padding:20px">
    <div style="font-size:13px;color:var(--text-muted);margin-bottom:6px">Collection Rate</div>
    <div style="font-size:28px;font-weight:800;color:var(--warning)">
      {{ $totalBilled > 0 ? number_format(($totalCollected / $totalBilled) * 100, 1) : 0 }}%
    </div>
  </div>
</div>

{{-- Table --}}
<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>#</th>
          <th>Student</th>
          <th>Fee Type</th>
          <th>Amount</th>
          <th>Due Date</th>
          <th>Paid On</th>
          <th>Status</th>
        </tr>
      </thead>
      <tbody>
        @forelse($fees as $fee)
        <tr>
          <td>{{ $loop->iteration }}</td>
          <td><strong>{{ $fee->student->name ?? '—' }}<br>@include('partials.report-student-status', ['statusStudent' => $fee->student])</strong></td>
          <td>{{ $fee->fee_type ?? '—' }}</td>
          <td>Rs. {{ number_format($fee->amount, 0) }}</td>
          <td>{{ $fee->due_date ? \Carbon\Carbon::parse($fee->due_date)->format('d M Y') : '—' }}</td>
          <td>{{ $fee->paid_at ? \Carbon\Carbon::parse($fee->paid_at)->format('d M Y') : '—' }}</td>
          <td>
            <span class="badge {{ $fee->status == 'paid' ? 'success' : 'danger' }}">
              {{ ucfirst($fee->status) }}
            </span>
          </td>
        </tr>
        @empty
        <tr><td colspan="7" style="text-align:center;padding:40px;color:var(--text-muted)">No fee records found.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>

  {{-- Pagination --}}
  @if($fees->hasPages())
  <div style="padding:16px 20px;border-top:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:12px">
    <div style="font-size:13px;color:var(--text-muted)">
      Showing {{ $fees->firstItem() }} to {{ $fees->lastItem() }} of {{ $fees->total() }} records
    </div>
    <div style="display:flex;gap:4px;align-items:center">
      @if($fees->onFirstPage())
        <span class="page-btn" style="cursor:not-allowed;opacity:.4">&laquo;</span>
      @else
        <a href="{{ $fees->appends(request()->query())->previousPageUrl() }}" class="page-btn">&laquo;</a>
      @endif

      @foreach($fees->appends(request()->query())->getUrlRange(
          max(1, $fees->currentPage() - 2),
          min($fees->lastPage(), $fees->currentPage() + 2)
      ) as $page => $url)
        @if($page == $fees->currentPage())
          <span style="padding:6px 12px;border:1px solid #890C25;border-radius:6px;background:#890C25;color:#fff;font-size:13px;font-weight:600">{{ $page }}</span>
        @else
          <a href="{{ $url }}" class="page-btn">{{ $page }}</a>
        @endif
      @endforeach

      @if($fees->hasMorePages())
        <a href="{{ $fees->appends(request()->query())->nextPageUrl() }}" class="page-btn">&raquo;</a>
      @else
        <span class="page-btn" style="cursor:not-allowed;opacity:.4">&raquo;</span>
      @endif
    </div>
  </div>
  @endif

</div>
@endsection
