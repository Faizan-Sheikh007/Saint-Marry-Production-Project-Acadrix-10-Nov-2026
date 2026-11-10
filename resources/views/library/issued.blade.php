@extends('layouts.app')

@section('title', 'Issued Books')
@section('page_title', 'Issued Books')

@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
  <div>
    <div style="font-size:22px;font-weight:800">Issued Books</div>
    <div style="color:var(--text-muted);font-size:13px;margin-top:2px">Every book currently out, overdue, or already returned</div>
  </div>
  <a href="{{ route('admin.library.index') }}" class="btn btn-outline"><i class="fas fa-book-open"></i> Back to Catalogue</a>
</div>

@if(session('success'))
  <div class="alert alert-success" style="margin-bottom:20px"><i class="fas fa-circle-check"></i> {{ session('success') }}</div>
@endif
@if(session('error'))
  <div class="alert alert-danger" style="margin-bottom:20px"><i class="fas fa-triangle-exclamation"></i> {{ session('error') }}</div>
@endif

<!-- Stat Cards (also act as quick filters) -->
<div class="grid grid-3" style="gap:16px;margin-bottom:22px">
  <a href="{{ route('admin.library.issued', ['status' => 'issued']) }}" class="card" style="padding:18px 20px;display:flex;align-items:center;gap:14px;text-decoration:none;color:inherit;border:1.5px solid {{ request('status') == 'issued' ? 'var(--primary)' : 'transparent' }}">
    <div style="width:46px;height:46px;border-radius:12px;background:#eff6ff;display:flex;align-items:center;justify-content:center;flex-shrink:0">
      <i class="fas fa-hand-holding" style="color:#2563eb;font-size:18px"></i>
    </div>
    <div>
      <div style="font-size:20px;font-weight:800">{{ $stats['issued'] }}</div>
      <div style="font-size:11.5px;color:var(--text-muted)">On Time</div>
    </div>
  </a>
  <a href="{{ route('admin.library.issued', ['status' => 'overdue']) }}" class="card" style="padding:18px 20px;display:flex;align-items:center;gap:14px;text-decoration:none;color:inherit;border:1.5px solid {{ request('status') == 'overdue' ? 'var(--danger)' : 'transparent' }}">
    <div style="width:46px;height:46px;border-radius:12px;background:#fef2f2;display:flex;align-items:center;justify-content:center;flex-shrink:0">
      <i class="fas fa-triangle-exclamation" style="color:#dc2626;font-size:18px"></i>
    </div>
    <div>
      <div style="font-size:20px;font-weight:800">{{ $stats['overdue'] }}</div>
      <div style="font-size:11.5px;color:var(--text-muted)">Overdue</div>
    </div>
  </a>
  <a href="{{ route('admin.library.issued', ['status' => 'returned']) }}" class="card" style="padding:18px 20px;display:flex;align-items:center;gap:14px;text-decoration:none;color:inherit;border:1.5px solid {{ request('status') == 'returned' ? '#16a34a' : 'transparent' }}">
    <div style="width:46px;height:46px;border-radius:12px;background:#f0fdf4;display:flex;align-items:center;justify-content:center;flex-shrink:0">
      <i class="fas fa-circle-check" style="color:#16a34a;font-size:18px"></i>
    </div>
    <div>
      <div style="font-size:20px;font-weight:800">{{ $stats['returned'] }}</div>
      <div style="font-size:11.5px;color:var(--text-muted)">Returned</div>
    </div>
  </a>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="padding:14px 20px">
    <form method="GET" action="{{ route('admin.library.issued') }}" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end" data-ajax-filter>
      <div class="form-group" style="margin-bottom:0;flex:1;min-width:220px">
        <label class="form-label">Search</label>
        <input type="text" name="search" class="form-control" placeholder="Student name, roll no, book title, contact..." value="{{ request('search') }}">
      </div>
      <div class="form-group" style="margin-bottom:0;min-width:160px">
        <label class="form-label">Status</label>
        <select name="status" class="form-control">
          <option value="">All</option>
          <option value="issued"   {{ request('status') == 'issued'   ? 'selected' : '' }}>On Time</option>
          <option value="overdue"  {{ request('status') == 'overdue'  ? 'selected' : '' }}>Overdue</option>
          <option value="returned" {{ request('status') == 'returned' ? 'selected' : '' }}>Returned</option>
        </select>
      </div>
      <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
      <a href="{{ route('admin.library.issued') }}" class="btn btn-outline" data-ajax-filter-link>Reset</a>
    </form>
  </div>
</div>

<!-- Issues Table -->
<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Book</th>
          <th>Student</th>
          <th>Class / Program</th>
          <th>Contact</th>
          <th>Issued</th>
          <th>Due</th>
          <th>Status</th>
          <th>Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($issues as $issue)
          @php $overdue = $issue->isOverdue(); @endphp
          <tr>
            <td>
              <div style="font-weight:600">{{ $issue->book->name ?? 'Deleted Book' }}</div>
              @if($issue->book?->isbn)<div style="font-size:11px;color:var(--text-muted)">{{ $issue->book->isbn }}</div>@endif
            </td>
            <td>
              <div style="display:flex;align-items:center;gap:10px">
                <div style="width:32px;height:32px;border-radius:50%;background:{{ $issue->status === 'returned' ? '#94a3b8' : 'var(--primary)' }};display:flex;align-items:center;justify-content:center;font-weight:700;color:#fff;flex-shrink:0;font-size:11.5px">
                  {{ strtoupper(substr($issue->student_name, 0, 2)) }}
                </div>
                <div>
                  <div style="font-weight:600;font-size:13px">{{ $issue->student_name }}</div>
                  <div style="font-size:11px;color:var(--text-muted)">{{ $issue->student_roll_no ?: '—' }}</div>
                </div>
              </div>
            </td>
            <td style="font-size:12.5px;color:var(--text-muted)">
              {{ $issue->class_name ?: '—' }}
              @if($issue->program_name)<div>{{ $issue->program_name }}</div>@endif
            </td>
            <td style="font-size:12.5px">{{ $issue->contact_number }}</td>
            <td style="font-size:12.5px;color:var(--text-muted)">{{ $issue->issue_date->format('d M, Y') }}</td>
            <td style="font-size:12.5px;color:{{ $overdue ? '#dc2626' : 'var(--text-muted)' }};font-weight:{{ $overdue ? '700' : '400' }}">{{ $issue->due_date->format('d M, Y') }}</td>
            <td>
              @if($issue->status === 'returned')
                <span class="badge gray">Returned {{ $issue->returned_date?->format('d M') }}</span>
                @if($issue->fine_amount > 0)
                  <div style="font-size:11px;color:#dc2626;margin-top:3px">Fine: Rs. {{ number_format($issue->fine_amount, 0) }}</div>
                @endif
              @elseif($overdue)
                <span class="badge danger">{{ $issue->overdueDays() }}d overdue</span>
                <div style="font-size:11px;color:#dc2626;margin-top:3px">Est. fine: Rs. {{ number_format($issue->estimatedFine(), 0) }}</div>
              @else
                <span class="badge success">Issued</span>
              @endif
            </td>
            <td>
              <div style="display:flex;gap:6px">
                @if($issue->book)
                  <a href="{{ route('admin.library.show', $issue->book) }}" class="btn btn-outline btn-sm" title="View Book"><i class="fas fa-book"></i></a>
                @endif
                @if($issue->status === 'issued')
                  <form action="{{ route('admin.library.issues.return', $issue) }}" method="POST"
                    data-confirm="Mark this copy as returned by {{ $issue->student_name }}?{{ $overdue ? ' An overdue fine of Rs. '.number_format($issue->estimatedFine(),0).' will be recorded.' : '' }}"
                    data-confirm-title="Return this book?"
                    data-confirm-variant="{{ $overdue ? 'warning' : 'primary' }}"
                    data-confirm-icon="fa-undo"
                    data-confirm-ok-text="Yes, mark returned">
                    @csrf
                    <button type="submit" class="btn btn-outline btn-sm" title="Mark Returned"><i class="fas fa-undo"></i></button>
                  </form>
                @endif
              </div>
            </td>
          </tr>
        @empty
          <tr>
            <td colspan="8" style="text-align:center;padding:40px;color:var(--text-muted)">
              <i class="fas fa-hand-holding" style="font-size:32px;opacity:0.3;display:block;margin-bottom:12px"></i>
              No book issuances found.
            </td>
          </tr>
        @endforelse
      </tbody>
    </table>
  </div>
  <div style="padding:16px 20px">{{ $issues->links() }}</div>
</div>

@endsection
