@extends('layouts.app')
@section('title', 'Programs / Classes')
@section('page_title', 'Programs / Classes')

@push('styles')
<link rel="preconnect" href="https://fonts.googleapis.com">
<style>
  @import url('https://fonts.googleapis.com/css2?family=Fraunces:opsz,wght@9..144,400;9..144,500;9..144,600&display=swap');

  :root{
    --acx-gold:#c9982b;
    --acx-gold-dark:#9c7419;
    --acx-gold-soft:rgba(201,152,43,.12);
  }

  .pi-hero{
    display:flex; align-items:flex-end; justify-content:space-between; gap:20px;
    margin-bottom:24px; flex-wrap:wrap;
  }
  .pi-eyebrow{
    font-size:11px; font-weight:700; letter-spacing:.14em; text-transform:uppercase;
    color:var(--acx-gold-dark); display:flex; align-items:center; gap:8px; margin-bottom:8px;
  }
  .pi-eyebrow::before{ content:""; width:22px; height:1.5px; background:var(--acx-gold); display:inline-block; }
  .pi-heading{ font-family:'Fraunces', serif; font-weight:600; font-size:30px; color:var(--text); letter-spacing:-.01em; }
  .pi-sub{ color:var(--text-muted); font-size:13.5px; margin-top:6px; }
  .pi-sub a{ color:var(--primary); font-weight:600; }

  .pi-stats{
    display:grid; grid-template-columns:repeat(4, 1fr); gap:16px; margin-bottom:26px;
  }
  @media (max-width: 900px){ .pi-stats{ grid-template-columns:repeat(2, 1fr); } }

  .pi-empty{
    text-align:center; padding:70px 20px; border:1.5px dashed var(--border); border-radius:16px;
    background:var(--card);
  }
  .pi-empty i{ font-size:32px; color:var(--acx-gold-dark); margin-bottom:14px; }
  .pi-empty h3{ font-family:'Fraunces', serif; font-size:19px; margin-bottom:6px; }
  .pi-empty p{ color:var(--text-muted); font-size:13.5px; margin-bottom:18px; }

  .pi-grid{ display:grid; grid-template-columns:repeat(auto-fill, minmax(290px, 1fr)); gap:20px; }

  .pi-card{
    background:var(--card); border:1px solid var(--border); border-radius:16px; overflow:hidden;
    box-shadow:var(--shadow); transition:.15s; display:flex; flex-direction:column;
  }
  .pi-card:hover{ box-shadow:var(--shadow-lg); transform:translateY(-2px); }

  .pi-card-top{
    background:
      radial-gradient(circle at 90% -20%, rgba(201,152,43,.18), transparent 55%),
      linear-gradient(160deg, var(--primary-dark) 0%, var(--primary) 65%, #7a0b20 100%);
    padding:22px 20px 18px; position:relative; color:#fff;
  }
  .pi-card-top::before{
    content:""; position:absolute; inset:0;
    background-image:repeating-linear-gradient(135deg, rgba(255,255,255,.035) 0 2px, transparent 2px 26px);
    pointer-events:none;
  }
  .pi-card-top-row{ display:flex; align-items:center; gap:12px; position:relative; }
  .pi-seal{
    width:46px; height:46px; border-radius:50%; flex-shrink:0;
    background:radial-gradient(circle at 35% 30%, #ffe8b0, var(--acx-gold) 55%, var(--acx-gold-dark) 100%);
    display:flex; align-items:center; justify-content:center;
    font-family:'Fraunces', serif; font-weight:600; font-size:16px; color:#4a2f05;
    border:2px solid rgba(255,255,255,.5);
  }
  .pi-card-top-name{ font-family:'Fraunces', serif; font-weight:600; font-size:18px; line-height:1.25; }
  .pi-card-top-code{ font-size:11px; color:rgba(255,255,255,.65); font-family:monospace; letter-spacing:.05em; margin-top:1px; }

  .pi-status-dot{
    position:absolute; top:18px; right:18px; width:9px; height:9px; border-radius:50%;
  }
  .pi-status-dot.active{ background:#5fe0a0; box-shadow:0 0 0 3px rgba(95,224,160,.25); }
  .pi-status-dot.inactive{ background:rgba(255,255,255,.35); }

  .pi-chips{ display:flex; gap:6px; margin-top:14px; position:relative; flex-wrap:wrap; }
  .pi-chip{
    padding:3px 10px; border-radius:20px; font-size:10.5px; font-weight:700; letter-spacing:.03em;
    background:rgba(255,255,255,.14); border:1px solid rgba(255,255,255,.22);
  }

  .pi-card-body{ padding:16px 20px 20px; flex:1; display:flex; flex-direction:column; }
  .pi-card-desc{ font-size:12.5px; color:var(--text-muted); line-height:1.55; min-height:36px; margin-bottom:14px; }

  .pi-metrics{ display:grid; grid-template-columns:1fr 1fr; gap:10px; margin-bottom:16px; }
  .pi-metric{
    border:1px solid var(--border); border-radius:10px; padding:10px 12px; text-align:center;
    transition:.15s;
  }
  .pi-metric:hover{ border-color:var(--acx-gold); background:var(--acx-gold-soft); }
  .pi-metric-num{ font-size:19px; font-weight:800; color:var(--text); line-height:1; }
  .pi-metric-label{ font-size:10.5px; color:var(--text-muted); text-transform:uppercase; letter-spacing:.04em; margin-top:4px; }

  .pi-card-actions{ display:flex; gap:8px; margin-top:auto; }
  .pi-card-actions .btn{ flex:1; justify-content:center; }
  .pi-card-actions form{ flex:0; }
</style>
@endpush

@section('content')
<div class="pi-hero">
  <div>
    <div class="pi-eyebrow">Academic Structure</div>
    <div class="pi-heading">Programs / Classes</div>
    <div class="pi-sub">The top level of your academic tree. <a href="{{ route('classes.index') }}">Manage Sections &amp; Semesters &rarr;</a></div>
  </div>
  <a href="{{ route('programs.create') }}" class="btn btn-primary"><i class="fas fa-add"></i> Add Program / Class</a>
</div>

@if(session('error'))<div class="alert alert-danger" style="margin-bottom:16px">{{ session('error') }}</div>@endif
@if(session('success'))<div class="alert alert-success" style="margin-bottom:16px">{{ session('success') }}</div>@endif

<div class="pi-stats">
  <div class="stat-card">
    <div class="stat-icon red"><i class="fas fa-sitemap"></i></div>
    <div><div class="stat-value">{{ $stats['total'] }}</div><div class="stat-label">Total Programs</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon green"><i class="fas fa-circle-check"></i></div>
    <div><div class="stat-value">{{ $stats['active'] }}</div><div class="stat-label">Active</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon blue"><i class="fas fa-layer-group"></i></div>
    <div><div class="stat-value">{{ $stats['sections'] }}</div><div class="stat-label">Sections / Semesters</div></div>
  </div>
  <div class="stat-card">
    <div class="stat-icon yellow"><i class="fas fa-user-graduate"></i></div>
    <div><div class="stat-value">{{ $stats['students'] }}</div><div class="stat-label">Total Students</div></div>
  </div>
</div>

@if($items->isEmpty())
  <div class="pi-empty">
    <i class="fas fa-sitemap"></i>
    <h3>No programs yet</h3>
    <p>Create your first Program or Class — e.g. "BSCS" or "Grade 10" — then add Sections underneath it.</p>
    <a href="{{ route('programs.create') }}" class="btn btn-primary"><i class="fas fa-add"></i> Add Program / Class</a>
  </div>
@else
  <div class="pi-grid">
    @foreach($items as $item)
    <div class="pi-card">
      <div class="pi-card-top">
        <span class="pi-status-dot {{ $item->is_active ? 'active' : 'inactive' }}" title="{{ $item->is_active ? 'Active' : 'Inactive' }}"></span>
        <div class="pi-card-top-row">
          <div class="pi-seal">{{ strtoupper(\Illuminate\Support\Str::of($item->name)->explode(' ')->map(fn($w)=>$w[0] ?? '')->take(2)->implode('')) }}</div>
          <div>
            <div class="pi-card-top-name">{{ $item->name }}</div>
            @if($item->code)<div class="pi-card-top-code">{{ $item->code }}</div>@endif
          </div>
        </div>
        <div class="pi-chips">
          <span class="pi-chip">{{ ucfirst($item->level) }}</span>
          @if($item->duration_terms)<span class="pi-chip">{{ $item->duration_terms }} terms</span>@endif
        </div>
      </div>

      <div class="pi-card-body">
        <div class="pi-card-desc">{{ $item->description ?: 'No description added.' }}</div>

        <div class="pi-metrics">
          <a href="{{ route('programs.show', $item) }}" class="pi-metric" style="text-decoration:none">
            <div class="pi-metric-num">{{ $item->sections_count }}</div>
            <div class="pi-metric-label">Sections</div>
          </a>
          <a href="{{ route('students.index', ['program' => $item->id]) }}" class="pi-metric" style="text-decoration:none">
            <div class="pi-metric-num">{{ $item->students_count }}</div>
            <div class="pi-metric-label">Students</div>
          </a>
        </div>

        <div class="pi-card-actions">
          <a href="{{ route('classes.create', ['program_id' => $item->id]) }}" class="btn btn-outline btn-sm" title="Add Section / Semester"><i class="fas fa-layer-group"></i></a>
          <a href="{{ route('programs.edit', $item) }}" class="btn btn-outline btn-sm" title="Edit"><i class="fas fa-edit"></i></a>
          <form action="{{ route('programs.destroy', $item) }}" method="POST" onsubmit="return confirm('Delete this Program/Class?')">
            @csrf @method('DELETE')
            <button class="btn btn-outline btn-sm" style="color:var(--danger)" title="Delete"><i class="fas fa-trash"></i></button>
          </form>
        </div>
      </div>
    </div>
    @endforeach
  </div>
  <div style="margin-top:24px">{{ $items->links() }}</div>
@endif
@endsection
