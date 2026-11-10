@extends('layouts.app')
@section('title', 'Parts/Semesters')
@section('page_title', 'Parts/Semesters')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<style>
  @import url('https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600;9..144,700&display=swap');

  :root{
    --acx-gold:#c9982b;
    --acx-gold-dark:#9c7419;
    --acx-gold-soft:rgba(201,152,43,.12);
    --acx-navy:#241a3d;
  }

  .si-hero{
    display:flex; align-items:flex-end; justify-content:space-between; gap:20px;
    margin-bottom:24px; flex-wrap:wrap;
  }
  .si-eyebrow{
    font-size:11px; font-weight:700; letter-spacing:.14em; text-transform:uppercase;
    color:var(--acx-gold-dark); display:flex; align-items:center; gap:8px; margin-bottom:8px;
  }
  .si-eyebrow::before{ content:""; width:22px; height:1.5px; background:var(--acx-gold); display:inline-block; }
  .si-heading{ font-family:'Fraunces', serif; font-weight:600; font-size:30px; color:var(--text); letter-spacing:-.01em; }
  .si-sub{ color:var(--text-muted); font-size:13.5px; margin-top:6px; }
  .si-sub a{ color:var(--primary); font-weight:600; }

  .si-stats{ display:grid; grid-template-columns:repeat(4, 1fr); gap:16px; margin-bottom:20px; }
  @media (max-width: 900px){ .si-stats{ grid-template-columns:repeat(2, 1fr); } }

  .si-toolbar{
    background:var(--card); border:1px solid var(--border); border-radius:14px;
    padding:14px 18px; margin-bottom:22px; display:flex; align-items:center; justify-content:space-between; gap:14px; flex-wrap:wrap;
  }
  .si-toolbar label{ font-size:11px; font-weight:700; text-transform:uppercase; letter-spacing:.05em; color:var(--text-muted); margin-right:4px; }
  .si-toolbar select{ min-width:220px; }
  .si-toolbar form{ display:flex; align-items:center; gap:10px; flex-wrap:wrap; }

  .si-level-tabs{ display:flex; gap:6px; background:var(--bg); border-radius:10px; padding:4px; }
  .si-level-tab{
    border:none; background:transparent; padding:7px 14px; border-radius:8px; font-size:12.5px; font-weight:700;
    color:var(--text-muted); cursor:pointer; transition:.15s;
  }
  .si-level-tab.active{ background:var(--card); color:var(--acx-gold-dark); box-shadow:0 1px 3px rgba(0,0,0,.08); }

  /* ── Program group ─────────────────────────────────────────── */
  .prog-group{
    background:var(--card); border:1px solid var(--border); border-radius:18px;
    overflow:hidden; box-shadow:var(--shadow); margin-bottom:22px;
  }
  .prog-head{
    display:flex; align-items:center; gap:16px; padding:20px 22px;
    background:linear-gradient(120deg, var(--acx-navy) 0%, #3a2a63 100%);
    color:#fff; flex-wrap:wrap;
  }
  .prog-avatar{
    width:52px; height:52px; border-radius:14px; flex-shrink:0;
    background:linear-gradient(150deg, var(--acx-gold), var(--acx-gold-dark));
    display:flex; align-items:center; justify-content:center;
    font-family:'Fraunces', serif; font-weight:700; font-size:19px; color:#241a3d;
    box-shadow:0 4px 10px rgba(0,0,0,.25);
  }
  .prog-title-wrap{ flex:1; min-width:200px; }
  .prog-title{ font-family:'Fraunces', serif; font-weight:600; font-size:19px; letter-spacing:-.01em; display:flex; align-items:center; gap:10px; flex-wrap:wrap; }
  .prog-level-pill{
    display:inline-flex; align-items:center; gap:5px; padding:3px 10px; border-radius:20px;
    background:rgba(255,255,255,.14); font-size:10.5px; font-weight:700; text-transform:uppercase; letter-spacing:.04em;
  }
  .prog-meta{ font-size:12px; color:rgba(255,255,255,.65); margin-top:4px; }
  .prog-meta a{ color:var(--acx-gold); font-weight:600; }
  .prog-stats{ display:flex; gap:22px; flex-wrap:wrap; }
  .prog-stat{ text-align:center; }
  .prog-stat-value{ font-size:18px; font-weight:800; font-family:'Fraunces', serif; }
  .prog-stat-label{ font-size:10px; color:rgba(255,255,255,.6); text-transform:uppercase; letter-spacing:.05em; margin-top:2px; }

  .sem-grid{
    display:grid; grid-template-columns:repeat(auto-fill, minmax(240px, 1fr)); gap:14px;
    padding:20px 22px;
  }
  .sem-card{
    background:var(--bg); border:1px solid var(--border); border-radius:14px; padding:16px;
    transition:.15s; position:relative; overflow:hidden;
  }
  .sem-card:hover{ border-color:var(--acx-gold); box-shadow:0 6px 16px rgba(201,152,43,.15); transform:translateY(-2px); }
  .sem-card::before{
    content:""; position:absolute; top:0; left:0; width:4px; height:100%;
    background:linear-gradient(180deg, var(--acx-gold), var(--acx-gold-dark));
  }
  .sem-card-top{ display:flex; align-items:center; gap:10px; margin-bottom:12px; }
  .sem-avatar{
    width:36px; height:36px; border-radius:10px; flex-shrink:0;
    background:linear-gradient(150deg, var(--primary), var(--primary-dark));
    display:flex; align-items:center; justify-content:center;
    font-family:'Fraunces', serif; font-weight:600; font-size:13px; color:#fff;
  }
  .sem-name{ font-weight:700; font-size:14px; }
  .sem-sub{ font-size:11px; color:var(--text-muted); margin-top:1px; }

  .sem-fill-wrap{ display:flex; align-items:center; gap:8px; margin-bottom:12px; }
  .sem-fill-track{ flex:1; height:6px; border-radius:6px; background:var(--border); overflow:hidden; }
  .sem-fill-bar{ height:100%; border-radius:6px; background:linear-gradient(90deg, var(--acx-gold-dark), var(--acx-gold)); }
  .sem-fill-num{ font-size:11px; color:var(--text-muted); white-space:nowrap; font-weight:600; }

  .sem-desc{ font-size:11.5px; color:var(--text-muted); margin-bottom:12px; min-height:0; }
  .sem-actions{ display:flex; gap:6px; }
  .sem-actions .btn{ flex:1; justify-content:center; padding:6px 8px; }

  .si-empty{ text-align:center; padding:60px 20px; color:var(--text-muted); }
  .si-empty i{ font-size:28px; color:var(--acx-gold-dark); margin-bottom:12px; display:block; }
</style>
@endpush

@section('content')
<div class="si-hero">
  <div>
    <div class="si-eyebrow">Academic Structure</div>
    <div class="si-heading">Parts/Semesters</div>
    <div class="si-sub">Grouped by Program &mdash; each one belongs to a Program. <a href="{{ route('programs.index') }}">Manage Programs &rarr;</a></div>
  </div>
  <a href="{{ route('classes.create') }}" class="btn btn-primary"><i class="fas fa-add"></i> Add Part/Semester</a>
</div>

@if(session('error'))<div class="alert alert-danger" style="margin-bottom:16px">{{ session('error') }}</div>@endif
@if(session('success'))<div class="alert alert-success" style="margin-bottom:16px">{{ session('success') }}</div>@endif

<div class="si-stats">
  <div class="stat-card">
    <div class="stat-icon red"><i class="fas fa-layer-group"></i></div>
    <div><div class="stat-value">{{ $stats['total'] }}</div><div class="stat-label">Total Parts/Semesters</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fas fa-sitemap"></i></div>
    <div><div class="stat-value">{{ $stats['programs'] }}</div><div class="stat-label">Programs in Use</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon yellow"><i class="fas fa-user-graduate"></i></div>
    <div><div class="stat-value">{{ $stats['students'] }}</div><div class="stat-label">Total Students</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><i class="fas fa-chair"></i></div>
    <div><div class="stat-value">{{ $stats['capacity'] }}</div><div class="stat-label">Total Seats</div></div>
  </div>
</div>

<div class="si-toolbar">
  <form method="GET" style="display:flex;align-items:center;gap:10px;flex-wrap:wrap" data-ajax-filter>
    <label>Filter by Program</label>
    <select name="program" class="form-control" onchange="this.form.requestSubmit()">
      <option value="">All Programs</option>
      @foreach($programs as $program)
      <option value="{{ $program->id }}" {{ request('program') == $program->id ? 'selected' : '' }}>{{ $program->name }}</option>
      @endforeach
    </select>
    @if(request('program'))
      <a href="{{ route('classes.index') }}" class="btn btn-outline btn-sm" data-ajax-filter-link>Clear</a>
    @endif
  </form>

  <div class="si-level-tabs" id="level-tabs">
    <button type="button" class="si-level-tab active" data-level="all">All</button>
    <button type="button" class="si-level-tab" data-level="college">College</button>
    <button type="button" class="si-level-tab" data-level="university">University</button>
  </div>
</div>

@if($groupedPrograms->isEmpty())
  <div class="prog-group">
    <div class="si-empty">
      <i class="fas fa-layer-group"></i>
      No sections found. <a href="{{ route('classes.create') }}" style="color:var(--primary);font-weight:600">Add one &rarr;</a>
    </div>
  </div>
@else
  @foreach($groupedPrograms as $program)
  <div class="prog-group" data-level="{{ $program->level }}">
    <div class="prog-head">
      <div class="prog-avatar">{{ strtoupper(substr($program->code ?: $program->name, 0, 2)) }}</div>
      <div class="prog-title-wrap">
        <div class="prog-title">
          <a href="{{ route('programs.show', $program) }}" style="color:#fff;text-decoration:none">{{ $program->name }}</a>
          <span class="prog-level-pill"><i class="fas fa-{{ $program->level === 'university' ? 'university' : 'school' }}"></i> {{ ucfirst($program->level) }}</span>
          @if($program->system_type)
            <span class="prog-level-pill"><i class="fas fa-calendar-days"></i> {{ ucfirst($program->system_type) }} System</span>
          @endif
          @if(!$program->is_active)
            <span class="prog-level-pill" style="background:rgba(239,68,68,.25)"><i class="fas fa-pause"></i> Inactive</span>
          @endif
        </div>
        <div class="prog-meta">
          {{ $program->code ? "Code: {$program->code}" : '' }}
          @if($program->institution) &middot; {{ $program->institution->name }} @endif
        </div>
      </div>
      <div class="prog-stats">
        <div class="prog-stat"><div class="prog-stat-value">{{ $program->sections->count() }}</div><div class="prog-stat-label">Parts</div></div>
        <div class="prog-stat"><div class="prog-stat-value">{{ $program->students_total }}</div><div class="prog-stat-label">Students</div></div>
        <div class="prog-stat"><div class="prog-stat-value">{{ $program->seats_total }}</div><div class="prog-stat-label">Seats</div></div>
      </div>
    </div>

    <div class="sem-grid">
      @foreach($program->sections as $item)
      <div class="sem-card">
        <div class="sem-card-top">
          <div class="sem-avatar">{{ strtoupper(substr($item->section ?: $item->name, 0, 2)) }}</div>
          <div>
            <div class="sem-name">{{ $item->name }}</div>
            <div class="sem-sub">{{ $item->section ? "Section {$item->section}" : ucfirst($item->level) }} &middot; {{ $item->students_count }} student{{ $item->students_count == 1 ? '' : 's' }}</div>
          </div>
        </div>

        @php $pct = $item->capacity ? min(100, round(($item->students_count / $item->capacity) * 100)) : 0; @endphp
        <div class="sem-fill-wrap">
          <div class="sem-fill-track"><div class="sem-fill-bar" style="width:{{ $pct }}%"></div></div>
          <span class="sem-fill-num">{{ $item->students_count }}/{{ $item->capacity ?: '—' }}</span>
        </div>

        @if($item->description)
          <div class="sem-desc">{{ \Illuminate\Support\Str::limit($item->description, 60) }}</div>
        @endif

        <div class="sem-actions">
          <a href="{{ route('classes.show', $item) }}" class="btn btn-outline btn-sm" title="View"><i class="fas fa-eye"></i></a>
          <a href="{{ route('classes.edit', $item) }}" class="btn btn-outline btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
          <form action="{{ route('classes.destroy', $item) }}" method="POST" style="flex:1" onsubmit="return confirm('Delete?')">
            @csrf @method('DELETE')
            <button class="btn btn-outline btn-sm" style="width:100%;color:var(--danger)" title="Delete"><i class="fas fa-trash"></i></button>
          </form>
        </div>
      </div>
      @endforeach
    </div>
  </div>
  @endforeach
@endif

@endsection

@push('scripts')
<script>
  document.getElementById('level-tabs')?.addEventListener('click', function (e) {
    var btn = e.target.closest('.si-level-tab');
    if (!btn) return;
    this.querySelectorAll('.si-level-tab').forEach(b => b.classList.remove('active'));
    btn.classList.add('active');
    var level = btn.dataset.level;
    document.querySelectorAll('.prog-group[data-level]').forEach(function (group) {
      group.style.display = (level === 'all' || group.dataset.level === level) ? '' : 'none';
    });
  });
</script>
@endpush
