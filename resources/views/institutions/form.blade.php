@extends('layouts.app')
@section('title', isset($institution) ? 'Edit Institution' : 'Add Institution')
@section('page_title', isset($institution) ? 'Edit Institution' : 'Add Institution')

@section('content')

<form action="{{ isset($institution) ? route('institutions.update', $institution) : route('institutions.store') }}" method="POST">
  @csrf
  @if(isset($institution)) @method('PUT') @endif

  <div class="card" style="max-width:640px">
    <div class="card-header"><div class="card-title"><i class="fas fa-university" style="color:var(--primary)"></i> Institution Details</div></div>
    <div class="card-body">
      <div class="form-group">
        <label class="form-label">Facility / University Name</label>
        <input type="text" name="name" class="form-control" required
               value="{{ old('name', $institution->name ?? '') }}"
               placeholder="e.g. Punjab Medical Facility">
      </div>

      <div class="form-group">
        <label class="form-label">Disclaimer</label>
        <textarea name="disclaimer" class="form-control" rows="6"
                  placeholder="Shown on the admission form and installment slip for any University-level Program linked to this institution, unless a specific Program overrides it.">{{ old('disclaimer', $institution->disclaimer ?? '') }}</textarea>
        <small style="color:var(--text-muted)">Individual programs/departments can override this with their own disclaimer text.</small>
      </div>

      <div class="form-group">
        <label style="display:flex;align-items:center;gap:8px">
          <input type="checkbox" name="is_active" value="1" {{ old('is_active', $institution->is_active ?? true) ? 'checked' : '' }}>
          Active (selectable on University-level Programs)
        </label>
      </div>
    </div>
  </div>

  <div style="margin-top:20px;display:flex;gap:10px">
    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save</button>
    <a href="{{ route('institutions.index') }}" class="btn btn-outline">Cancel</a>
  </div>
</form>

@endsection
