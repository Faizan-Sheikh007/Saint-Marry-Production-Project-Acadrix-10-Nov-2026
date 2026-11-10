@extends('layouts.app')
@section('title', isset($academicYear) ? 'Edit Academic Session' : 'Add Academic Session')
@section('page_title', isset($academicYear) ? 'Edit Academic Session' : 'Add Academic Session')

@section('content')

@if($errors->any())
<div class="alert alert-danger" style="margin-bottom:16px">
  <strong>Please fix the following:</strong>
  <ul style="margin:6px 0 0 18px">@foreach($errors->all() as $error)<li>{{ $error }}</li>@endforeach</ul>
</div>
@endif

<form action="{{ isset($academicYear) ? route('academic-years.update', $academicYear) : route('academic-years.store') }}" method="POST">
  @csrf
  @if(isset($academicYear)) @method('PUT') @endif
  <input type="hidden" name="session_links_submitted" value="1">

  <div class="card" style="max-width:900px;margin-bottom:18px">
    <div class="card-header"><div class="card-title"><i class="fas fa-calendar-days" style="color:var(--primary)"></i> Session Details</div></div>
    <div class="card-body">
      <div class="grid grid-2" style="gap:14px">
        <div class="form-group">
          <label class="form-label">Session Name</label>
          <input type="text" name="name" class="form-control" required
                 value="{{ old('name', $academicYear->name ?? now()->year . '-' . (now()->year + 1)) }}"
                 placeholder="e.g. 2026-2027">
        </div>

        <div class="form-group">
          <label class="form-label">Sort Order</label>
          <input type="number" name="sort_order" class="form-control" min="0"
                 value="{{ old('sort_order', $academicYear->sort_order ?? 0) }}">
        </div>
      </div>

      <div class="grid grid-2" style="gap:14px">
        <div class="form-group">
          <label class="form-label">Session Start Date</label>
          <input type="date" name="start_date" class="form-control" required
                 value="{{ old('start_date', isset($academicYear) && $academicYear->start_date ? $academicYear->start_date->format('Y-m-d') : '') }}">
          <small style="color:var(--text-muted)">Printed as the student card join/valid-from date.</small>
        </div>
        <div class="form-group">
          <label class="form-label">Session End Date</label>
          <input type="date" name="end_date" class="form-control" required
                 value="{{ old('end_date', isset($academicYear) && $academicYear->end_date ? $academicYear->end_date->format('Y-m-d') : '') }}">
          <small style="color:var(--text-muted)">Printed as the student card expiry date and used by its QR verification.</small>
        </div>
      </div>

      <div class="grid grid-2" style="gap:14px">
        <div class="form-group">
          <label style="display:flex;align-items:flex-start;gap:8px">
            <input type="checkbox" name="is_default" value="1" style="margin-top:3px" {{ old('is_default', $academicYear->is_default ?? false) ? 'checked' : '' }}>
            <span><strong>Default Session</strong><br><small style="color:var(--text-muted)">Pre-selected on new admissions. Only one session can be default.</small></span>
          </label>
        </div>

        <div class="form-group">
          <label style="display:flex;align-items:flex-start;gap:8px">
            <input type="checkbox" name="is_active" value="1" style="margin-top:3px" {{ old('is_active', $academicYear->is_active ?? true) ? 'checked' : '' }}>
            <span><strong>Active Session</strong><br><small style="color:var(--text-muted)">Available for new admissions and fee setup.</small></span>
          </label>
        </div>
      </div>
    </div>
  </div>

  @php
    $selectedIds = collect(old('session_links_submitted')
      ? old('school_class_ids', [])
      : ($selectedSchoolClassIds ?? []))->map(fn($id) => (int) $id)->all();
  @endphp
  <div class="card" style="max-width:1100px">
    <div class="card-header" style="display:flex;align-items:center;justify-content:space-between;gap:12px;flex-wrap:wrap">
      <div>
        <div class="card-title"><i class="fas fa-diagram-project" style="color:var(--primary);margin-right:7px"></i>Session → Program → Part/Semester</div>
        <div style="font-size:12px;color:var(--text-muted);margin-top:4px">Select exactly which Programs and Parts/Semesters are offered in this session. Admissions and default installment dates will use these links.</div>
      </div>
      <div style="display:flex;gap:7px">
        <button type="button" class="btn btn-outline btn-sm" id="select-all-session-terms"><i class="fas fa-check-double"></i> Select All</button>
        <button type="button" class="btn btn-outline btn-sm" id="clear-all-session-terms">Clear</button>
      </div>
    </div>
    <div class="card-body">
      @if($programs->isEmpty())
        <div style="padding:20px;text-align:center;color:var(--text-muted)">Create Programs first, then return here to link their Parts/Semesters to this session.</div>
      @else
        <div style="display:grid;grid-template-columns:repeat(auto-fit,minmax(300px,1fr));gap:12px">
          @foreach($programs as $program)
            <div class="session-program-card" style="border:1px solid var(--border);border-radius:10px;padding:12px;background:var(--bg)">
              <div style="display:flex;align-items:center;justify-content:space-between;gap:8px;margin-bottom:9px">
                <div>
                  <strong style="font-size:13px">{{ $program->name }}</strong>
                  @if(!$program->is_active)<span class="badge gray" style="margin-left:5px">Inactive</span>@endif
                </div>
                @if($program->sections->isNotEmpty())
                  <label style="font-size:11px;display:flex;align-items:center;gap:5px;white-space:nowrap">
                    <input type="checkbox" class="program-toggle"> All
                  </label>
                @endif
              </div>

              @forelse($program->sections as $section)
                <label style="display:flex;align-items:center;gap:8px;padding:6px 7px;border-top:1px dashed var(--border);font-size:12px">
                  <input type="checkbox" class="session-term-checkbox" name="school_class_ids[]" value="{{ $section->id }}" {{ in_array((int) $section->id, $selectedIds, true) ? 'checked' : '' }}>
                  <span>{{ $section->section_label }}</span>
                </label>
              @empty
                <div style="font-size:11.5px;color:var(--text-muted);padding:7px 0">No Parts/Semesters exist for this Program yet.</div>
              @endforelse
            </div>
          @endforeach
        </div>
      @endif

      <div style="margin-top:12px;padding:10px 12px;border-radius:8px;background:rgba(245,158,11,.08);font-size:11.5px;color:var(--text-muted)">
        <i class="fas fa-circle-info" style="color:#d97706;margin-right:5px"></i>
        Existing students protect their Session / Program / Semester link: a semester cannot be unlinked from a session while students in that session are still assigned to it.
      </div>
    </div>
  </div>

  <div style="margin-top:20px;display:flex;gap:10px">
    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Session &amp; Links</button>
    <a href="{{ route('academic-years.index') }}" class="btn btn-outline">Cancel</a>
  </div>
</form>

@push('scripts')
<script>
(() => {
  const cards = Array.from(document.querySelectorAll('.session-program-card'));
  const allBoxes = () => Array.from(document.querySelectorAll('.session-term-checkbox'));

  function syncCard(card) {
    const boxes = Array.from(card.querySelectorAll('.session-term-checkbox'));
    const toggle = card.querySelector('.program-toggle');
    if (!toggle || !boxes.length) return;
    const checked = boxes.filter(box => box.checked).length;
    toggle.checked = checked === boxes.length;
    toggle.indeterminate = checked > 0 && checked < boxes.length;
  }

  cards.forEach(card => {
    const toggle = card.querySelector('.program-toggle');
    const boxes = Array.from(card.querySelectorAll('.session-term-checkbox'));
    if (toggle) {
      toggle.addEventListener('change', () => {
        boxes.forEach(box => box.checked = toggle.checked);
        syncCard(card);
      });
    }
    boxes.forEach(box => box.addEventListener('change', () => syncCard(card)));
    syncCard(card);
  });

  document.getElementById('select-all-session-terms')?.addEventListener('click', () => {
    allBoxes().forEach(box => box.checked = true);
    cards.forEach(syncCard);
  });
  document.getElementById('clear-all-session-terms')?.addEventListener('click', () => {
    allBoxes().forEach(box => box.checked = false);
    cards.forEach(syncCard);
  });
})();
</script>
@endpush
@endsection
