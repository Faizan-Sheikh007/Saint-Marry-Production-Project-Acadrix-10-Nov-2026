@extends('layouts.app')
@section('title', 'Message — ' . $student->name)
@section('page_title', 'Send Message')

@section('content')

<div style="margin-bottom:20px">
  <div style="font-size:22px;font-weight:800">Message {{ $student->name }}</div>
  <div style="color:var(--text-muted);font-size:13px;margin-top:2px">
    Sends an email to the student and/or their parent's email address on file. Logged for reference.
  </div>
</div>

@if(session('success'))
  <div class="alert alert-success" style="margin-bottom:20px"><i class="fas fa-circle-check"></i> {{ session('success') }}</div>
@endif
@if($errors->any())
  <div class="alert alert-danger" style="margin-bottom:20px">
    <i class="fas fa-triangle-exclamation"></i> {{ $errors->first() }}
  </div>
@endif

<form action="{{ route('students.message.send', $student) }}" method="POST">
  @csrf

  <div class="card" style="max-width:640px">
    <div class="card-header"><div class="card-title"><i class="fas fa-envelope" style="color:var(--primary)"></i> Compose Message</div></div>
    <div class="card-body">

      <div class="form-group">
        <label class="form-label">Recipients</label>
        <div style="display:flex;flex-direction:column;gap:8px">
          <label style="display:flex;align-items:center;gap:8px;font-weight:400">
            <input type="checkbox" name="send_student" value="1" {{ $student->email ? 'checked' : 'disabled' }}>
            Student — {{ $student->email ?: 'no email on file' }}
          </label>
          <label style="display:flex;align-items:center;gap:8px;font-weight:400">
            <input type="checkbox" name="send_parent" value="1" {{ $student->parentGuardian?->email ? 'checked' : 'disabled' }}>
            Parent — {{ $student->parentGuardian?->email ?: 'no parent / no email on file' }}
          </label>
        </div>
      </div>

      <div class="form-group">
        <label class="form-label">Subject *</label>
        <input type="text" name="title" class="form-control" required value="{{ old('title') }}" placeholder="e.g. Fee reminder, Attendance notice">
      </div>

      <div class="form-group">
        <label class="form-label">Message *</label>
        <textarea name="body" class="form-control" rows="8" required placeholder="Type your message…">{{ old('body') }}</textarea>
      </div>

    </div>
  </div>

  <div style="margin-top:20px;display:flex;gap:10px">
    <button type="submit" class="btn btn-primary"><i class="fas fa-paper-plane"></i> Send</button>
    <a href="{{ route('students.show', $student) }}" class="btn btn-outline">Cancel</a>
  </div>
</form>

@endsection
