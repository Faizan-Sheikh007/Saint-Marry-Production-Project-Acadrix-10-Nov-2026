@extends('layouts.app')
@section('title','Chat with '.$teacher->name)
@section('content')

<div style="display:flex;align-items:center;gap:12px;margin-bottom:18px">
  <a href="{{ route('admin.messages.index') }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i></a>
  <div style="width:40px;height:40px;border-radius:50%;background:var(--primary);color:#fff;display:flex;align-items:center;justify-content:center;font-weight:700">{{ strtoupper(substr($teacher->name,0,2)) }}</div>
  <div>
    <div style="font-weight:700;font-size:16px">{{ $teacher->name }}</div>
    <div class="text-sm text-muted">{{ $teacher->designation ?? 'Teacher' }} @if($teacher->subject) &middot; {{ $teacher->subject->name }} @endif</div>
  </div>
</div>

<div class="card" style="margin-bottom:16px">
  <div class="card-body" id="chatThread" style="padding:20px;max-height:520px;overflow-y:auto;display:flex;flex-direction:column;gap:12px">
    @forelse($thread as $m)
      @php $fromAdmin = $m->isFromAdmin(); @endphp
      <div style="display:flex;{{ $fromAdmin ? 'justify-content:flex-end' : 'justify-content:flex-start' }}">
        <div style="max-width:70%;padding:10px 14px;border-radius:12px;background:{{ $fromAdmin ? 'var(--primary)' : 'var(--bg,#f4f4f5)' }};color:{{ $fromAdmin ? '#fff' : 'inherit' }}">
          <div style="font-size:13.5px;white-space:pre-wrap">{{ $m->message }}</div>
          <div style="font-size:10.5px;opacity:.7;margin-top:4px">{{ $fromAdmin ? 'You' : $teacher->name }} &middot; {{ $m->created_at->format('d M, g:i A') }}</div>
        </div>
      </div>
    @empty
      <div class="empty-state"><i class="fas fa-comments"></i><p>No messages yet. Say hello to {{ $teacher->name }}.</p></div>
    @endforelse
  </div>
</div>

<form method="POST" action="{{ route('admin.messages.store', $teacher) }}" style="display:flex;gap:10px">
  @csrf
  <textarea name="message" rows="2" required placeholder="Type a message to {{ $teacher->name }}..." style="flex:1;padding:12px 14px;border:1px solid var(--border);border-radius:10px;resize:vertical"></textarea>
  <button type="submit" class="btn btn-primary" style="align-self:flex-end"><i class="fas fa-paper-plane"></i> Send</button>
</form>

@push('scripts')
<script>
  const chatThread = document.getElementById('chatThread');
  if (chatThread) chatThread.scrollTop = chatThread.scrollHeight;
</script>
@endpush
@endsection
