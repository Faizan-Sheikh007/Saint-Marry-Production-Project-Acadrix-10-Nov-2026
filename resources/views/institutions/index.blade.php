@extends('layouts.app')
@section('title', 'Institutions')
@section('page_title', 'Affiliated Institutions')

@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
  <div>
    <div style="font-size:22px;font-weight:800">Institutions <span style="color:var(--text-muted);font-size:16px">({{ $items->total() }})</span></div>
    <div style="color:var(--text-muted);font-size:13px;margin-top:2px">
      Affiliated universities/facilities (e.g. Punjab Medical Facility, GC University Faisalabad). Select one on a
      University-level Program to show its name &amp; disclaimer on that department's admission form and installment slip.
    </div>
  </div>
  <a href="{{ route('institutions.create') }}" class="btn btn-primary"><i class="fas fa-plus"></i> Add Institution</a>
</div>

@if(session('success'))
  <div class="alert alert-success" style="margin-bottom:20px"><i class="fas fa-circle-check"></i> {{ session('success') }}</div>
@endif
@if(session('error'))
  <div class="alert alert-danger" style="margin-bottom:20px"><i class="fas fa-triangle-exclamation"></i> {{ session('error') }}</div>
@endif

<div class="card">
  <div class="table-wrap">
    <table>
      <thead>
        <tr>
          <th>Name</th>
          <th>Disclaimer</th>
          <th style="text-align:center">Programs</th>
          <th style="text-align:center">Status</th>
          <th style="text-align:center">Actions</th>
        </tr>
      </thead>
      <tbody>
        @forelse($items as $institution)
        <tr>
          <td style="font-weight:700">{{ $institution->name }}</td>
          <td style="max-width:420px;color:var(--text-muted);font-size:12.5px">
            {{ Str::limit($institution->disclaimer, 140) ?: '— none set —' }}
          </td>
          <td style="text-align:center">{{ $institution->programs_count }}</td>
          <td style="text-align:center">
            @if($institution->is_active)
              <span class="badge success">Active</span>
            @else
              <span class="badge gray">Inactive</span>
            @endif
          </td>
          <td style="text-align:center">
            <a href="{{ route('institutions.edit', $institution) }}" class="btn btn-sm btn-outline"><i class="fas fa-edit"></i> Edit</a>
            <form action="{{ route('institutions.destroy', $institution) }}" method="POST" style="display:inline"
              data-confirm="This permanently removes {{ $institution->name }}." data-confirm-title="Delete this institution?"
              data-confirm-variant="danger" data-confirm-icon="fa-trash" data-confirm-ok-text="Yes, delete">
              @csrf @method('DELETE')
              <button type="submit" class="btn btn-sm btn-outline" style="color:var(--danger)"><i class="fas fa-trash"></i></button>
            </form>
          </td>
        </tr>
        @empty
        <tr><td colspan="5" style="text-align:center;padding:30px;color:var(--text-muted)">No institutions added yet.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div style="margin-top:20px">{{ $items->links() }}</div>

@endsection
