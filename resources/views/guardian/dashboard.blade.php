@extends('layouts.app')
@section('title', 'Parent Dashboard')
@section('page_title', 'Parent Dashboard')
@section('content')

<div style="background:linear-gradient(135deg,var(--primary),#6366f1);border-radius:16px;
            padding:28px 32px;margin-bottom:24px;color:#fff">
  <div style="font-size:24px;font-weight:800;margin-bottom:6px">
    Welcome, {{ auth()->user()->name }} 👨‍👩‍👦
  </div>
  <div style="opacity:.8;font-size:14px">
    Monitor your children's progress and school activities
  </div>
</div>

@if($children->isEmpty())
  <div class="card">
    <div style="text-align:center;padding:60px;color:var(--text-muted)">
      <i class="fas fa-user-graduate" style="font-size:40px;opacity:0.3;display:block;margin-bottom:12px"></i>
      <div style="font-size:16px;font-weight:600;margin-bottom:6px">No linked students found</div>
      <div style="font-size:13px">Please contact admin to link your children to this account.</div>
    </div>
  </div>
@else
  @foreach($children as $child)
  <div class="card" style="margin-bottom:20px">
    <div class="card-header">
      <div style="display:flex;align-items:center;gap:12px">
        @if($child->photo)
          <img src="{{ storage_asset($child->photo) }}"
               style="width:44px;height:44px;border-radius:50%;object-fit:cover;border:2px solid var(--border)">
        @else
          <div style="width:44px;height:44px;border-radius:50%;background:var(--primary);
                      display:flex;align-items:center;justify-content:center;
                      color:#fff;font-weight:700;font-size:16px;flex-shrink:0">
            {{ strtoupper(substr($child->name, 0, 2)) }}
          </div>
        @endif
        <div>
          <div class="card-title">{{ $child->name }} @include('partials.student-status-badge', ['statusStudent' => $child])</div>
          <div style="font-size:13px;color:var(--text-muted)">
            {{ $child->schoolClass->name ?? '—' }}
            · Roll: {{ $child->roll_number }}
            · ID: {{ $child->student_id }}
          </div>
        </div>
      </div>
      <div style="display:flex;gap:8px">
        <a href="{{ route('parent.child.results', $child) }}" class="btn btn-outline btn-sm">
          <i class="fas fa-award"></i> Results
        </a>
        <a href="{{ route('parent.child', $child) }}" class="btn btn-outline btn-sm">
          <i class="fas fa-eye"></i> Full Details
        </a>
      </div>
    </div>
    <div class="card-body">
      <div style="display:flex;gap:12px;flex-wrap:wrap">

        {{-- Attendance --}}
        @php $att = $child->attendancePercentage(); @endphp
        <div style="flex:1;min-width:120px;text-align:center;padding:16px;
                    background:var(--bg);border-radius:10px">
          <div style="font-size:26px;font-weight:800;
                      color:{{ $att >= 75 ? 'green' : ($att >= 50 ? 'orange' : 'red') }}">
            {{ $att }}%
          </div>
          <div style="font-size:12px;color:var(--text-muted);margin-top:2px">
            <i class="fas fa-calendar-check"></i> Attendance
          </div>
        </div>

        {{-- Avg Grade --}}
        @php $avg = $child->averageGrade(); @endphp
        <div style="flex:1;min-width:120px;text-align:center;padding:16px;
                    background:var(--bg);border-radius:10px">
          <div style="font-size:26px;font-weight:800;
                      color:{{ $avg >= 70 ? 'green' : ($avg >= 50 ? 'orange' : 'red') }}">
            {{ $avg }}%
          </div>
          <div style="font-size:12px;color:var(--text-muted);margin-top:2px">
            <i class="fas fa-star"></i> Avg Grade
          </div>
        </div>

        {{-- Pending Fees --}}
        @php $fees = $child->pendingFees(); @endphp
        <div style="flex:1;min-width:120px;text-align:center;padding:16px;
                    background:var(--bg);border-radius:10px">
          <div style="font-size:26px;font-weight:800;
                      color:{{ $fees > 0 ? 'red' : 'green' }}">
            Rs.{{ number_format($fees) }}
          </div>
          <div style="font-size:12px;color:var(--text-muted);margin-top:2px">
            <i class="fas fa-money-bill"></i> Pending Fees
          </div>
        </div>

        {{-- Fee Status badge --}}
        <div style="flex:1;min-width:120px;text-align:center;padding:16px;
                    background:var(--bg);border-radius:10px">
          <div style="font-size:22px;font-weight:800">
            <span class="badge {{ $child->currentFeeStatus() === 'paid' ? 'success' : 'danger' }}"
                  style="font-size:13px">
              {{ ucfirst($child->currentFeeStatus()) }}
            </span>
          </div>
          <div style="font-size:12px;color:var(--text-muted);margin-top:6px">
            <i class="fas fa-receipt"></i> Fee Status
          </div>
        </div>

      </div>
    </div>
  </div>
  @endforeach
@endif

@endsection
