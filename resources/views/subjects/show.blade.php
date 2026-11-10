@extends('layouts.app')
@section('title', $subject->name)
@section('page_title', 'Subject Details')
@section('content')

<style>
@import url('https://fonts.googleapis.com/css2?family=Outfit:wght@300;400;500;600;700;800&display=swap');
.sjs-scope * { box-sizing:border-box; }
.sjs-scope { font-family:'Outfit', sans-serif; }
.sjs-scope i.fas, .sjs-scope i.far, .sjs-scope i.fab { font-family:'Font Awesome 6 Free', 'Font Awesome 6 Brands'; }
.sjs-scope {
  --crimson:#890c25; --crimson-soft:rgba(137,12,37,.08); --border:rgba(137,12,37,.12);
  --surface:#fff; --text:#1a0608; --muted:#8a5860; --radius:16px; --shadow:0 2px 16px rgba(137,12,37,.07);
}
.sjs-top { display:flex; gap:12px; margin-bottom:22px; }
.sjs-btn-outline, .sjs-btn-primary { display:inline-flex; align-items:center; gap:8px; padding:9px 16px; border-radius:9px; font-size:13px; font-weight:700; text-decoration:none; border:1.5px solid transparent; }
.sjs-btn-outline { background:var(--surface); border-color:var(--border); color:var(--muted); }
.sjs-btn-outline:hover { border-color:var(--crimson); color:var(--crimson); }
.sjs-btn-primary { background:var(--crimson); color:#fff; }
.sjs-card { background:var(--surface); border:1px solid var(--border); border-radius:var(--radius); box-shadow:var(--shadow); overflow:hidden; max-width:640px; }
.sjs-head { padding:24px 26px; background:linear-gradient(135deg,var(--crimson) 0%,#a81030 100%); color:#fff; }
.sjs-head h2 { font-size:20px; font-weight:800; }
.sjs-head p { font-size:12.5px; opacity:.85; margin-top:4px; }
.sjs-body { padding:24px 26px; }
.sjs-meta { display:flex; gap:24px; flex-wrap:wrap; margin-bottom:18px; }
.sjs-meta-item .label { font-size:11px; text-transform:uppercase; letter-spacing:.05em; font-weight:700; color:var(--muted); }
.sjs-meta-item .value { font-size:15px; font-weight:700; color:var(--text); margin-top:2px; }
.sjs-desc { font-size:13.5px; color:var(--text); line-height:1.6; border-top:1px solid var(--border); padding-top:16px; }
.sjs-section-title { font-size:14px; font-weight:800; margin:26px 0 12px; display:flex; align-items:center; gap:8px; }
.sjs-section-title i { color:var(--crimson); }
.sjs-row { display:flex; align-items:center; justify-content:space-between; padding:11px 14px; border:1px solid var(--border); border-radius:10px; margin-bottom:8px; }
.sjs-row .cls { font-size:13px; font-weight:700; }
.sjs-teacher-pill { font-size:12px; font-weight:700; padding:4px 10px; border-radius:20px; background:var(--crimson-soft); color:var(--crimson); }
.sjs-teacher-pill.unassigned { background:#fff4e6; color:#b45309; }
.sjs-empty { text-align:center; padding:24px; color:var(--muted); font-size:13px; border:1.5px dashed var(--border); border-radius:10px; }
</style>

<div class="sjs-scope">
  <div class="sjs-top">
    <a href="{{ route('subjects.index') }}" class="sjs-btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
    <a href="{{ route('subjects.edit', $subject) }}" class="sjs-btn-primary"><i class="fas fa-edit"></i> Edit</a>
  </div>

  <div class="sjs-card">
    <div class="sjs-head">
      <h2>{{ $subject->name }}</h2>
      <p>{{ $subject->code ?? 'No code assigned' }}</p>
    </div>
    <div class="sjs-body">
      <div class="sjs-meta">
        <div class="sjs-meta-item">
          <div class="label">Credit Hours</div>
          <div class="value">{{ $subject->credit_hours ?? '—' }}</div>
        </div>
        <div class="sjs-meta-item">
          <div class="label">Taught In</div>
          <div class="value">{{ $subject->classAssignments->count() }} class{{ $subject->classAssignments->count() === 1 ? '' : 'es' }}</div>
        </div>
      </div>
      <div class="sjs-desc">{{ $subject->description ?? 'No description provided.' }}</div>

      <div class="sjs-section-title"><i class="fas fa-sitemap"></i> Classes &amp; Teachers</div>
      @forelse($subject->classAssignments as $assignment)
        <div class="sjs-row">
          <span class="cls">{{ $assignment->schoolClass->display_name ?? ($assignment->schoolClass->name ?? '—') }}</span>
          <span class="sjs-teacher-pill {{ $assignment->teacher ? '' : 'unassigned' }}">
            {{ $assignment->teacher->name ?? 'Unassigned' }}
          </span>
        </div>
      @empty
        <div class="sjs-empty">This subject isn't assigned to any class yet. Go to the Curriculum page to assign it.</div>
      @endforelse
    </div>
  </div>
</div>
@endsection
