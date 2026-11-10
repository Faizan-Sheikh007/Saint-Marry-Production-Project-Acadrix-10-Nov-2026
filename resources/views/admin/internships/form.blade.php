@extends('layouts.app')
@section('title', isset($internship) && $internship->exists ? 'Edit Internship' : 'New Internship')
@section('content')

@php $editing = $internship->exists; @endphp

<div class="welcome-banner" style="margin-bottom:24px">
  <div style="position:relative;z-index:1">
    <h1 style="font-size:22px;font-weight:800;margin-bottom:6px"><i class="fas fa-briefcase"></i> {{ $editing ? 'Edit Internship' : 'New Internship' }}</h1>
    <p style="opacity:.8">{{ $editing ? 'Update this posting.' : 'Post a new internship opportunity and choose who can see it.' }}</p>
  </div>
</div>

@if($errors->any())
  <div class="alert alert-danger" style="margin-bottom:16px">
    <ul style="margin:0;padding-left:18px">@foreach($errors->all() as $e)<li>{{ $e }}</li>@endforeach</ul>
  </div>
@endif

<form method="POST"
      action="{{ $editing ? route('admin.internships.update', $internship) : route('admin.internships.store') }}"
      enctype="multipart/form-data">
  @csrf
  @if($editing) @method('PUT') @endif

  <div class="grid grid-2" style="align-items:flex-start;gap:20px">

    <div class="card">
      <div class="card-header"><div class="card-title">Details</div></div>
      <div style="padding:20px">

        <div class="form-group">
          <label class="form-label">Title</label>
          <input type="text" name="title" class="form-control" required value="{{ old('title', $internship->title) }}" placeholder="e.g. Summer Software Internship 2026">
        </div>

        <div class="form-group">
          <label class="form-label">Description</label>
          <textarea name="description" rows="5" class="form-control">{{ old('description', $internship->description) }}</textarea>
        </div>

        <div class="form-row">
          <div class="form-group">
            <label class="form-label"><i class="fas fa-money-bill-wave" style="margin-right:4px;opacity:.6"></i>Challan / Fee Amount (Rs.)</label>
            <input type="number" step="0.01" min="0" name="fee_amount" class="form-control" required value="{{ old('fee_amount', $internship->fee_amount) }}" placeholder="0.00">
          </div>
          <div class="form-group">
            <label class="form-label"><i class="fas fa-calendar-day" style="margin-right:4px;opacity:.6"></i>Application / Challan Due Date</label>
            <input type="date" name="due_date" class="form-control" value="{{ old('due_date', optional($internship->due_date)->format('Y-m-d')) }}">
          </div>
        </div>

        <div class="form-group">
          <label class="form-label">Cover Image</label>
          @if($editing && $internship->image_path)
            <div style="margin-bottom:10px">
              <img src="{{ storage_asset($internship->image_path) }}" style="height:80px;width:130px;border-radius:8px;object-fit:cover;border:1px solid var(--border)">
            </div>
          @endif
          <input type="file" name="image" accept="image/*" class="form-control">
        </div>

        <label class="switch-row">
          <input type="checkbox" name="is_active" value="1" {{ old('is_active', $internship->is_active ?? true) ? 'checked' : '' }}>
          <span><i class="fas fa-eye" style="margin-right:6px;opacity:.6"></i>Active (visible to students immediately)</span>
        </label>
      </div>
    </div>

    <div class="card">
      <div class="card-header"><div class="card-title">Who Can See &amp; Apply</div></div>
      <div style="padding:20px">

        @php $targetType = old('target_type', $internship->target_type ?? 'all'); @endphp

        <div class="form-group">
          <label class="form-label">Audience</label>
          <div class="choice-group" id="targetType">
            <label class="choice-card {{ $targetType === 'all' ? 'active' : '' }}" data-value="all">
              <input type="radio" name="target_type" value="all" {{ $targetType === 'all' ? 'checked' : '' }}>
              <i class="fas fa-globe"></i>
              <span>All Students</span>
            </label>
            <label class="choice-card {{ $targetType === 'programs' ? 'active' : '' }}" data-value="programs">
              <input type="radio" name="target_type" value="programs" {{ $targetType === 'programs' ? 'checked' : '' }}>
              <i class="fas fa-sitemap"></i>
              <span>Programs</span>
            </label>
            <label class="choice-card {{ $targetType === 'students' ? 'active' : '' }}" data-value="students">
              <input type="radio" name="target_type" value="students" {{ $targetType === 'students' ? 'checked' : '' }}>
              <i class="fas fa-user-check"></i>
              <span>Students</span>
            </label>
          </div>
        </div>

        @php $selectedPrograms = old('target_program_ids', $internship->target_program_ids ?? []); @endphp
        <div id="programsWrap" class="form-group" style="display:{{ $targetType === 'programs' ? 'block' : 'none' }}">
          <label class="form-label">Programs</label>
          <div class="check-list">
            @forelse($programs as $p)
              <label class="check-row">
                <input type="checkbox" name="target_program_ids[]" value="{{ $p->id }}" {{ in_array($p->id, $selectedPrograms) ? 'checked' : '' }}>
                {{ $p->name }}
              </label>
            @empty
              <div class="check-empty"><i class="fas fa-sitemap" style="opacity:.3;display:block;font-size:20px;margin-bottom:6px"></i>No programs found.</div>
            @endforelse
          </div>
        </div>

        @php $selectedStudents = old('target_student_ids', $internship->target_student_ids ?? []); @endphp
        <div id="studentsWrap" class="form-group" style="display:{{ $targetType === 'students' ? 'block' : 'none' }}">
          <label class="form-label">Students</label>
          <div style="position:relative;margin-bottom:8px">
            <i class="fas fa-search" style="position:absolute;left:13px;top:50%;transform:translateY(-50%);color:var(--text-muted);font-size:12px"></i>
            <input type="text" id="studentFilter" class="form-control" placeholder="Search by name or student ID..." style="padding-left:32px" oninput="filterStudents()">
          </div>
          <div class="check-list">
            @forelse($students as $s)
              <label class="student-row check-row" data-search="{{ strtolower($s->name.' '.$s->student_id) }}">
                <input type="checkbox" name="target_student_ids[]" value="{{ $s->id }}" {{ in_array($s->id, $selectedStudents) ? 'checked' : '' }}>
                {{ $s->name }} <span class="muted">({{ $s->student_id }})</span>
              </label>
            @empty
              <div class="check-empty"><i class="fas fa-user-check" style="opacity:.3;display:block;font-size:20px;margin-bottom:6px"></i>No students found.</div>
            @endforelse
          </div>
        </div>

        <div class="text-sm text-muted" style="margin-top:6px">
          <i class="fas fa-circle-info"></i> Only students in the chosen audience will see this internship on their dashboard.
        </div>
      </div>
    </div>
  </div>

  <div style="display:flex;gap:10px;margin-top:20px">
    <button type="submit" class="btn btn-primary"><i class="fas fa-check"></i> {{ $editing ? 'Save Changes' : 'Post Internship' }}</button>
    <a href="{{ route('admin.internships.index') }}" class="btn btn-outline">Cancel</a>
  </div>
</form>

<script>
function onTargetChange(v) {
  document.querySelectorAll('#targetType .choice-card').forEach(c => {
    c.classList.toggle('active', c.dataset.value === v);
  });
  document.getElementById('programsWrap').style.display = v === 'programs' ? 'block' : 'none';
  document.getElementById('studentsWrap').style.display = v === 'students' ? 'block' : 'none';
}
document.querySelectorAll('#targetType input[name="target_type"]').forEach(r => {
  r.addEventListener('change', () => onTargetChange(r.value));
});
function filterStudents() {
  const q = document.getElementById('studentFilter').value.toLowerCase();
  document.querySelectorAll('.student-row').forEach(row => {
    row.style.display = row.dataset.search.includes(q) ? 'flex' : 'none';
  });
}
</script>

@endsection
