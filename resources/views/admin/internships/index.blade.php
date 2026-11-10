@extends('layouts.app')
@section('title', 'Internships')
@section('content')

<div class="welcome-banner" style="margin-bottom:24px">
  <div style="position:relative;z-index:1;display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:14px">
    <div>
      <h1 style="font-size:22px;font-weight:800;margin-bottom:6px"><i class="fas fa-briefcase"></i> Internships</h1>
      <p style="opacity:.8">Post internship opportunities, pick who sees them, and manage applications &amp; challans.</p>
    </div>
    <a href="{{ route('admin.internships.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> New Internship</a>
  </div>
</div>

@if(session('success'))<div class="alert alert-success" style="margin-bottom:16px">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger" style="margin-bottom:16px">{{ session('error') }}</div>@endif

@php
  $totalPostings  = $internships->count();
  $totalApplicants = $internships->sum(fn($i) => $i->applications->count());
  $totalPending    = $internships->sum(fn($i) => $i->applications->where('status','pending')->count());
  $totalConfirmed  = $internships->sum(fn($i) => $i->applications->where('fee_status','paid')->count());
@endphp

<div class="grid grid-4" style="margin-bottom:24px">
  <div class="stat-card">
    <div class="stat-icon red"><i class="fas fa-briefcase"></i></div>
    <div><div style="font-size:22px;font-weight:800">{{ $totalPostings }}</div><div class="text-sm text-muted">Postings</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fas fa-users"></i></div>
    <div><div style="font-size:22px;font-weight:800">{{ $totalApplicants }}</div><div class="text-sm text-muted">Total Applicants</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon" style="background:rgba(217,119,6,.1);color:#d97706"><i class="fas fa-hourglass-half"></i></div>
    <div><div style="font-size:22px;font-weight:800">{{ $totalPending }}</div><div class="text-sm text-muted">Awaiting Review</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><i class="fas fa-circle-check"></i></div>
    <div><div style="font-size:22px;font-weight:800">{{ $totalConfirmed }}</div><div class="text-sm text-muted">Confirmed &amp; Paid</div></div>
  </div>
</div>

@if($internships->isEmpty())
  <div class="card"><div class="empty-state">
    <i class="fas fa-briefcase"></i>
    <p>No internships posted yet. Create your first one to get started.</p>
    <a href="{{ route('admin.internships.create') }}" class="btn btn-primary" style="margin-top:10px"><i class="fas fa-plus"></i> New Internship</a>
  </div></div>
@else
<div class="grid grid-3" style="gap:20px">
  @foreach($internships as $i)
    @php
      $pending   = $i->applications->where('status','pending')->count();
      $approved  = $i->applications->where('status','admin_approved')->count();
      $confirmed = $i->applications->where('fee_status','paid')->count();
    @endphp
    <div class="card internship-card" style="overflow:hidden;display:flex;flex-direction:column">
      <div style="height:140px;background:linear-gradient(135deg,rgba(139,12,34,.14),rgba(139,12,34,.04));display:flex;align-items:center;justify-content:center;position:relative">
        @if($i->image_path)
          <img src="{{ storage_asset($i->image_path) }}" alt="{{ $i->title }}" style="width:100%;height:100%;object-fit:cover">
        @else
          <i class="fas fa-briefcase" style="font-size:38px;color:var(--primary);opacity:.35"></i>
        @endif
        <div style="position:absolute;top:10px;right:10px">
          @if($i->is_active)
            <span class="badge success">Active</span>
          @else
            <span class="badge gray">Inactive</span>
          @endif
        </div>
      </div>
      <div style="padding:18px 20px;flex:1;display:flex;flex-direction:column">
        <div style="font-size:16px;font-weight:800;margin-bottom:4px">{{ $i->title }}</div>
        <div class="text-sm text-muted" style="margin-bottom:12px;flex:1">{{ \Illuminate\Support\Str::limit($i->description, 90) ?: 'No description provided.' }}</div>

        <div style="display:flex;justify-content:space-between;font-size:12.5px;color:var(--text-muted);margin-bottom:10px">
          <span><i class="fas fa-money-bill-wave"></i> Rs. {{ number_format($i->fee_amount, 0) }}</span>
          <span>
            @if($i->target_type === 'all') <i class="fas fa-globe"></i> All Students
            @elseif($i->target_type === 'programs') <i class="fas fa-sitemap"></i> {{ count($i->target_program_ids ?? []) }} Program(s)
            @else <i class="fas fa-user-check"></i> {{ count($i->target_student_ids ?? []) }} Student(s)
            @endif
          </span>
        </div>

        <div style="display:flex;gap:8px;flex-wrap:wrap;margin-bottom:14px">
          <span class="badge warning">{{ $pending }} Pending</span>
          <span class="badge info">{{ $approved }} Approved</span>
          <span class="badge success">{{ $confirmed }} Confirmed</span>
        </div>

        <div style="display:flex;gap:8px">
          <a href="{{ route('admin.internships.show', $i) }}" class="btn btn-primary" style="flex:1;justify-content:center"><i class="fas fa-list-check"></i> Applicants</a>
          <a href="{{ route('admin.internships.edit', $i) }}" class="btn btn-outline" title="Edit"><i class="fas fa-pen"></i></a>
          <form action="{{ route('admin.internships.destroy', $i) }}" method="POST" onsubmit="return confirm('Delete this internship posting? This cannot be undone.')">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-outline" title="Delete" style="color:#c0192e;border-color:#c0192e"><i class="fas fa-trash"></i></button>
          </form>
        </div>
      </div>
    </div>
  @endforeach
</div>
@endif

@endsection
