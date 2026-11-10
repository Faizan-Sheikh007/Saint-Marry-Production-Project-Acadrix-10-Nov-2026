@extends('layouts.app')

@section('title', $library->name)
@section('page_title', 'Book Details')

@section('content')

<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px;flex-wrap:wrap">
  <a href="{{ route('admin.library.index') }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
  <a href="{{ route('admin.library.edit', $library) }}" class="btn btn-outline btn-sm"><i class="fas fa-edit"></i> Edit</a>
  @if($library->available_copies > 0)
    <a href="{{ route('admin.library.issue.form', $library) }}" class="btn btn-primary btn-sm"><i class="fas fa-hand-holding"></i> Issue Book</a>
  @endif
</div>

@if(session('success'))
  <div class="alert alert-success" style="margin-bottom:20px"><i class="fas fa-circle-check"></i> {{ session('success') }}</div>
@endif
@if(session('error'))
  <div class="alert alert-danger" style="margin-bottom:20px"><i class="fas fa-triangle-exclamation"></i> {{ session('error') }}</div>
@endif

<div class="grid grid-2" style="gap:20px">
  <!-- Book Card -->
  <div class="card">
    <div style="background:linear-gradient(135deg,var(--primary),#7c3aed);padding:28px 24px;color:#fff;position:relative">
      <div style="font-size:11px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;opacity:.85;margin-bottom:8px">{{ $library->category ?? 'Uncategorized' }}</div>
      <div style="font-size:22px;font-weight:800;line-height:1.3">{{ $library->name }}</div>
      <div style="font-size:14px;opacity:.9;margin-top:6px">{{ $library->author ?: 'Unknown Author' }}</div>
      <i class="fas fa-book-open" style="position:absolute;right:22px;top:22px;font-size:30px;opacity:.25"></i>
    </div>

    <div class="card-body">
      <div style="display:flex;flex-wrap:wrap;gap:12px;margin-bottom:18px">
        <div style="flex:1;min-width:100px;text-align:center;background:#eff6ff;border-radius:10px;padding:12px">
          <div style="font-size:20px;font-weight:800;color:#2563eb">{{ $library->copies }}</div>
          <div style="font-size:11px;color:#64748b;margin-top:2px">Total Copies</div>
        </div>
        <div style="flex:1;min-width:100px;text-align:center;background:#f0fdf4;border-radius:10px;padding:12px">
          <div style="font-size:20px;font-weight:800;color:#16a34a">{{ $library->available_copies }}</div>
          <div style="font-size:11px;color:#64748b;margin-top:2px">Available</div>
        </div>
        <div style="flex:1;min-width:100px;text-align:center;background:#fff7ed;border-radius:10px;padding:12px">
          <div style="font-size:20px;font-weight:800;color:#ea580c">{{ $library->copies - $library->available_copies }}</div>
          <div style="font-size:11px;color:#64748b;margin-top:2px">Issued Out</div>
        </div>
      </div>

      <div style="display:flex;flex-direction:column;gap:10px;font-size:13.5px">
        @if($library->isbn)<div><i class="fas fa-barcode" style="width:20px;color:var(--primary)"></i> {{ $library->isbn }}</div>@endif
        @if($library->rack)<div><i class="fas fa-location-dot" style="width:20px;color:var(--primary)"></i> Rack {{ $library->rack }}</div>@endif
        @if($library->program)<div><i class="fas fa-graduation-cap" style="width:20px;color:var(--primary)"></i> {{ $library->program->name }}</div>@endif
        @if($library->schoolClass)<div><i class="fas fa-chalkboard" style="width:20px;color:var(--primary)"></i> {{ $library->schoolClass->display_name }}</div>@endif
        @if($library->industry)<div><i class="fas fa-industry" style="width:20px;color:var(--primary)"></i> {{ $library->industry }}</div>@endif
      </div>
    </div>
  </div>

  <!-- Issue History -->
  <div class="card">
    <div class="card-header"><div class="card-title"><i class="fas fa-clock-rotate-left" style="color:var(--primary)"></i> Issue History</div></div>
    <div class="card-body" style="padding:0">
      @forelse($issues as $issue)
        @php $overdue = $issue->isOverdue(); @endphp
        <div style="padding:16px 20px;border-bottom:1px solid #f1f5f9">
          <div style="display:flex;align-items:center;justify-content:space-between;gap:10px;flex-wrap:wrap">
            <div style="display:flex;align-items:center;gap:12px;min-width:0">
              <div style="width:38px;height:38px;border-radius:50%;background:{{ $issue->status === 'returned' ? '#94a3b8' : 'var(--primary)' }};display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;flex-shrink:0;font-size:12.5px">
                {{ strtoupper(substr($issue->student_name, 0, 2)) }}
              </div>
              <div style="min-width:0">
                <div style="font-weight:600;font-size:13.5px;white-space:nowrap;overflow:hidden;text-overflow:ellipsis">{{ $issue->student_name }}</div>
                <div style="font-size:11.5px;color:var(--text-muted)">
                  {{ $issue->student_roll_no ?? '—' }} @if($issue->class_name) &middot; {{ $issue->class_name }} @endif
                </div>
              </div>
            </div>
            @if($issue->status === 'returned')
              <span class="badge gray">Returned {{ $issue->returned_date?->format('d M') }}</span>
            @elseif($overdue)
              <span class="badge danger">{{ $issue->overdueDays() }}d Overdue</span>
            @else
              <span class="badge success">Issued</span>
            @endif
          </div>

          <div style="display:flex;flex-wrap:wrap;gap:14px;font-size:11.5px;color:var(--text-muted);margin-top:10px">
            <div><i class="fas fa-calendar-day"></i> Issued: {{ $issue->issue_date->format('d M, Y') }}</div>
            <div><i class="fas fa-calendar-check"></i> Due: {{ $issue->due_date->format('d M, Y') }}</div>
            <div><i class="fas fa-phone"></i> {{ $issue->contact_number }}</div>
            @if($issue->fine_per_day > 0)
              <div><i class="fas fa-coins"></i> Rs. {{ number_format($issue->fine_per_day, 0) }}/day fine</div>
            @endif
          </div>

          @if($issue->status === 'issued' && $overdue)
            <div style="font-size:11.5px;color:#dc2626;margin-top:6px;font-weight:600">
              <i class="fas fa-triangle-exclamation"></i> Estimated fine if returned today: Rs. {{ number_format($issue->estimatedFine(), 0) }}
            </div>
          @endif

          @if($issue->status === 'returned' && $issue->fine_amount > 0)
            <div style="font-size:11.5px;color:#dc2626;margin-top:6px;font-weight:600">
              <i class="fas fa-coins"></i> Fine charged: Rs. {{ number_format($issue->fine_amount, 0) }}
            </div>
          @endif

          @if($issue->status === 'issued')
            <form action="{{ route('admin.library.issues.return', $issue) }}" method="POST"
              data-confirm="Mark this copy as returned by {{ $issue->student_name }}?{{ $overdue ? ' An overdue fine of Rs. '.number_format($issue->estimatedFine(),0).' will be recorded.' : '' }}"
              data-confirm-title="Return this book?"
              data-confirm-variant="{{ $overdue ? 'warning' : 'primary' }}"
              data-confirm-icon="fa-undo"
              data-confirm-ok-text="Yes, mark returned"
              style="margin-top:10px">
              @csrf
              <button type="submit" class="btn btn-outline btn-sm"><i class="fas fa-undo"></i> Mark Returned</button>
            </form>
          @endif
        </div>
      @empty
        <div style="text-align:center;padding:40px 20px;color:var(--text-muted)">
          <i class="fas fa-hand-holding" style="font-size:28px;opacity:.3;display:block;margin-bottom:12px"></i>
          This book hasn't been issued to anyone yet.
        </div>
      @endforelse
    </div>
  </div>
</div>

@endsection
