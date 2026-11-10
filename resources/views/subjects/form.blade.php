@extends('layouts.app')
@section('title', isset($subject) ? 'Edit Subject' : 'Add Subject')
@section('page_title', isset($subject) ? 'Edit Subject' : 'Add Subject')
@section('content')

<style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');
.sjf-scope * { box-sizing:border-box; }
.sjf-scope { font-family:'Outfit', sans-serif; }
.sjf-scope i.fas, .sjf-scope i.far, .sjf-scope i.fab { font-family:'Font Awesome 6 Free', 'Font Awesome 6 Brands'; }
.sjf-scope {
  --crimson:#890c25; --crimson-soft:rgba(137,12,37,.08); --border:rgba(137,12,37,.12);
  --surface:#fff; --text:#1a0608; --muted:#8a5860; --radius:16px; --shadow:0 2px 16px rgba(137,12,37,.07);
}
.sjf-back { display:inline-flex; align-items:center; gap:8px; padding:9px 16px; border-radius:9px; border:1.5px solid var(--border);
  background:var(--surface); color:var(--muted); font-size:13px; font-weight:600; text-decoration:none; margin-bottom:20px; transition:.15s; }
.sjf-back:hover { border-color:var(--crimson); color:var(--crimson); }
.sjf-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); box-shadow:var(--shadow); max-width:640px; overflow:hidden; }
.sjf-head { padding:22px 26px; background:linear-gradient(135deg,var(--crimson) 0%,#a81030 100%); color:#fff; }
.sjf-head h2 { font-size:18px; font-weight:800; }
.sjf-head p { font-size:12.5px; opacity:.85; margin-top:4px; }
.sjf-body { padding:26px; }
.sjf-group { margin-bottom:18px; }
.sjf-label { display:block; font-size:12px; font-weight:700; letter-spacing:.05em; text-transform:uppercase; color:var(--muted); margin-bottom:7px; }
.sjf-control { width:100%; padding:11px 14px; border-radius:10px; border:1.5px solid var(--border); background:var(--surface); color:var(--text); font-size:13.5px; transition:border-color .15s, box-shadow .15s; }
.sjf-control:focus { outline:none; border-color:var(--crimson); box-shadow:0 0 0 3px rgba(137,12,37,.10); }
.sjf-row { display:grid; grid-template-columns:1fr 1fr; gap:14px; }
.sjf-foot { display:flex; gap:12px; justify-content:flex-end; margin-top:8px; }
.sjf-btn-outline, .sjf-btn-primary { display:inline-flex; align-items:center; gap:8px; padding:10px 20px; border-radius:10px; font-size:13.5px; font-weight:700; cursor:pointer; text-decoration:none; border:1.5px solid transparent; transition:.15s; }
.sjf-btn-outline { background:transparent; border-color:var(--border); color:var(--muted); }
.sjf-btn-outline:hover { border-color:var(--crimson); color:var(--crimson); }
.sjf-btn-primary { background:var(--crimson); color:#fff; box-shadow:0 4px 14px rgba(137,12,37,.30); }
.sjf-btn-primary:hover { background:#a81030; }
</style>

<div class="sjf-scope">
  <a href="{{ route('subjects.index') }}" class="sjf-back"><i class="fas fa-arrow-left"></i> Back to Curriculum</a>

  <div class="sjf-card">
    <div class="sjf-head">
      <h2>{{ isset($subject) ? 'Edit Subject' : 'New Subject' }}</h2>
      <p>{{ isset($subject) ? 'Update the details for this subject.' : 'Add a subject to your catalog — you can assign it to classes and teachers afterward.' }}</p>
    </div>
    <div class="sjf-body">
      <form action="{{ isset($subject) ? route('subjects.update', $subject) : route('subjects.store') }}" method="POST">
        @csrf
        @if(isset($subject)) @method('PUT') @endif

        <div class="sjf-group">
          <label class="sjf-label">Subject Name *</label>
          <input type="text" name="name" class="sjf-control" required placeholder="e.g. Computer Science"
                 value="{{ old('name', $subject->name ?? '') }}">
        </div>

        <div class="sjf-row">
          <div class="sjf-group">
            <label class="sjf-label">Code</label>
            <input type="text" name="code" class="sjf-control" placeholder="e.g. CS101" value="{{ old('code', $subject->code ?? '') }}">
          </div>
          <div class="sjf-group">
            <label class="sjf-label">Credit Hours</label>
            <input type="number" name="credit_hours" class="sjf-control" min="1" value="{{ old('credit_hours', $subject->credit_hours ?? 1) }}">
          </div>
        </div>

        <div class="sjf-group">
          <label class="sjf-label">Description</label>
          <textarea name="description" class="sjf-control" rows="3" placeholder="Brief description of this subject...">{{ old('description', $subject->description ?? '') }}</textarea>
        </div>

        <div class="sjf-foot">
          <a href="{{ route('subjects.index') }}" class="sjf-btn-outline">Cancel</a>
          <button type="submit" class="sjf-btn-primary"><i class="fas fa-save"></i> Save Subject</button>
        </div>
      </form>
    </div>
  </div>
</div>
@endsection
