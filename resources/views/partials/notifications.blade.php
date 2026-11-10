<div style="position:fixed;top:64px;right:24px;z-index:600">
  <div id="notifPanel" style="background:var(--surface);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow-lg);width:340px;max-width:calc(100vw - 32px);display:none;flex-direction:column;overflow:hidden">
    <div style="padding:16px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between">
      <div style="font-weight:700;font-size:15px">Notifications</div>
      <span class="badge primary">{{ \App\Models\Notification::where('read', false)->count() }} new</span>
    </div>
    <div style="overflow-y:auto;max-height:360px">
      @forelse(\App\Models\Notification::latest()->take(8)->get() as $notif)
      <div style="padding:14px 18px;border-bottom:1px solid var(--border);display:flex;gap:12px;align-items:flex-start;{{ !$notif->read ? 'background:var(--primary-light)' : '' }}">
        <div style="width:36px;height:36px;border-radius:50%;background:var(--{{ $notif->color ?? 'primary' }}-light);color:var(--{{ $notif->color ?? 'primary' }});display:flex;align-items:center;justify-content:center;font-size:14px;flex-shrink:0">
          <i class="{{ $notif->icon ?? 'fas fa-bell' }}"></i>
        </div>
        <div style="flex:1">
          <div style="font-size:13px;font-weight:500">{{ $notif->message }}</div>
          <div style="font-size:11px;color:var(--text-muted);margin-top:3px">{{ $notif->created_at->diffForHumans() }}</div>
        </div>
      </div>
      @empty
      <div style="padding:24px;text-align:center;color:var(--text-muted)">No notifications</div>
      @endforelse
    </div>
    <div style="padding:12px 18px;text-align:center">
      <a href="{{ route('admin.communication.index') }}" style="color:var(--primary);font-size:13px;font-weight:600;text-decoration:none">View all notifications</a>
    </div>
  </div>
</div>

<style>
#notifPanel.open { display: flex !important; }
</style>
