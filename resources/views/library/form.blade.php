@extends('layouts.app')

@section('title', isset($library) ? 'Edit Book' : 'Add Book')
@section('page_title', isset($library) ? 'Edit Book' : 'Add Book')

@section('content')

<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px">
  <a href="{{ route('admin.library.index') }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Back</a>
  <div style="font-size:20px;font-weight:800">{{ isset($library) ? 'Edit Book' : 'Add New Book' }}</div>
</div>

@if($errors->any())
  <div style="background:#fee2e2;border:1px solid #ef4444;padding:12px 16px;border-radius:8px;margin-bottom:20px">
    <strong style="color:#dc2626">Please fix the following errors:</strong>
    <ul style="margin:8px 0 0 20px;color:#dc2626">
      @foreach($errors->all() as $error)
        <li>{{ $error }}</li>
      @endforeach
    </ul>
  </div>
@endif

<form action="{{ isset($library) ? route('admin.library.update', $library) : route('admin.library.store') }}" method="POST">
  @csrf
  @if(isset($library)) @method('PUT') @endif

  <div class="grid grid-2" style="gap:20px;align-items:start">

    <!-- Book Details -->
    <div class="card">
      <div class="card-header"><div class="card-title"><i class="fas fa-book" style="color:var(--primary)"></i> Book Details</div></div>
      <div class="card-body">
        <div class="form-group">
          <label class="form-label">Book Title *</label>
          <input type="text" name="name" class="form-control" required value="{{ old('name', $library->name ?? '') }}" placeholder="e.g. Introduction to Algorithms">
          @error('name') <div style="color:var(--danger);font-size:12px;margin-top:4px">{{ $message }}</div> @enderror
        </div>

        <div class="form-group">
          <label class="form-label">Author</label>
          <input type="text" name="author" class="form-control" value="{{ old('author', $library->author ?? '') }}" placeholder="e.g. Thomas H. Cormen">
        </div>

        <div class="grid grid-2" style="gap:16px">
          <div class="form-group">
            <label class="form-label">ISBN</label>
            <input type="text" name="isbn" class="form-control" value="{{ old('isbn', $library->isbn ?? '') }}" placeholder="978-0-XX-XXXXXX-X">
            @error('isbn') <div style="color:var(--danger);font-size:12px;margin-top:4px">{{ $message }}</div> @enderror
          </div>
          <div class="form-group">
            <label class="form-label">Category</label>
            <input type="text" name="category" class="form-control" list="categoryList" value="{{ old('category', $library->category ?? '') }}" placeholder="e.g. Computer Science">
            <datalist id="categoryList">
              @foreach($categories ?? [] as $cat)
                <option value="{{ $cat }}">
              @endforeach
            </datalist>
          </div>
        </div>

        <div class="grid grid-2" style="gap:16px">
          <div class="form-group">
            <label class="form-label">Rack / Location</label>
            <input type="text" name="rack" class="form-control" value="{{ old('rack', $library->rack ?? '') }}" placeholder="e.g. Rack B-12">
          </div>
          <div class="form-group">
            <label class="form-label">No. of Copies *</label>
            <input type="number" name="copies" class="form-control" required value="{{ old('copies', $library->copies ?? 1) }}" min="1">
            @error('copies') <div style="color:var(--danger);font-size:12px;margin-top:4px">{{ $message }}</div> @enderror
            @if(isset($library))
              <div style="font-size:11.5px;color:var(--text-muted);margin-top:4px">Currently {{ $library->available_copies }} of {{ $library->copies }} available. Changing this adjusts availability by the same amount.</div>
            @endif
          </div>
        </div>
      </div>
    </div>

    <!-- Target Audience -->
    <div class="card">
      <div class="card-header"><div class="card-title"><i class="fas fa-graduation-cap" style="color:var(--primary)"></i> Target Audience</div></div>
      <div class="card-body">
        <p style="font-size:12.5px;color:var(--text-muted);margin:0 0 16px">Optionally tag this book to a program, class, or industry so students and staff can find it faster. Leave blank if it's a general-collection book.</p>

        <div class="form-group">
          <label class="form-label">Program</label>
          <select name="program_id" id="program_id" class="form-control">
            <option value="">All Programs</option>
            @foreach($programs as $program)
              <option value="{{ $program->id }}" {{ old('program_id', $library->program_id ?? '') == $program->id ? 'selected' : '' }}>{{ $program->name }}</option>
            @endforeach
          </select>
        </div>

        <div class="form-group">
          <label class="form-label">Class / Semester</label>
          <select name="school_class_id" id="school_class_id" class="form-control">
            <option value="">All Classes</option>
            @foreach($classes as $class)
              <option value="{{ $class->id }}" data-program="{{ $class->program_id }}"
                {{ old('school_class_id', $library->school_class_id ?? '') == $class->id ? 'selected' : '' }}>
                {{ $class->display_name }}
              </option>
            @endforeach
          </select>
        </div>

        <div class="form-group" style="margin-bottom:0">
          <label class="form-label">Industry</label>
          <input type="text" name="industry" class="form-control" value="{{ old('industry', $library->industry ?? '') }}" placeholder="e.g. Software / IT, Healthcare, Business">
        </div>
      </div>
    </div>

  </div>

  <!-- Submit -->
  <div style="display:flex;gap:12px;justify-content:flex-end;margin-top:20px">
    <a href="{{ route('admin.library.index') }}" class="btn btn-outline">Cancel</a>
    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> {{ isset($library) ? 'Update Book' : 'Save Book' }}</button>
  </div>
</form>

@push('scripts')
<script>
(function () {
  const programSelect = document.getElementById('program_id');
  const classSelect    = document.getElementById('school_class_id');
  const allClassOptions = Array.from(classSelect.options);

  function filterClasses() {
    const pid = programSelect.value;
    classSelect.innerHTML = '';
    allClassOptions.forEach(opt => {
      if (opt.value === '' || !pid || opt.dataset.program === pid) {
        classSelect.appendChild(opt.cloneNode(true));
      }
    });
  }

  programSelect.addEventListener('change', filterClasses);
  if (programSelect.value) filterClasses();
})();
</script>
@endpush

@endsection
