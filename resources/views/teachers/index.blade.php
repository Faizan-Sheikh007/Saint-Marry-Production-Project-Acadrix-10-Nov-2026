@extends('layouts.app')
@section('title', 'Teachers & Staff')
@section('page_title', 'Teachers & Staff')
@section('content')

<style>
  :root {
    --primary: #890C25;
    --primary-light: #a91d3a;
    --primary-dark: #6b0a1a;
    --success: #10b981;
    --danger: #ef4444;
    --warning: #f59e0b;
    --text: #1f2937;
    --text-muted: #6b7280;
    --border: #e5e7eb;
    --bg: #f9fafb;
  }

  * { box-sizing: border-box; }
  body { background: white; }

  .page-header { display:flex; justify-content:space-between; align-items:center; margin-bottom:28px; flex-wrap:wrap; gap:16px; }
  .header-content h1 { font-size:30px; font-weight:800; color:var(--text); margin:0 0 6px 0; letter-spacing:-0.5px; display:flex; align-items:center; gap:12px; }
  .header-content h1 i { font-size:22px; color:#fff; background:linear-gradient(135deg,var(--primary-light),var(--primary-dark));
                          width:42px; height:42px; border-radius:12px; display:inline-flex; align-items:center; justify-content:center; }
  .header-content p { color:var(--text-muted); font-size:14px; margin:0 0 0 54px; }
  .header-actions { display:flex; gap:12px; flex-wrap:wrap; }

  .btn { padding:10px 16px; border-radius:8px; font-size:14px; font-weight:600; border:none; cursor:pointer;
         display:inline-flex; align-items:center; gap:8px; transition:all .2s; text-decoration:none; }
  .btn-primary { background:var(--primary); color:white; }
  .btn-primary:hover { background:var(--primary-dark); transform:translateY(-2px); box-shadow:0 4px 12px rgba(137,12,37,.3); }
  .btn-outline { background:white; color:var(--text); border:1px solid var(--border); }
  .btn-outline:hover { background:var(--bg); border-color:var(--primary); color:var(--primary); }
  .btn-success { background:#10b981; color:white; border:none; }
  .btn-success:hover { background:#059669; transform:translateY(-2px); box-shadow:0 4px 12px rgba(16,185,129,.3); }
  .btn-sm { padding:8px 12px; font-size:12px; }

  .stats-grid { display:grid; grid-template-columns:repeat(auto-fit,minmax(180px,1fr)); gap:16px; margin-bottom:32px; }
  .stat-card { background:white; border:1px solid var(--border); border-radius:12px; padding:18px 20px;
               display:flex; align-items:center; gap:14px; text-align:left;
               box-shadow:0 1px 3px rgba(0,0,0,.06); transition:all .25s cubic-bezier(.4,0,.2,1); text-decoration:none; color:inherit; }
  .stat-card:hover { border-color:var(--primary); box-shadow:0 10px 24px rgba(137,12,37,.12); transform:translateY(-4px); }
  .stat-icon { width:42px; height:42px; border-radius:11px; display:flex; align-items:center; justify-content:center;
               font-size:16px; flex-shrink:0; background:rgba(137,12,37,.09); color:var(--primary); }
  .stat-number { font-size:24px; font-weight:800; color:var(--primary); margin-bottom:2px; line-height:1; }
  .stat-label { font-size:11.5px; color:var(--text-muted); text-transform:uppercase; letter-spacing:.4px; font-weight:600; }

  /* ── Filters ── */
  .filters-section { background:white; border:1px solid var(--border); border-radius:10px; padding:20px; margin-bottom:24px; box-shadow:0 1px 3px rgba(0,0,0,.1); }
  .filters-form { display:grid; grid-template-columns:1fr 200px auto; gap:12px; align-items:flex-end; }
  @media(max-width:768px) { .filters-form { grid-template-columns:1fr; } }
  .form-group { display:flex; flex-direction:column; gap:6px; }
  .form-label { font-size:13px; font-weight:600; color:var(--text); text-transform:uppercase; letter-spacing:.3px; }
  .form-control { padding:10px 12px; border:1px solid var(--border); border-radius:6px; font-size:14px; font-family:inherit; color:var(--text); background:white; height:42px; }
  .form-control:focus { outline:none; border-color:var(--primary); box-shadow:0 0 0 3px rgba(137,12,37,.1); }
  .filter-btns { display:flex; gap:8px; align-items:center; }

  /* ── Attendance Quick-Access Banner ── */
  .att-banner {
    background: linear-gradient(135deg, #059669 0%, #10b981 100%);
    border-radius: 10px;
    padding: 18px 24px;
    margin-bottom: 28px;
    display: flex;
    align-items: center;
    justify-content: space-between;
    flex-wrap: wrap;
    gap: 14px;
    box-shadow: 0 4px 12px rgba(16,185,129,.25);
  }
  .att-banner-left { display:flex; align-items:center; gap:14px; }
  .att-banner-icon { width:48px; height:48px; background:rgba(255,255,255,.2); border-radius:50%;
                     display:flex; align-items:center; justify-content:center; font-size:20px; color:white; }
  .att-banner-text { color:white; }
  .att-banner-title { font-size:16px; font-weight:700; margin-bottom:2px; }
  .att-banner-sub   { font-size:13px; opacity:.85; }
  .att-banner-btn {
    background: white; color: #059669; border: none; padding: 10px 20px;
    border-radius: 8px; font-weight: 700; font-size: 14px; cursor: pointer;
    display: inline-flex; align-items: center; gap: 8px; text-decoration: none;
    transition: all .2s; white-space: nowrap;
  }
  .att-banner-btn:hover { background: #f0fdf4; transform: translateY(-1px); box-shadow: 0 4px 10px rgba(0,0,0,.15); }

  /* ── Teacher Cards ── */
  .teachers-grid { display:grid; grid-template-columns:repeat(auto-fill,minmax(300px,1fr)); gap:20px; margin-bottom:40px; }
  .teacher-card { background:white; border:1px solid var(--border); border-radius:14px; overflow:hidden;
                  box-shadow:0 1px 3px rgba(0,0,0,.06); transition:all .3s cubic-bezier(.4,0,.2,1); display:flex; flex-direction:column; }
  .teacher-card:hover { border-color:var(--primary); box-shadow:0 14px 28px -8px rgba(137,12,37,.22); transform:translateY(-6px); }

  .teacher-card-header { position:relative; overflow:hidden; background:linear-gradient(135deg,var(--primary-light) 0%,var(--primary) 55%,var(--primary-dark) 100%);
                         padding:24px 20px; display:flex; flex-direction:column; align-items:center; text-align:center; color:white; }
  .teacher-card-header::before { content:""; position:absolute; inset:0; opacity:.13;
                         background-image:radial-gradient(circle,#fff 1.2px,transparent 1.2px); background-size:14px 14px; }
  .teacher-avatar { width:68px; height:68px; border-radius:50%; background:rgba(255,255,255,.25);
                    display:flex; align-items:center; justify-content:center; font-weight:700; font-size:22px;
                    margin-bottom:12px; border:3px solid rgba(255,255,255,.55); overflow:hidden; position:relative; z-index:1;
                    box-shadow:0 6px 14px rgba(0,0,0,.15); }
  .teacher-avatar img { width:100%; height:100%; object-fit:cover; }
  .teacher-name { font-size:17px; font-weight:700; margin-bottom:3px; position:relative; z-index:1; }
  .teacher-subject { font-size:12px; opacity:.9; margin-bottom:8px; position:relative; z-index:1; }
  .teacher-designation { font-size:11px; opacity:.9; background:rgba(255,255,255,.18); border:1px solid rgba(255,255,255,.3);
                          padding:5px 12px; border-radius:20px; display:inline-block; position:relative; z-index:1; font-weight:600; }

  .teacher-card-body { padding:18px; flex-grow:1; display:flex; flex-direction:column; }
  .teacher-metrics { display:grid; grid-template-columns:1fr 1fr 1fr; gap:10px; margin-bottom:14px; padding-bottom:14px; border-bottom:1px solid var(--border); }
  .metric-item { text-align:center; }
  .metric-value { font-size:17px; font-weight:700; color:var(--primary); line-height:1; }
  .metric-label { font-size:10px; color:var(--text-muted); text-transform:uppercase; letter-spacing:.3px; margin-top:5px; font-weight:600; }

  .teacher-info { display:flex; align-items:center; gap:8px; font-size:12px; margin-bottom:12px; color:var(--text-muted); }
  .status-indicator { width:9px; height:9px; border-radius:50%; display:inline-block; flex-shrink:0; }
  .status-indicator.active   { background:var(--success); }
  .status-indicator.inactive { background:var(--danger); }

  .teacher-actions { display:grid; grid-template-columns:1fr 1fr; gap:7px; margin-top:auto; }
  .teacher-actions.three { grid-template-columns:1fr 1fr 1fr; }
  .teacher-actions.four  { grid-template-columns:1fr 1fr 1fr 1fr; }
  .action-btn { padding:9px 8px; border:1px solid var(--border); background:white; border-radius:6px; cursor:pointer;
                font-size:11px; font-weight:600; color:var(--text); transition:all .2s; text-decoration:none;
                display:flex; align-items:center; justify-content:center; gap:4px; position:relative; white-space:nowrap; }
  .action-btn:hover  { background:var(--bg); border-color:var(--primary); color:var(--primary); }
  .action-btn.primary { background:var(--primary); color:white; border-color:var(--primary); }
  .action-btn.primary:hover { background:var(--primary-dark); }
  .action-btn.success { background:#10b981; color:white; border-color:#10b981; }
  .action-btn.success:hover { background:#059669; }

  .reply-badge { position:absolute; top:-7px; right:-7px; background:#ef4444; color:#fff;
                 font-size:10px; font-weight:800; border-radius:20px; padding:1px 6px;
                 min-width:18px; text-align:center; border:2px solid white; }

  /* ── Table ── */
  .card { background:white; border:1px solid var(--border); border-radius:10px; box-shadow:0 1px 3px rgba(0,0,0,.1); overflow:hidden; margin-top:40px; }
  .card-header { padding:16px 20px; border-bottom:1px solid var(--border); background:var(--bg);
                 font-weight:700; font-size:14px; color:var(--text); display:flex; justify-content:space-between; align-items:center; }
  .table-wrapper { overflow-x:auto; }
  table { width:100%; border-collapse:collapse; font-size:13px; }
  thead { background:var(--bg); border-bottom:2px solid var(--border); }
  th { padding:12px 14px; text-align:left; font-weight:700; color:var(--primary); font-size:11px; text-transform:uppercase; letter-spacing:.3px; white-space:nowrap; }
  tbody tr { border-bottom:1px solid var(--border); transition:background .2s; }
  tbody tr:hover { background:var(--bg); }
  td { padding:14px; color:var(--text); vertical-align:middle; }
  .teacher-row-info { display:flex; align-items:center; gap:10px; }
  .table-avatar { width:38px; height:38px; border-radius:50%; background:var(--primary); color:white;
                  display:flex; align-items:center; justify-content:center; font-weight:700; font-size:13px; flex-shrink:0; overflow:hidden; }
  .table-avatar img { width:100%; height:100%; object-fit:cover; }
  .teacher-detail-name  { font-weight:600; color:var(--text); font-size:13px; }
  .teacher-detail-email { font-size:11px; color:var(--text-muted); }

  .badge { display:inline-flex; align-items:center; gap:4px; padding:5px 10px; border-radius:6px; font-size:11px; font-weight:600; }
  .badge.success { background:rgba(16,185,129,.1); color:var(--success); }
  .badge.danger  { background:rgba(239,68,68,.1);  color:var(--danger); }
  .badge.warning { background:rgba(245,158,11,.1); color:var(--warning); }

  .table-actions { display:flex; gap:5px; align-items:center; flex-wrap:wrap; }
  .table-action-btn { width:30px; height:30px; border:1px solid var(--border); background:white; border-radius:6px;
                      cursor:pointer; display:flex; align-items:center; justify-content:center; font-size:12px;
                      color:var(--text-muted); transition:all .2s; text-decoration:none; position:relative; }
  .table-action-btn:hover        { background:var(--bg); border-color:var(--primary); color:var(--primary); }
  .table-action-btn.danger:hover { background:rgba(239,68,68,.1); border-color:var(--danger); color:var(--danger); }
  .table-action-btn.primary-icon { border-color:var(--primary); color:var(--primary); }
  .table-action-btn.primary-icon:hover { background:var(--primary); color:white; }
  .table-action-btn .reply-badge { top:-6px; right:-6px; }

  .empty-state { text-align:center; padding:60px 20px; color:var(--text-muted); }
  .empty-icon    { font-size:48px; margin-bottom:12px; opacity:.3; }
  .empty-message { font-size:14px; margin-bottom:16px; }
  .empty-action  { color:var(--primary); font-weight:600; text-decoration:none; }

  @media(max-width:768px) {
    .teachers-grid { grid-template-columns:1fr; }
    .page-header   { flex-direction:column; }
    .stats-grid    { grid-template-columns:repeat(2,1fr); }
    .teacher-actions { grid-template-columns:1fr 1fr; }
    .att-banner    { flex-direction:column; align-items:flex-start; }
    table { font-size:12px; }
    th, td { padding:10px 8px; }
  }
</style>

<div>

  {{-- ── Page Header ── --}}
  <div class="page-header">
    <div class="header-content">
      <h1><i class="fas fa-chalkboard-user"></i> Teachers & Staff</h1>
      <p>Manage staff profiles, attendance, salary & leave</p>
    </div>
    <div class="header-actions">
      <a href="{{ route('admin.teachers.create') }}" class="btn btn-primary">
        <i class="fas fa-plus"></i> Add Staff
      </a>
    </div>
  </div>

  {{-- ── Stats ── --}}
  <div class="stats-grid">
    <a href="{{ route('admin.teachers.index') }}" class="stat-card">
      <div class="stat-icon"><i class="fas fa-users"></i></div>
      <div>
        <div class="stat-number">{{ $items->total() }}</div>
        <div class="stat-label">Total Staff</div>
      </div>
    </a>
    <a href="{{ route('admin.teachers.index', ['status'=>'active']) }}" class="stat-card">
      <div class="stat-icon" style="background:rgba(16,185,129,.1);color:#10b981"><i class="fas fa-user-check"></i></div>
      <div>
        <div class="stat-number" style="color:#10b981">{{ $items->where('status','active')->count() }}</div>
        <div class="stat-label">Active</div>
      </div>
    </a>
    <a href="{{ route('admin.teachers.index', ['status'=>'inactive']) }}" class="stat-card">
      <div class="stat-icon" style="background:rgba(239,68,68,.1);color:#ef4444"><i class="fas fa-user-xmark"></i></div>
      <div>
        <div class="stat-number" style="color:#ef4444">{{ $items->where('status','inactive')->count() }}</div>
        <div class="stat-label">Inactive</div>
      </div>
    </a>
  </div>

  {{-- ── Filters ── --}}
  <div class="filters-section">
    <form method="GET" action="{{ route('admin.teachers.index') }}" class="filters-form" data-ajax-filter>
      <div class="form-group">
        <label class="form-label">Search</label>
        <input type="text" name="search" class="form-control"
               placeholder="Name, Employee ID, Email..." value="{{ request('search') }}">
      </div>
      <div class="form-group">
        <label class="form-label">Status</label>
        <select name="status" class="form-control">
          <option value="">All Status</option>
          <option value="active"   {{ request('status')=='active'   ? 'selected':'' }}>Active</option>
          <option value="inactive" {{ request('status')=='inactive' ? 'selected':'' }}>Inactive</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">&nbsp;</label>
        <div class="filter-btns">
          <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Filter</button>
          <a href="{{ route('admin.teachers.index') }}" class="btn btn-outline" data-ajax-filter-link>Reset</a>
        </div>
      </div>
    </form>
  </div>

  {{-- ── Teachers Cards Grid ── --}}
  @if($items->count() > 0)
    <div class="teachers-grid">
      @foreach($items as $teacher)
        <div class="teacher-card">

          <div class="teacher-card-header">
            <div class="teacher-avatar">
              @if($teacher->photo)
                <img src="{{ storage_asset($teacher->photo) }}" alt="{{ $teacher->name }}">
              @else
                {{ strtoupper(substr($teacher->name, 0, 2)) }}
              @endif
            </div>
            <div class="teacher-name">{{ $teacher->name }}</div>
            <div class="teacher-subject">{{ $teacher->subject->name ?? 'General' }}</div>
            <div class="teacher-designation">
              {{ $teacher->experience ? $teacher->experience.' yrs exp' : 'Fresher' }}
            </div>
          </div>

          <div class="teacher-card-body">
            <div class="teacher-metrics">
              <div class="metric-item">
                <div class="metric-value">4.8</div>
                <div class="metric-label">Rating</div>
              </div>
              <div class="metric-item">
                <div class="metric-value">3</div>
                <div class="metric-label">Classes</div>
              </div>
            </div>

            <div class="teacher-info">
              <span class="status-indicator {{ $teacher->status }}"></span>
              <span>{{ ucfirst($teacher->status) }}</span>
            </div>

            {{-- Row: Profile + Edit + Delete --}}
            <div class="teacher-actions three" style="margin-bottom:7px">
              <a href="{{ route('admin.teachers.show', $teacher) }}" class="action-btn">
                <i class="fas fa-eye"></i> Profile
              </a>
              <a href="{{ route('admin.teachers.edit', $teacher) }}" class="action-btn primary" title="Edit">
                <i class="fas fa-edit"></i> Edit
              </a>
              <form action="{{ route('admin.teachers.destroy', $teacher) }}" method="POST"
                    data-confirm="Delete {{ $teacher->name }}? This removes their profile and login account permanently — this can't be undone."
                    data-confirm-title="Delete Teacher?" data-confirm-variant="danger" data-confirm-icon="fas fa-trash"
                    data-confirm-ok-text="Delete">
                @csrf @method('DELETE')
                <button type="submit" class="action-btn" style="width:100%;color:var(--danger);border-color:rgba(239,68,68,.35)" title="Delete">
                  <i class="fas fa-trash"></i> Delete
                </button>
              </form>
            </div>

          </div>
        </div>
      @endforeach
    </div>

  @else
    <div class="card">
      <div class="empty-state">
        <div class="empty-icon"><i class="fas fa-chalkboard-user"></i></div>
        <div class="empty-message">No teachers found in your institution.</div>
        <a href="{{ route('admin.teachers.create') }}" class="empty-action">Add your first teacher →</a>
      </div>
    </div>
  @endif

  {{-- ── Staff Table ── --}}
  @if($items->count() > 0)
    <div class="card">
      <div class="card-header">
        All Staff Records
      </div>
      <div class="table-wrapper">
        <table>
          <thead>
            <tr>
              <th>Staff Member</th>
              <th>Employee ID</th>
              <th>Department</th>
              <th>Join Date</th>
              <th>Salary</th>
              <th>Status</th>
              <th>Actions</th>
            </tr>
          </thead>
          <tbody>
            @foreach($items as $teacher)
              <tr>
                <td>
                  <div class="teacher-row-info">
                    <div class="table-avatar">
                      @if($teacher->photo)
                        <img src="{{ storage_asset($teacher->photo) }}" alt="{{ $teacher->name }}">
                      @else
                        {{ strtoupper(substr($teacher->name, 0, 2)) }}
                      @endif
                    </div>
                    <div>
                      <div class="teacher-detail-name">{{ $teacher->name }}</div>
                      <div class="teacher-detail-email">{{ $teacher->email }}</div>
                    </div>
                  </div>
                </td>
                <td style="font-weight:600;color:var(--primary)">{{ $teacher->employee_id }}</td>
                <td>{{ $teacher->subject->name ?? '—' }}</td>
                <td>{{ $teacher->joining_date ? $teacher->joining_date->format('M Y') : '—' }}</td>
                <td>{{ $teacher->salary ? 'Rs '.number_format($teacher->salary) : '—' }}</td>
                <td>
                  <span class="badge {{ $teacher->status==='active' ? 'success' : ($teacher->status==='inactive' ? 'danger' : 'warning') }}">
                    {{ ucfirst($teacher->status) }}
                  </span>
                </td>
                <td>
                  <div class="table-actions">

                    {{-- Profile --}}
                    <a href="{{ route('admin.teachers.show', $teacher) }}"
                       class="table-action-btn" title="View Profile">
                      <i class="fas fa-eye"></i>
                    </a>

                    {{-- Edit --}}
                    <a href="{{ route('admin.teachers.edit', $teacher) }}"
                       class="table-action-btn" title="Edit">
                      <i class="fas fa-edit"></i>
                    </a>

                    {{-- Delete --}}
                    <form action="{{ route('admin.teachers.destroy', $teacher) }}" method="POST"
                          data-confirm="Delete {{ $teacher->name }}? This removes their profile and login account permanently — this can't be undone."
                          data-confirm-title="Delete Teacher?" data-confirm-variant="danger" data-confirm-icon="fas fa-trash"
                          data-confirm-ok-text="Delete">
                      @csrf @method('DELETE')
                      <button type="submit" class="table-action-btn danger" title="Delete">
                        <i class="fas fa-trash"></i>
                      </button>
                    </form>

                  </div>
                </td>
              </tr>
            @endforeach
          </tbody>
        </table>
      </div>

      {{-- Pagination --}}
      @if($items->hasPages())
      <div style="padding:14px 20px;border-top:1px solid var(--border);background:var(--bg)">
        {{ $items->appends(request()->query())->links() }}
      </div>
      @endif

    </div>
  @endif

</div>
@endsection
