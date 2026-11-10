@extends('layouts.app')
@section('title','Announcements')
@section('content')

<div class="welcome-banner" style="margin-bottom:24px">
  <div style="position:relative;z-index:1">
    <h1 style="font-size:22px;font-weight:800;margin-bottom:6px"><i class="fas fa-bullhorn"></i> Announcements for Teachers</h1>
    <p style="opacity:.8">Posted announcements show on every targeted teacher's dashboard.</p>
  </div>
</div>

@if(session('success'))
  <div class="alert alert-success" style="margin-bottom:16px">{{ session('success') }}</div>
@endif

<div class="grid grid-2" style="align-items:flex-start;gap:20px">
  <div class="card">
    <div class="card-header"><div class="card-title">New Announcement</div></div>
    <div style="padding:18px 20px">
      <form method="POST" action="{{ route('admin.announcements.store') }}">
        @csrf
        <label class="text-sm text-muted">Title</label>
        <input type="text" name="title" required style="width:100%;padding:10px 12px;margin:6px 0 14px;border:1px solid var(--border);border-radius:8px">

        <label class="text-sm text-muted">Content</label>
        <textarea name="content" rows="5" required style="width:100%;padding:10px 12px;margin:6px 0 14px;border:1px solid var(--border);border-radius:8px;resize:vertical"></textarea>

        <label class="text-sm text-muted">Audience</label>
        <select name="audience" id="audienceSelect" onchange="document.getElementById('teacherSelectWrap').style.display=(this.value==='specific'?'block':'none')" style="width:100%;padding:10px 12px;margin:6px 0 14px;border:1px solid var(--border);border-radius:8px">
          <option value="all">All Teachers</option>
          <option value="specific">Specific Teacher</option>
        </select>

        <div id="teacherSelectWrap" style="display:none;margin-bottom:14px">
          <label class="text-sm text-muted">Teacher</label>
          <select name="teacher_id" style="width:100%;padding:10px 12px;margin-top:6px;border:1px solid var(--border);border-radius:8px">
            @foreach($teachers as $t)<option value="{{ $t->id }}">{{ $t->name }}</option>@endforeach
          </select>
        </div>

        <label style="display:flex;align-items:center;gap:8px;margin-bottom:16px">
          <input type="checkbox" name="pinned" value="1"> Pin to top of teacher dashboard
        </label>

        <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center"><i class="fas fa-paper-plane"></i> Post Announcement</button>
      </form>
    </div>
  </div>

  <div class="card">
    <div class="card-header"><div class="card-title">Posted Announcements</div></div>
    @forelse($announcements as $a)
      <div style="padding:14px 20px;border-bottom:1px solid var(--border)">
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:4px">
          @if($a->pinned)<span class="badge warning"><i class="fas fa-thumbtack"></i></span>@endif
          <div style="font-weight:700;font-size:13.5px">{{ $a->title }}</div>
          <span class="badge {{ $a->audience === 'all' ? 'success' : 'primary' }}" style="margin-left:auto">{{ $a->audience === 'all' ? 'All Teachers' : ($a->teacher->name ?? 'Specific') }}</span>
        </div>
        <div class="text-sm" style="color:var(--text-muted);margin-bottom:8px">{{ $a->content }}</div>
        <div style="display:flex;align-items:center;gap:10px">
          <div class="text-sm text-muted">{{ $a->created_at->diffForHumans() }} &middot; by {{ $a->author->name ?? 'Admin' }}</div>
          <form method="POST" action="{{ route('admin.announcements.destroy', $a) }}" style="margin-left:auto" data-confirm="Delete this announcement?">
            @csrf @method('DELETE')
            <button type="submit" class="btn btn-outline btn-xs"><i class="fas fa-trash"></i></button>
          </form>
        </div>
      </div>
    @empty
      <div class="empty-state"><i class="fas fa-bullhorn"></i><p>No announcements posted yet.</p></div>
    @endforelse
    <div style="padding:14px 20px">{{ $announcements->links() }}</div>
  </div>
</div>
@endsection
