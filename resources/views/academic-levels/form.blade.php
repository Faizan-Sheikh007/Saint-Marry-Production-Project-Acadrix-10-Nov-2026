@extends('layouts.app')
@section('title', isset($academicLevel) ? 'Edit Academic Level' : 'Add Academic Level')
@section('page_title', isset($academicLevel) ? 'Edit Academic Level' : 'Add Academic Level')

@section('content')

<form action="{{ isset($academicLevel) ? route('academic-levels.update', $academicLevel) : route('academic-levels.store') }}" method="POST">
  @csrf
  @if(isset($academicLevel)) @method('PUT') @endif

  <div class="card" style="max-width:640px">
    <div class="card-header"><div class="card-title"><i class="fas fa-layer-group" style="color:var(--primary)"></i> Academic Level Details</div></div>
    <div class="card-body">
      <div class="form-group">
        <label class="form-label">Name</label>
        <input type="text" name="name" class="form-control" required
               value="{{ old('name', $academicLevel->name ?? '') }}"
               placeholder="e.g. College, University, Diploma">
      </div>

      <div class="form-group">
        <label class="form-label">Sort Order</label>
        <input type="number" name="sort_order" class="form-control" min="0"
               value="{{ old('sort_order', $academicLevel->sort_order ?? 0) }}">
        <small style="color:var(--text-muted)">Lower numbers appear first on the admission form.</small>
      </div>

      <div class="form-group">
        <label style="display:flex;align-items:center;gap:8px">
          <input type="checkbox" name="requires_institution" value="1" {{ old('requires_institution', $academicLevel->requires_institution ?? false) ? 'checked' : '' }}>
          Requires Institution (shows the Institution picker on the admission form when this level is selected)
        </label>
      </div>

      <div class="form-group">
        <label style="display:flex;align-items:center;gap:8px">
          <input type="checkbox" name="is_active" value="1" {{ old('is_active', $academicLevel->is_active ?? true) ? 'checked' : '' }}>
          Active (selectable on the admission form)
        </label>
      </div>
    </div>
  </div>

  <div style="margin-top:20px;display:flex;gap:10px">
    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
    <a href="{{ route('academic-levels.index') }}" class="btn btn-outline">Cancel</a>
  </div>
</form>

@endsection
