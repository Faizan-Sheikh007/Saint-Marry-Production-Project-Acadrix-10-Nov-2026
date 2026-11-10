@extends('layouts.app')
@section('title', 'Results & Marksheets')
@section('page_title', 'Results & Marksheets')
@section('content')

<div class="page-header">
  <div>
    <div class="page-title"><i class="fas fa-award" style="color:var(--primary)"></i> Results &amp; Marksheets</div>
    <div class="page-subtitle">Compile per-subject marks into a class marksheet, then publish it for students and parents to view and download.</div>
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <a href="{{ route('admin.results.by-student') }}" class="btn btn-outline">
      <i class="fas fa-user-magnifying-glass"></i> Find by Student
    </a>
    <a href="{{ route('admin.examinations.index') }}" class="btn btn-outline">
      <i class="fas fa-file-pen"></i> Back to Examinations
    </a>
  </div>
</div>

<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="padding:16px 20px">
    <form method="GET" style="display:flex;gap:10px;flex-wrap:wrap;align-items:flex-end" data-ajax-filter>
      <div style="flex:1;min-width:180px">
        <label class="form-label">Exam name</label>
        <input type="text" name="search" class="form-control" placeholder="Mid Term Exam…" value="{{ request('search') }}">
      </div>
      <div style="min-width:180px">
        <label class="form-label">Class</label>
        <select name="school_class_id" class="form-control">
          <option value="">All Classes</option>
          @foreach($classes as $c)
            <option value="{{ $c->id }}" {{ request('school_class_id') == $c->id ? 'selected' : '' }}>{{ $c->display_name ?? $c->name }}</option>
          @endforeach
        </select>
      </div>
      <div style="display:flex;gap:8px">
        <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
        <a href="{{ route('admin.results.index') }}" class="btn btn-outline" data-ajax-filter-link>Reset</a>
      </div>
    </form>
  </div>
</div>

@forelse($batches as $b)
<div class="card" style="margin-bottom:16px">
  <div class="card-header" style="flex-wrap:wrap;gap:10px">
    <div style="display:flex;align-items:center;gap:12px">
      <div style="width:42px;height:42px;border-radius:10px;background:rgba(137,12,37,.1);display:flex;align-items:center;justify-content:center;color:var(--primary);font-size:17px;flex-shrink:0">
        <i class="fas fa-award"></i>
      </div>
      <div>
        <div class="card-title">{{ $b['exam_name'] }}</div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:2px">
          <span class="tag">{{ $b['class']->display_name ?? $b['class']->name }}</span>
          <span style="margin-left:6px">{{ $b['completion']['subjects_total'] }} subject{{ $b['completion']['subjects_total'] === 1 ? '' : 's' }} &middot; {{ $b['completion']['students_total'] }} student{{ $b['completion']['students_total'] === 1 ? '' : 's' }}</span>
        </div>
      </div>
    </div>
    <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap">
      @if($b['published'])
        <span class="badge success"><i class="fas fa-circle-check"></i> Published</span>
      @else
        <span class="badge gray">Not Published</span>
      @endif
      <a href="{{ route('admin.results.class', ['batchId' => $b['batch_id'], 'schoolClass' => $b['class']->id]) }}" class="btn btn-primary btn-sm">
        <i class="fas fa-arrow-right"></i> Open
      </a>
    </div>
  </div>
  <div class="card-body" style="padding:14px 20px">
    <div class="progress" style="margin-bottom:5px">
      <div class="progress-bar {{ $b['completion']['percent'] >= 100 ? 'success' : ($b['completion']['percent'] > 0 ? 'warning' : 'primary') }}" style="width:{{ $b['completion']['percent'] }}%"></div>
    </div>
    <div style="font-size:11.5px;color:var(--text-muted)">
      {{ $b['completion']['filled_slots'] }} / {{ $b['completion']['expected_slots'] }} marks entered ({{ $b['completion']['percent'] }}%)
      @if($b['published'])
        &middot; Published {{ $b['published_at']->format('d M Y, g:i A') }}
      @endif
    </div>
  </div>
</div>
@empty
<div class="card">
  <div class="empty-state">
    <i class="fas fa-award"></i>
    <p>No examination results to compile yet. Once a teacher enters marks for a scheduled exam, it will show up here.</p>
  </div>
</div>
@endforelse

@endsection
