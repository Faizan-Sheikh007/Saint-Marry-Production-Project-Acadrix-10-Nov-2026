@extends('layouts.app')
@section('title','Messages')
@section('content')

<div class="welcome-banner" style="margin-bottom:24px">
  <div style="position:relative;z-index:1">
    <h1 style="font-size:22px;font-weight:800;margin-bottom:6px"><i class="fas fa-comments"></i> Message a Teacher</h1>
    <p style="opacity:.8">Pick a teacher below to start or continue a conversation.</p>
  </div>
</div>

@if(session('success'))
  <div class="alert alert-success" style="margin-bottom:16px">{{ session('success') }}</div>
@endif

<div class="card">
  <div class="card-header">
    <div class="card-title">All Teachers</div>
  </div>
  @forelse($teachers as $t)
    @php $last = $t->messages->first(); @endphp
    <a href="{{ route('admin.messages.show', $t) }}" style="display:flex;align-items:center;gap:14px;padding:14px 20px;border-bottom:1px solid var(--border);text-decoration:none;color:inherit">
      <div style="width:38px;height:38px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;flex-shrink:0">{{ strtoupper(substr($t->name,0,2)) }}</div>
      <div style="flex:1;min-width:0">
        <div style="font-weight:600;font-size:13.5px">{{ $t->name }}</div>
        <div class="text-sm text-muted" style="overflow:hidden;text-overflow:ellipsis;white-space:nowrap">{{ $last?->message ?? 'No messages yet — say hello.' }}</div>
      </div>
      @if($t->unread_count > 0)
        <span class="badge danger">{{ $t->unread_count }} new</span>
      @endif
      <i class="fas fa-chevron-right text-muted"></i>
    </a>
  @empty
    <div class="empty-state"><i class="fas fa-chalkboard-teacher"></i><p>No active teachers found.</p></div>
  @endforelse
</div>
@endsection
