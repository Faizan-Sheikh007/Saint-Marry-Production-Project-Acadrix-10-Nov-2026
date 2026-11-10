@extends('layouts.app')
@section('title', 'Grading Scale')
@section('page_title', 'Grading Scale')
@section('content')

<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:16px;flex-wrap:wrap;gap:12px">
  <div>
    <div style="font-size:22px;font-weight:800">Grading Scale</div>
    <div style="font-size:12.5px;color:var(--text-muted)">Define the grading formula used to grade every exam result and marksheet.</div>
  </div>
</div>

@if(session('success'))<div class="alert alert-success" style="margin-bottom:16px">{{ session('success') }}</div>@endif
@if($errors->any())<div class="alert alert-danger" style="margin-bottom:16px">{{ $errors->first() }}</div>@endif

<div class="card" style="margin-bottom:20px">
  <div class="card-header"><div class="card-title">Grade Bands</div></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Grade</th><th>Min %</th><th>Max %</th><th>Remarks</th><th style="width:160px">Actions</th></tr></thead>
      <tbody>
        @forelse($bands as $band)
        <tr>
          <form method="POST" action="{{ route('admin.grading-scale.update', $band) }}">
            @csrf @method('PUT')
            <td><input type="text" name="grade" value="{{ $band->grade }}" class="form-control" style="width:80px" required></td>
            <td><input type="number" name="min_percentage" value="{{ $band->min_percentage }}" class="form-control" style="width:90px" step="0.01" min="0" max="100" required></td>
            <td><input type="number" name="max_percentage" value="{{ $band->max_percentage }}" class="form-control" style="width:90px" step="0.01" min="0" max="100" placeholder="open-ended"></td>
            <td><input type="text" name="remarks" value="{{ $band->remarks }}" class="form-control" style="width:160px" maxlength="100"></td>
            <td style="display:flex;gap:6px">
              <button type="submit" class="btn btn-outline btn-sm" title="Save"><i class="fas fa-save"></i></button>
          </form>
              <form method="POST" action="{{ route('admin.grading-scale.destroy', $band) }}" data-confirm="Delete the {{ $band->grade }} grade band? This affects grading for every future/re-computed result." data-confirm-title="Delete grade band?" data-confirm-variant="danger" data-confirm-ok-text="Yes, delete">
                @csrf @method('DELETE')
                <button type="submit" class="btn btn-outline btn-sm" title="Delete"><i class="fas fa-trash"></i></button>
              </form>
            </td>
        </tr>
        @empty
        <tr><td colspan="5" style="text-align:center;color:var(--text-muted);padding:24px">No grade bands defined yet — results cannot be graded until at least one is added.</td></tr>
        @endforelse
      </tbody>
    </table>
  </div>
</div>

<div class="card">
  <div class="card-header"><div class="card-title"><i class="fas fa-plus" style="color:var(--primary);margin-right:6px"></i>Add Grade Band</div></div>
  <div class="card-body">
    <form method="POST" action="{{ route('admin.grading-scale.store') }}" style="display:grid;grid-template-columns:repeat(5,1fr);gap:12px;align-items:end">
      @csrf
      <div class="form-group" style="margin:0">
        <label>Grade</label>
        <input type="text" name="grade" class="form-control" placeholder="e.g. A+" required maxlength="10">
      </div>
      <div class="form-group" style="margin:0">
        <label>Min %</label>
        <input type="number" name="min_percentage" class="form-control" step="0.01" min="0" max="100" required>
      </div>
      <div class="form-group" style="margin:0">
        <label>Max % <small style="color:var(--text-muted)">(blank = open-ended)</small></label>
        <input type="number" name="max_percentage" class="form-control" step="0.01" min="0" max="100">
      </div>
      <div class="form-group" style="margin:0">
        <label>Remarks</label>
        <input type="text" name="remarks" class="form-control" placeholder="e.g. Excellent" maxlength="100">
      </div>
      <button type="submit" class="btn btn-primary"><i class="fas fa-plus"></i> Add</button>
    </form>
  </div>
</div>

<div class="alert" style="background:#eff6ff;border:1px solid #bfdbfe;color:#1e40af;margin-top:20px">
  <i class="fas fa-circle-info"></i> Changes here take effect immediately for newly-entered marks and recompiled marksheets across the Results &amp; Marksheets module.
</div>

@endsection
