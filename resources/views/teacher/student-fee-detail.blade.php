@extends('layouts.app')
@section('title', $student->name.' — Fees')
@section('page_title', 'Fees — '.$student->name)
@section('content')

<div style="display:flex;align-items:center;gap:12px;margin-bottom:24px">
  <a href="{{ route('teacher.students.fees') }}" class="btn btn-outline btn-sm">
    <i class="fas fa-arrow-left"></i> Back
  </a>
  <div style="font-size:22px;font-weight:800"><i class="fas fa-receipt"></i> {{ $student->name }} — Fees (read-only)</div>
</div>

@include('partials.fee-summary', ['feeSummary' => $feeSummary, 'feeRouteName' => null])

@endsection
