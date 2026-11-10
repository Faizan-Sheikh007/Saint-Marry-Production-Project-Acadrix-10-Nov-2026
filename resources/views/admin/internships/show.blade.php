@extends('layouts.app')
@section('title', $internship->title)

@push('styles')
<style>
.modal-backdrop{display:none;position:fixed;inset:0;background:rgba(15,5,7,.45);backdrop-filter:blur(3px);z-index:1000;align-items:center;justify-content:center}
.modal-backdrop.open{display:flex}
.modal-box{background:var(--surface,#fff);border-radius:16px;width:100%;max-width:480px;margin:20px;box-shadow:0 24px 60px rgba(139,12,34,.20);overflow:hidden}
.modal-header{display:flex;align-items:center;justify-content:space-between;padding:20px 24px 16px;border-bottom:1px solid var(--border)}
.modal-title{font-size:17px;font-weight:800}
.modal-close{width:32px;height:32px;border-radius:8px;border:none;background:rgba(139,12,34,.08);color:var(--text-muted);display:flex;align-items:center;justify-content:center;font-size:16px;cursor:pointer}
.modal-close:hover{background:var(--primary);color:#fff}
.modal-body{padding:20px 24px}
.modal-footer{padding:16px 24px 20px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:10px}
</style>
@endpush

@section('content')

<a href="{{ route('admin.internships.index') }}" class="text-sm text-muted" style="display:inline-flex;align-items:center;gap:6px;margin-bottom:14px"><i class="fas fa-arrow-left"></i> Back to Internships</a>

<div class="welcome-banner" style="margin-bottom:20px">
  <div style="position:relative;z-index:1;display:flex;align-items:flex-start;justify-content:space-between;flex-wrap:wrap;gap:14px">
    <div>
      <div style="display:flex;align-items:center;gap:10px;flex-wrap:wrap;margin-bottom:6px">
        <h1 style="font-size:21px;font-weight:800"><i class="fas fa-briefcase"></i> {{ $internship->title }}</h1>
        @if($internship->is_active)
          <span class="badge success" style="background:rgba(255,255,255,.18);color:#fff">Active</span>
        @else
          <span class="badge" style="background:rgba(255,255,255,.18);color:#fff">Inactive</span>
        @endif
      </div>
      <div style="display:flex;gap:16px;flex-wrap:wrap;font-size:13px;opacity:.85">
        <span><i class="fas fa-money-bill-wave"></i> Rs. {{ number_format($internship->fee_amount, 0) }} challan</span>
        <span><i class="fas fa-users"></i> {{ $applications->count() }} applicant(s)</span>
        @if($internship->due_date)<span><i class="fas fa-calendar"></i> Due {{ $internship->due_date->format('d M Y') }}</span>@endif
        <span>
          @if($internship->target_type === 'all') <i class="fas fa-globe"></i> All Students
          @elseif($internship->target_type === 'programs') <i class="fas fa-sitemap"></i> {{ count($internship->target_program_ids ?? []) }} Program(s)
          @else <i class="fas fa-user-check"></i> {{ count($internship->target_student_ids ?? []) }} Student(s)
          @endif
        </span>
      </div>
    </div>
    <a href="{{ route('admin.internships.edit', $internship) }}" class="btn btn-outline" style="background:rgba(255,255,255,.1);border-color:rgba(255,255,255,.3);color:#fff"><i class="fas fa-pen"></i> Edit Posting</a>
  </div>
</div>

@if(session('success'))<div class="alert alert-success" style="margin-bottom:16px">{{ session('success') }}</div>@endif
@if(session('error'))<div class="alert alert-danger" style="margin-bottom:16px">{{ session('error') }}</div>@endif

@if($internship->image_path)
<div class="card" style="margin-bottom:20px;overflow:hidden">
  <div style="background:var(--bg);display:flex;align-items:center;justify-content:center;max-height:320px">
    <img src="{{ storage_asset($internship->image_path) }}" style="width:100%;max-height:320px;object-fit:contain">
  </div>
</div>
@endif

@if($internship->description)
<div class="card" style="margin-bottom:20px">
  <div class="card-header"><div class="card-title"><i class="fas fa-align-left" style="opacity:.5;margin-right:6px"></i>Description</div></div>
  <div style="padding:18px 20px;font-size:13.5px;line-height:1.8;color:var(--text);white-space:pre-line">{{ $internship->description }}</div>
</div>
@endif

<div class="card">
  <div class="card-header"><div class="card-title">Applicants</div></div>

  @if($applications->isEmpty())
    <div class="empty-state">
      <i class="fas fa-inbox"></i>
      <p>No applications yet.</p>
    </div>
  @else
  <div class="table-wrap">
    <table style="width:100%">
      <thead>
        <tr>
          <th style="text-align:left;padding:10px 16px">Student</th>
          <th style="text-align:left;padding:10px 16px">Roll No.</th>
          <th style="text-align:left;padding:10px 16px">Applied</th>
          <th style="text-align:left;padding:10px 16px">Status</th>
          <th style="text-align:left;padding:10px 16px">Fee / Challan</th>
          <th style="text-align:right;padding:10px 16px">Actions</th>
        </tr>
      </thead>
      <tbody>
        @foreach($applications as $app)
        <tr style="border-top:1px solid var(--border)">
          <td style="padding:12px 16px">
            <div style="font-weight:700">{{ $app->student->name ?? '—' }}</div>
            <div class="text-sm text-muted">{{ $app->student->student_id ?? '' }}</div>
          </td>
          <td style="padding:12px 16px">{{ $app->roll_number ?: '—' }}</td>
          <td style="padding:12px 16px">{{ $app->created_at->format('d M Y, h:i A') }}</td>
          <td style="padding:12px 16px"><span class="badge {{ $app->statusColor() }}">{{ $app->statusLabel() }}</span></td>
          <td style="padding:12px 16px">
            @if($app->voucher)
              <a href="{{ route('vouchers.show', $app->voucher) }}" class="text-sm" style="color:var(--primary);font-weight:600">{{ $app->voucher->voucher_no }}</a>
              <div class="text-sm text-muted">{{ $app->fee_status === 'paid' ? 'Paid' : 'Unpaid' }}</div>
            @else
              <span class="text-sm text-muted">Not issued yet</span>
            @endif
          </td>
          <td style="padding:12px 16px;text-align:right">
            @if($app->status === 'pending')
              <button type="button" class="btn btn-primary" style="padding:6px 12px;font-size:12.5px" onclick="openReview({{ $app->id }}, 'approve')"><i class="fas fa-check"></i> Approve</button>
              <button type="button" class="btn btn-outline" style="padding:6px 12px;font-size:12.5px;color:#c0192e;border-color:#c0192e" onclick="openReview({{ $app->id }}, 'reject')"><i class="fas fa-xmark"></i> Reject</button>
            @else
              <span class="text-sm text-muted">Reviewed {{ $app->reviewed_at?->format('d M Y') }}</span>
            @endif
          </td>
        </tr>
        @endforeach
      </tbody>
    </table>
  </div>
  @endif
</div>

{{-- One shared modal, retargeted per-row via JS --}}
<div id="reviewModal" class="modal-backdrop">
  <div class="modal-box">
    <div class="modal-header">
      <span class="modal-title" id="reviewModalTitle">Review Application</span>
      <button class="modal-close" onclick="closeModal('reviewModal')">&times;</button>
    </div>
    <form id="reviewForm" method="POST">
      @csrf
      <div class="modal-body">
        <div class="form-group" id="dueDateWrap">
          <label class="form-label">Challan Due Date (optional)</label>
          <input type="date" name="due_date" class="form-control">
        </div>
        <div class="form-group">
          <label class="form-label">Note (optional)</label>
          <textarea name="admin_notes" rows="3" class="form-control" placeholder="Visible only to admin/accounts."></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('reviewModal')">Cancel</button>
        <button type="submit" class="btn btn-primary" id="reviewSubmitBtn"><i class="fas fa-check"></i> Confirm</button>
      </div>
    </form>
  </div>
</div>

<script>
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

function openReview(applicationId, action) {
  const form = document.getElementById('reviewForm');
  const base = "{{ url('/admin/internship-applications') }}";
  form.action = base + '/' + applicationId + '/' + action;

  document.getElementById('reviewModalTitle').textContent = action === 'approve' ? 'Approve Application' : 'Reject Application';
  document.getElementById('dueDateWrap').style.display = action === 'approve' ? 'block' : 'none';

  const btn = document.getElementById('reviewSubmitBtn');
  btn.className = action === 'approve' ? 'btn btn-primary' : 'btn btn-outline';
  btn.innerHTML = action === 'approve'
    ? '<i class="fas fa-check"></i> Approve & Issue Challan'
    : '<i class="fas fa-xmark"></i> Reject';

  openModal('reviewModal');
}
</script>

@endsection
