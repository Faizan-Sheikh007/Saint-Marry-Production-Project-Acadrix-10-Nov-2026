@extends('layouts.app')
@section('title', 'Internships')

@push('styles')
<style>
.modal-backdrop{display:none;position:fixed;inset:0;background:rgba(15,5,7,.45);backdrop-filter:blur(3px);z-index:1000;align-items:center;justify-content:center}
.modal-backdrop.open{display:flex}
.modal-box{background:var(--surface,#fff);border-radius:16px;width:100%;max-width:520px;margin:20px;box-shadow:0 24px 60px rgba(139,12,34,.20);animation:modalIn .25s cubic-bezier(.34,1.56,.64,1);overflow:hidden;max-height:88vh;display:flex;flex-direction:column}
@keyframes modalIn{from{opacity:0;transform:scale(.94) translateY(10px)}to{opacity:1;transform:scale(1) translateY(0)}}
.modal-header{display:flex;align-items:center;justify-content:space-between;padding:20px 24px 16px;border-bottom:1px solid var(--border)}
.modal-title{font-size:17px;font-weight:800}
.modal-close{width:32px;height:32px;border-radius:8px;border:none;background:rgba(139,12,34,.08);color:var(--text-muted);display:flex;align-items:center;justify-content:center;font-size:16px;cursor:pointer}
.modal-close:hover{background:var(--primary);color:#fff}
.modal-body{padding:20px 24px;overflow-y:auto}
.modal-footer{padding:16px 24px 20px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:10px}

.internship-card{background:var(--card,#fff);border:1px solid var(--border);border-radius:14px;overflow:hidden;display:flex;flex-direction:column;transition:transform .15s,box-shadow .15s}
.internship-card:hover{transform:translateY(-3px);box-shadow:0 12px 28px rgba(139,12,34,.12)}
.internship-cover{height:150px;background:linear-gradient(135deg,rgba(139,12,34,.16),rgba(139,12,34,.03));display:flex;align-items:center;justify-content:center}
.internship-cover img{width:100%;height:100%;object-fit:cover}
.student-basic-data{background:rgba(139,12,34,.05);border-radius:10px;padding:12px 14px;font-size:13px;display:grid;grid-template-columns:1fr 1fr;gap:8px 16px;margin-top:10px}
.student-basic-data div span{display:block;color:var(--text-muted);font-size:11px;text-transform:uppercase;letter-spacing:.04em}

/* Confetti burst canvas overlay */
#confettiLayer{position:fixed;inset:0;pointer-events:none;z-index:2000}
</style>
@endpush

@section('content')

<div class="welcome-banner" style="margin-bottom:24px">
  <div style="position:relative;z-index:1">
    <h1 style="font-size:22px;font-weight:800;margin-bottom:6px"><i class="fas fa-briefcase"></i> Internships</h1>
    <p style="opacity:.8">Opportunities posted for you by administration.</p>
  </div>
</div>

@if(session('success'))<div class="alert alert-success" style="margin-bottom:16px">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger" style="margin-bottom:16px">{{ session('error') }}</div>@endif

@if(!$student)
  <div class="card"><div class="empty-state"><i class="fas fa-triangle-exclamation"></i><p>No student profile is linked to this account.</p></div></div>
@else

@if($internships->isEmpty() && $myApplications->isEmpty())
  <div class="card"><div class="empty-state">
    <i class="fas fa-briefcase"></i>
    <p>No internships have been posted for you yet. Check back soon!</p>
  </div></div>
@endif

@if($internships->isNotEmpty())
<div class="grid grid-3" style="gap:20px;margin-bottom:28px">
  @foreach($internships as $i)
    @php
      $myApp = $myApplications->get($i->id);
      $isConfirmed = $myApp && $myApp->status === 'admin_approved' && $myApp->fee_status === 'paid';
    @endphp
    <div class="internship-card {{ $isConfirmed ? 'is-confirmed' : '' }}" @if($isConfirmed) data-celebrate="1" @endif>
      @if($isConfirmed)
        <div class="confirmed-strip"><i class="fas fa-champagne-glasses"></i> Confirmed — you're all set!</div>
      @endif
      <div class="internship-cover">
        @if($i->image_path)
          <img src="{{ storage_asset($i->image_path) }}" alt="{{ $i->title }}">
        @else
          <i class="fas fa-briefcase" style="font-size:34px;color:var(--primary);opacity:.35"></i>
        @endif
      </div>
      <div style="padding:16px 18px;flex:1;display:flex;flex-direction:column">
        <div style="font-weight:800;font-size:15.5px;margin-bottom:4px">{{ $i->title }}</div>
        <div class="text-sm text-muted" style="flex:1;margin-bottom:10px">{{ \Illuminate\Support\Str::limit($i->description, 80) ?: 'No description provided.' }}</div>
        <div style="font-size:12.5px;color:var(--text-muted);margin-bottom:12px">
          <i class="fas fa-money-bill-wave"></i> Fee: Rs. {{ number_format($i->fee_amount, 0) }}
          @if($i->due_date) &nbsp;&middot;&nbsp; <i class="fas fa-calendar"></i> Due {{ $i->due_date->format('d M Y') }} @endif
        </div>

        @if($myApp)
          @if($isConfirmed)
            <div class="congrats-banner"><i class="fas fa-champagne-glasses"></i> Congratulations, {{ $student->name }}! Your seat is confirmed and your challan is paid.</div>
            <span class="confetti-badge" style="margin-bottom:10px;align-self:flex-start"><i class="fas fa-circle-check"></i> {{ $myApp->statusLabel() }}</span>
          @else
            <span class="badge {{ $myApp->statusColor() }}" style="margin-bottom:10px;align-self:flex-start">{{ $myApp->statusLabel() }}</span>
          @endif
          @if($myApp->status === 'admin_approved' && $myApp->fee_status !== 'paid' && $myApp->voucher)
            <a href="{{ route('student.voucher', $myApp->voucher) }}" class="btn btn-primary" style="justify-content:center"><i class="fas fa-file-invoice-dollar"></i> Pay Challan {{ $myApp->voucher->voucher_no }}</a>
          @endif
          @if($isConfirmed && $myApp->voucher)
            <a href="{{ route('student.voucher', $myApp->voucher) }}" class="btn btn-outline" style="justify-content:center;border-color:#10b981;color:#059669"><i class="fas fa-receipt"></i> View Paid Challan</a>
          @endif
        @else
          <button type="button" class="btn btn-primary" style="justify-content:center" onclick="openInternship({{ $i->id }})"><i class="fas fa-circle-info"></i> View &amp; Apply</button>
        @endif
      </div>
    </div>

    {{-- Detail + apply modal for this posting --}}
    <div id="internshipModal{{ $i->id }}" class="modal-backdrop">
      <div class="modal-box">
        <div class="modal-header">
          <span class="modal-title">{{ $i->title }}</span>
          <button class="modal-close" onclick="closeModal('internshipModal{{ $i->id }}')">&times;</button>
        </div>
        <div class="modal-body">
          @if($i->image_path)<img src="{{ storage_asset($i->image_path) }}" style="width:100%;border-radius:10px;max-height:160px;object-fit:cover;margin-bottom:14px">@endif
          <p style="margin-bottom:14px;white-space:pre-line">{{ $i->description ?: 'No description provided.' }}</p>
          <div style="display:flex;gap:18px;font-size:13px;color:var(--text-muted);margin-bottom:16px">
            <span><i class="fas fa-money-bill-wave"></i> Challan Fee: <strong style="color:var(--text)">Rs. {{ number_format($i->fee_amount, 0) }}</strong></span>
            @if($i->due_date)<span><i class="fas fa-calendar"></i> Due: <strong style="color:var(--text)">{{ $i->due_date->format('d M Y') }}</strong></span>@endif
          </div>

          <form method="POST" action="{{ route('student.internships.apply', $i) }}">
            @csrf
            <div class="form-group">
              <label class="form-label">Roll Number / Student ID</label>
              <input type="text" name="roll_number" class="form-control" required value="{{ $student->roll_number ?: $student->student_id }}">
            </div>

            <div class="student-basic-data">
              <div><span>Name</span>{{ $student->name }}</div>
              <div><span>Student ID</span>{{ $student->student_id }}</div>
              <div><span>Program</span>{{ $student->program->name ?? '—' }}</div>
              <div><span>Class / Section</span>{{ $student->schoolClass->display_name ?? ($student->schoolClass->name ?? '—') }}</div>
            </div>

            <button type="submit" class="btn btn-primary" style="width:100%;justify-content:center;margin-top:16px"><i class="fas fa-paper-plane"></i> Apply for Internship</button>
          </form>
        </div>
      </div>
    </div>
  @endforeach
</div>
@endif

@if($myApplications->isNotEmpty())
<div class="card">
  <div class="card-header"><div class="card-title">My Applications</div></div>
  <div class="table-wrap">
    <table style="width:100%">
      <thead>
        <tr>
          <th style="text-align:left;padding:10px 16px">Internship</th>
          <th style="text-align:left;padding:10px 16px">Applied</th>
          <th style="text-align:left;padding:10px 16px">Status</th>
          <th style="text-align:left;padding:10px 16px">Fee</th>
        </tr>
      </thead>
      <tbody>
        @foreach($myApplications as $app)
        @php $rowConfirmed = $app->status === 'admin_approved' && $app->fee_status === 'paid'; @endphp
        <tr style="border-top:1px solid var(--border)" class="{{ $rowConfirmed ? 'is-confirmed-row' : '' }}">
          <td style="padding:12px 16px;font-weight:600">{{ $app->internship->title ?? '—' }}</td>
          <td style="padding:12px 16px">{{ $app->created_at->format('d M Y') }}</td>
          <td style="padding:12px 16px">
            @if($rowConfirmed)
              <span class="confetti-badge"><i class="fas fa-circle-check"></i> {{ $app->statusLabel() }}</span>
            @else
              <span class="badge {{ $app->statusColor() }}">{{ $app->statusLabel() }}</span>
            @endif
          </td>
          <td style="padding:12px 16px">
            @if($app->voucher)
              <a href="{{ route('student.voucher', $app->voucher) }}" style="color:var(--primary);font-weight:600">{{ $app->voucher->voucher_no }}</a>
              — {{ $app->fee_status === 'paid' ? 'Paid' : 'Unpaid' }}
            @else
              —
            @endif
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
</div>
@endif

@endif

<script>
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }
function openInternship(id) { openModal('internshipModal' + id); }

// Small celebratory confetti burst — fires once per page load if any
// internship on this page is confirmed (approved + fee paid).
(function () {
  if (!document.querySelector('[data-celebrate="1"]')) return;
  const key = 'internshipConfettiShown';
  if (sessionStorage.getItem(key)) return;
  sessionStorage.setItem(key, '1');

  const layer = document.createElement('div');
  layer.id = 'confettiLayer';
  document.body.appendChild(layer);

  const colors = ['#10b981', '#f59e0b', '#890c25', '#3b82f6', '#fbbf24'];
  const pieces = 60;
  for (let i = 0; i < pieces; i++) {
    const el = document.createElement('div');
    const size = 6 + Math.random() * 6;
    el.style.position = 'absolute';
    el.style.top = '-20px';
    el.style.left = Math.random() * 100 + 'vw';
    el.style.width = size + 'px';
    el.style.height = size * 0.4 + 'px';
    el.style.background = colors[i % colors.length];
    el.style.opacity = '0.9';
    el.style.borderRadius = '2px';
    el.style.transform = `rotate(${Math.random() * 360}deg)`;
    const duration = 2.2 + Math.random() * 1.6;
    const delay = Math.random() * 0.4;
    el.animate(
      [
        { transform: `translateY(0) rotate(0deg)`, opacity: 0.9 },
        { transform: `translateY(${90 + Math.random() * 10}vh) rotate(${360 + Math.random() * 360}deg)`, opacity: 0 },
      ],
      { duration: duration * 1000, delay: delay * 1000, easing: 'ease-in', fill: 'forwards' }
    );
    layer.appendChild(el);
  }
  setTimeout(() => layer.remove(), 4200);
})();
</script>

@endsection
