@extends('layouts.app')
@section('title','Messages')
@section('content')

<div class="welcome-banner" style="margin-bottom:24px">
  <div style="position:relative;z-index:1">
    <h1 style="font-size:22px;font-weight:800;margin-bottom:6px"><i class="fas fa-comments"></i> Messages with Admin</h1>
  </div>
</div>

<div class="card" style="margin-bottom:16px">
  <div class="card-body" id="chatThread" style="padding:20px;max-height:520px;overflow-y:auto;display:flex;flex-direction:column;gap:12px">
    @forelse($thread as $m)
      @php $fromAdmin = $m->isFromAdmin(); @endphp
      <div style="display:flex;{{ $fromAdmin ? 'justify-content:flex-start' : 'justify-content:flex-end' }}">
        <div style="max-width:70%;padding:10px 14px;border-radius:12px;background:{{ $fromAdmin ? 'var(--bg,#f4f4f5)' : 'var(--primary)' }};color:{{ $fromAdmin ? 'inherit' : '#fff' }}">
          <div style="font-size:13.5px;white-space:pre-wrap">{{ $m->message }}</div>
          <div style="font-size:10.5px;opacity:.7;margin-top:4px">{{ $fromAdmin ? 'Admin' : 'You' }} &middot; {{ $m->created_at->format('d M, g:i A') }}</div>
        </div>
      </div>
    @empty
      <div class="empty-state"><i class="fas fa-comments"></i><p>No messages yet. Admin will reach out here, or say hello below.</p></div>
    @endforelse
  </div>
</div>

<form method="POST" action="{{ route('teacher.messages.store') }}" style="display:flex;gap:10px">
  @csrf
  <textarea name="message" rows="2" required placeholder="Type a message to Admin..." style="flex:1;padding:12px 14px;border:1px solid var(--border);border-radius:10px;resize:vertical"></textarea>
  <button type="submit" class="btn btn-primary" style="align-self:flex-end"><i class="fas fa-paper-plane"></i> Send</button>
</form>

@push('scripts')
<script>
  const chatThread = document.getElementById('chatThread');
  if (chatThread) chatThread.scrollTop = chatThread.scrollHeight;
</script>
@endpush
@endsection
