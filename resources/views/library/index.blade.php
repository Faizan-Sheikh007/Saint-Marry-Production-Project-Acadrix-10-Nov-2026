@extends('layouts.app')

@section('title', 'Library')
@section('page_title', 'Library Management')

@section('content')

<!-- Header -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
  <div>
    <div style="font-size:22px;font-weight:800">Library <span style="color:var(--text-muted);font-size:16px">({{ $items->total() }} titles)</span></div>
    <div style="color:var(--text-muted);font-size:13px;margin-top:2px">Manage the book catalogue and student issuances</div>
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <a href="{{ route('admin.library.issued') }}" class="btn btn-outline"><i class="fas fa-hand-holding"></i> Issued Books</a>
    <a href="{{ route('admin.library.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Add Book</a>
  </div>
</div>

@if(session('success'))
  <div class="alert alert-success" style="margin-bottom:20px"><i class="fas fa-circle-check"></i> {{ session('success') }}</div>
@endif
@if(session('error'))
  <div class="alert alert-danger" style="margin-bottom:20px"><i class="fas fa-triangle-exclamation"></i> {{ session('error') }}</div>
@endif

<!-- Stat Cards -->
<div class="grid grid-4" style="gap:16px;margin-bottom:22px">
  <div class="card" style="padding:18px 20px;display:flex;align-items:center;gap:14px">
    <div style="width:46px;height:46px;border-radius:12px;background:#eef2ff;display:flex;align-items:center;justify-content:center;flex-shrink:0">
      <i class="fas fa-book" style="color:#4338ca;font-size:18px"></i>
    </div>
    <div>
      <div style="font-size:20px;font-weight:800">{{ $stats['titles'] }}</div>
      <div style="font-size:11.5px;color:var(--text-muted)">Book Titles</div>
    </div>
  </div>
  <div class="card" style="padding:18px 20px;display:flex;align-items:center;gap:14px">
    <div style="width:46px;height:46px;border-radius:12px;background:#eff6ff;display:flex;align-items:center;justify-content:center;flex-shrink:0">
      <i class="fas fa-layer-group" style="color:#2563eb;font-size:18px"></i>
    </div>
    <div>
      <div style="font-size:20px;font-weight:800">{{ $stats['available'] }} <span style="font-size:12px;color:var(--text-muted);font-weight:600">/ {{ $stats['copies'] }}</span></div>
      <div style="font-size:11.5px;color:var(--text-muted)">Copies Available</div>
    </div>
  </div>
  <a href="{{ route('admin.library.issued', ['status' => 'issued']) }}" class="card" style="padding:18px 20px;display:flex;align-items:center;gap:14px;text-decoration:none;color:inherit">
    <div style="width:46px;height:46px;border-radius:12px;background:#fff7ed;display:flex;align-items:center;justify-content:center;flex-shrink:0">
      <i class="fas fa-hand-holding" style="color:#ea580c;font-size:18px"></i>
    </div>
    <div>
      <div style="font-size:20px;font-weight:800">{{ $stats['issued_now'] }}</div>
      <div style="font-size:11.5px;color:var(--text-muted)">Currently Issued</div>
    </div>
  </a>
  <a href="{{ route('admin.library.issued', ['status' => 'overdue']) }}" class="card" style="padding:18px 20px;display:flex;align-items:center;gap:14px;text-decoration:none;color:inherit">
    <div style="width:46px;height:46px;border-radius:12px;background:#fef2f2;display:flex;align-items:center;justify-content:center;flex-shrink:0">
      <i class="fas fa-clock" style="color:#dc2626;font-size:18px"></i>
    </div>
    <div>
      <div style="font-size:20px;font-weight:800">{{ $stats['overdue_now'] }}</div>
      <div style="font-size:11.5px;color:var(--text-muted)">Overdue</div>
    </div>
  </a>
</div>

<!-- Filters -->
<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="padding:14px 20px">
    <form method="GET" action="{{ route('admin.library.index') }}" style="display:flex;gap:12px;flex-wrap:wrap;align-items:flex-end" data-ajax-filter>
      <div class="form-group" style="margin-bottom:0;flex:1;min-width:220px">
        <label class="form-label">Search</label>
        <input type="text" name="search" class="form-control" placeholder="Title, author, ISBN, category..." value="{{ request('search') }}">
      </div>
      <div class="form-group" style="margin-bottom:0;min-width:160px">
        <label class="form-label">Category</label>
        <select name="category" class="form-control">
          <option value="">All Categories</option>
          @foreach($categories as $cat)
            <option value="{{ $cat }}" {{ request('category') == $cat ? 'selected' : '' }}>{{ $cat }}</option>
          @endforeach
        </select>
      </div>
      <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
      <a href="{{ route('admin.library.index') }}" class="btn btn-outline" data-ajax-filter-link>Reset</a>
    </form>
  </div>
</div>

<!-- Book Grid -->
<div class="grid grid-3" style="gap:18px">
  @forelse($items as $item)
    @php
      $pct = $item->copies > 0 ? round(($item->available_copies / $item->copies) * 100) : 0;
      $barColor = $pct === 0 ? '#dc2626' : ($pct < 40 ? '#ea580c' : '#16a34a');
    @endphp
    <div class="card" style="display:flex;flex-direction:column;overflow:hidden">
      <div style="background:linear-gradient(135deg,var(--primary),#7c3aed);padding:22px 20px;color:#fff;position:relative">
        <div style="font-size:10.5px;font-weight:700;text-transform:uppercase;letter-spacing:.06em;opacity:.85;margin-bottom:6px">{{ $item->category ?? 'Uncategorized' }}</div>
        <div style="font-size:16.5px;font-weight:800;line-height:1.3;min-height:44px">{{ $item->name }}</div>
        <div style="font-size:12.5px;opacity:.9;margin-top:4px">{{ $item->author ?: 'Unknown Author' }}</div>
        <i class="fas fa-book-open" style="position:absolute;right:16px;top:16px;font-size:22px;opacity:.25"></i>
      </div>

      <div class="card-body" style="flex:1;display:flex;flex-direction:column">
        <div style="display:flex;flex-direction:column;gap:6px;font-size:12.5px;color:var(--text-muted);margin-bottom:14px;min-height:66px">
          <div><i class="fas fa-barcode" style="width:16px;color:var(--primary)"></i> {{ $item->isbn ?: '—' }}</div>
          <div><i class="fas fa-location-dot" style="width:16px;color:var(--primary)"></i> {{ $item->rack ? 'Rack '.$item->rack : '—' }}</div>
          <div>
            <i class="fas fa-graduation-cap" style="width:16px;color:var(--primary)"></i>
            @if($item->program || $item->schoolClass || $item->industry)
              {{ $item->program->name ?? '' }}{{ $item->schoolClass ? ' · '.$item->schoolClass->section_label : '' }}{{ $item->industry ? ' · '.$item->industry : '' }}
            @else
              General collection
            @endif
          </div>
        </div>

        <div style="margin-bottom:14px">
          <div style="display:flex;justify-content:space-between;font-size:11.5px;font-weight:600;color:#334155;margin-bottom:5px">
            <span>{{ $item->available_copies }} of {{ $item->copies }} available</span>
            <span>{{ $pct }}%</span>
          </div>
          <div style="background:#e2e8f0;height:7px;border-radius:20px;overflow:hidden">
            <div style="width:{{ $pct }}%;background:{{ $barColor }};height:100%;border-radius:20px"></div>
          </div>
        </div>

        <div style="margin-top:auto;display:flex;gap:8px;flex-wrap:wrap">
          <a href="{{ route('admin.library.show', $item) }}" class="btn btn-outline btn-sm" style="flex:1;justify-content:center"><i class="fas fa-eye"></i> View</a>
          <a href="{{ route('admin.library.edit', $item) }}" class="btn btn-outline btn-sm" style="flex:1;justify-content:center"><i class="fas fa-edit"></i> Edit</a>
          @if($item->available_copies > 0)
            <a href="{{ route('admin.library.issue.form', $item) }}" class="btn btn-primary btn-sm" style="flex:1;justify-content:center"><i class="fas fa-hand-holding"></i> Issue</a>
          @else
            <span class="btn btn-sm" style="flex:1;justify-content:center;background:#f1f5f9;color:#94a3b8;cursor:not-allowed">All Issued</span>
          @endif
        </div>
        <form action="{{ route('admin.library.destroy', $item) }}" method="POST"
          data-confirm="This permanently removes '{{ $item->name }}' from the catalogue. This can't be undone."
          data-confirm-title="Delete this book?"
          data-confirm-variant="danger"
          data-confirm-icon="fa-trash"
          data-confirm-ok-text="Yes, delete"
          style="margin-top:8px">
          @csrf @method('DELETE')
          <button type="submit" class="btn btn-outline btn-sm" style="width:100%;justify-content:center;color:var(--danger)"><i class="fas fa-trash"></i> Remove</button>
        </form>
      </div>
    </div>
  @empty
    <div class="card" style="grid-column:1/-1">
      <div class="card-body" style="text-align:center;padding:50px 20px;color:var(--text-muted)">
        <i class="fas fa-book-bookmark" style="font-size:36px;opacity:0.3;display:block;margin-bottom:14px"></i>
        No books found.
        <a href="{{ route('admin.library.create') }}" style="color:var(--primary)">Add your first book</a>
      </div>
    </div>
  @endforelse
</div>

<div style="margin-top:20px">{{ $items->links() }}</div>

@endsection
