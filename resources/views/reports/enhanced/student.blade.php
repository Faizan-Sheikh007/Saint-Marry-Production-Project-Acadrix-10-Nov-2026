@extends('layouts.app')
@section('title',$title.' — '.$student->name)
@section('page_title',$title)
@push('styles')<link rel="stylesheet" href="{{ asset('css/reports.css') }}"><style>.rpt-profile-table{width:100%;border-collapse:collapse}.rpt-profile-table td{width:50%;padding:9px 14px 9px 0;vertical-align:top}.rpt-summary-table{width:100%;table-layout:fixed;border-collapse:collapse;margin:14px 0 20px}.rpt-summary-table td{border:1px solid var(--border);background:var(--surface,#fff);padding:14px 8px;text-align:center;vertical-align:top}.rpt-summary-table .rpt-stat-value{font-size:19px}@media(max-width:700px){.rpt-summary-table .rpt-stat-value{font-size:13px}.rpt-summary-table .rpt-stat-label{font-size:9px}.rpt-summary-table td{padding:10px 3px}}</style>@endpush
@section('content')
@php
  $detailType = $attendanceOnly ? 'attendance' : 'students';
  $query = \Illuminate\Support\Arr::only($filters,['from','to','attendance_scope','subject_id','exam_ids','exam_type','teacher_id','attendance_status']);
@endphp
<div class="rpt-workspace">
  <div class="rpt-top"><div><div class="rpt-kicker">{{ $title }} / {{ $student->student_id }}</div><h1>{{ $student->name }}</h1><div class="rpt-subtitle">{{ $student->program?->name ?: 'No program assigned' }} · {{ $student->schoolClass?->name ?: 'No class assigned' }} · {{ $student->academicYear?->name ?: 'No academic year assigned' }}</div></div>
    <div class="rpt-actions-bar">
      <a class="btn btn-outline btn-sm" href="{{ route($links['prefix'].$detailType,$query) }}"><i class="fas fa-arrow-left"></i> Report list</a>
      <a class="btn btn-outline btn-sm" target="_blank" rel="noopener" href="{{ route($links['prefix'].$detailType.'.print',['student'=>$student->id]+$query) }}"><i class="fas fa-print"></i> Print</a>
      <a class="btn btn-primary btn-sm" href="{{ route($links['prefix'].$detailType.'.pdf',['student'=>$student->id]+$query) }}"><i class="fas fa-file-pdf"></i> Download {{ $attendanceOnly?'attendance':'student' }} PDF</a>
    </div>
  </div>
  @if($errors->any())<div class="rpt-note rpt-error" role="alert">@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
  @if(session('success'))<div class="rpt-note" role="status">{{ session('success') }}</div>@endif
  <div class="rpt-tabs"><a href="#overview">Overview</a><a href="#attendance">Attendance</a>@if(!$attendanceOnly)<a href="#results">Exam results</a><a href="#fees">Fees &amp; payments</a>@endif<a href="#remarks">Teacher remarks</a><a class="active" href="{{ route($links['prefix'].($attendanceOnly?'students':'attendance').'.show',['student'=>$student->id]+$query) }}">{{ $attendanceOnly?'View complete student report':'View attendance-only report' }}</a></div>
  <section class="rpt-card"><div class="rpt-card-head"><h2>Reporting period &amp; academic selection</h2></div><div class="rpt-card-body">
    <form method="GET" action="{{ route($links['prefix'].$detailType.'.show',$student) }}" data-no-ajax>
      <div class="rpt-filter-grid">
        <div class="rpt-filter-field"><label for="detail-from">From</label><input id="detail-from" class="form-control" name="from" type="date" value="{{ $filters['from'] }}"></div>
        <div class="rpt-filter-field"><label for="detail-to">To</label><input id="detail-to" class="form-control" name="to" type="date" value="{{ $filters['to'] }}"></div>
        <div class="rpt-filter-field"><label for="detail-register">Attendance register</label><select id="detail-register" class="form-control" name="attendance_scope">@foreach(['all'=>'All recorded sessions','general'=>'General / daily register','subject'=>'Subject registers'] as $v=>$label)<option value="{{ $v }}" @selected($filters['attendance_scope']===$v)>{{ $label }}</option>@endforeach</select></div>
        <div class="rpt-filter-field"><label for="detail-subject">Subject</label><select id="detail-subject" name="subject_id" class="form-control"><option value="">All subjects</option>@foreach($options['subjects'] as $subject)<option value="{{ $subject->id }}" @selected(($filters['subject_id']??null)==$subject->id)>{{ $subject->name }}</option>@endforeach</select></div>
        @if($attendanceOnly)<div class="rpt-filter-field"><label for="detail-status">Session list status</label><select id="detail-status" name="attendance_status" class="form-control"><option value="">All statuses</option>@foreach(['present','absent','late','leave'] as $status)<option value="{{ $status }}" @selected(($filters['attendance_status']??'')===$status)>{{ ucfirst($status) }}</option>@endforeach</select></div>@endif
      </div>
      @if(!$attendanceOnly)
      <details class="rpt-details" @if(!empty($filters['exam_ids']) || !empty($filters['exam_type']) || !empty($filters['teacher_id'])) open @endif><summary>Select multiple examinations</summary>
        <div class="rpt-filter-grid">
          @php
            $examOptions = $options['exams']->filter(fn($e)=>in_array($e->id,$studentExamIds));
            $examFields = [
              'exam_ids'=>['label'=>'Exams','type'=>'multiple','wide'=>true,'options'=>$examOptions->mapWithKeys(fn($e)=>[$e->id=>$e->exam_date?->format('Y-m-d').' · '.$e->name.' · '.($e->subject?->name??'General')])->all(),'hint'=>'Leave unselected for all graded exams in the period. Ctrl / Command selects multiple.'],
              'exam_type'=>['label'=>'Exam type','type'=>'select','options'=>['midterm'=>'Midterm','final'=>'Final','quiz'=>'Quiz','unit_test'=>'Unit test']],
              'teacher_id'=>['label'=>'Exam teacher','type'=>'select','options'=>$options['teachers']->pluck('name','id')->all()],
            ];
          @endphp
          @foreach($examFields as $name=>$field)@include('reports.enhanced.partials.field')@endforeach
        </div>
      </details>
      @endif
      <div class="rpt-filter-bottom"><span class="rpt-hint">Academic filters affect attendance, exams and remarks. Fee balances remain current.</span><div class="rpt-actions-bar"><a class="btn btn-outline btn-sm" href="{{ route($links['prefix'].$detailType.'.show',$student) }}">Reset</a><button class="btn btn-primary btn-sm" type="submit">Apply selection</button></div></div>
    </form>
  </div></section>
  @if(!empty($filterSummary))<div class="rpt-chips">@foreach($filterSummary as $label=>$value)<span class="rpt-chip"><strong>{{ $label }}:</strong> {{ $value }}</span>@endforeach</div>@endif
  @include('reports.enhanced.partials.student-body',['pdfMode'=>false])
  @if($canWriteRemarks)
  <section class="rpt-card" id="add-remark"><div class="rpt-card-head"><h2>Add a manual teacher / administrator remark</h2><span class="rpt-hint">Existing remarks are retained; add a new note for corrections.</span></div><div class="rpt-card-body">
    <form method="POST" action="{{ route($links['prefix'].'students.remarks.store',$student) }}">
      @csrf
      <div class="rpt-filter-grid">
        <div class="rpt-filter-field"><label for="remark-date">Remark date</label><input class="form-control" id="remark-date" type="date" name="remark_date" max="{{ today()->toDateString() }}" value="{{ old('remark_date',today()->toDateString()) }}" required></div>
        <div class="rpt-filter-field"><label for="remark-category">Category</label><select class="form-control" id="remark-category" name="category">@foreach(['general','academic','attendance','behaviour','improvement','achievement'] as $category)<option value="{{ $category }}" @selected(old('category','general')===$category)>{{ ucfirst($category) }}</option>@endforeach</select></div>
        <div class="rpt-filter-field"><label for="remark-subject">Subject</label><select class="form-control" id="remark-subject" name="subject_id"><option value="">General remark</option>@foreach($remarkSubjects as $subject)<option value="{{ $subject->id }}" @selected(old('subject_id')==$subject->id)>{{ $subject->name }}</option>@endforeach</select></div>
        @if(auth()->user()->isAdmin())<div class="rpt-filter-field"><label for="remark-teacher">Teacher attribution</label><select class="form-control" id="remark-teacher" name="teacher_id"><option value="">Administrator remark</option>@foreach($assignedTeachers as $teacher)<option value="{{ $teacher->id }}" @selected(old('teacher_id')==$teacher->id)>{{ $teacher->name }}</option>@endforeach</select></div>@endif
      </div>
      <div class="rpt-filter-field" style="margin-top:16px"><label for="remark-text">Remark</label><textarea class="form-control" id="remark-text" name="remarks" rows="4" maxlength="2000" required placeholder="Record progress, attendance concerns, achievements, or specific next steps…">{{ old('remarks') }}</textarea></div>
      <div class="rpt-filter-bottom"><span class="rpt-hint">Entered by {{ auth()->user()->name }}. The author and recording time are saved automatically. Accounts can read but cannot change these notes.</span><button class="btn btn-primary" type="submit">Save remark</button></div>
    </form>
  </div></section>
  @endif
  <details class="rpt-note"><summary><strong>Attendance calculation &amp; data notes</strong></summary>@foreach($notes as $note)<p>{{ $note }}</p>@endforeach</details>
</div>
@endsection
