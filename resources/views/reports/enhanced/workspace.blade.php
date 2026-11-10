@extends('layouts.app')
@section('title',$title)
@section('page_title',$title)
@push('styles')<link rel="stylesheet" href="{{ asset('css/reports.css') }}">@endpush
@push('scripts')<script src="{{ asset('js/reports.js') }}" defer></script>@endpush
@section('content')
@php
  $listRoute = in_array($type,['students','attendance']) ? $links['prefix'].$type : $links['financialPrefix'].$type;
  $mainUrl = route($listRoute);
  $exportQuery = \Illuminate\Support\Arr::except($filters,['page']);
  $withStudentActions = !in_array($type,['class-revenue','income-expense']);
@endphp
<div class="rpt-workspace">
  <div class="rpt-top">
    <div><div class="rpt-kicker">Reporting center / {{ $type }}</div><h1>{{ $title }}</h1>
      <div class="rpt-subtitle">{{ $type === 'students' ? 'Attendance, academic progress, fee position and manually recorded teacher feedback — in one student report.' : 'Explore the matching records, adjust the columns, and export the same report.' }}</div></div>
    <div class="rpt-actions-bar">
      <a href="{{ route($links['home']) }}" class="btn btn-outline btn-sm"><i class="fas fa-arrow-left"></i> Reports</a>
      <a href="{{ route($links['export'],['type'=>$type]+$exportQuery) }}" class="btn btn-outline btn-sm"><i class="fas fa-file-csv"></i> CSV</a>
      <a href="{{ route($links['prefix'].'print',['type'=>$type]+$exportQuery) }}" target="_blank" rel="noopener" class="btn btn-outline btn-sm"><i class="fas fa-print"></i> Print</a>
      <a href="{{ route($links['prefix'].'pdf',['type'=>$type]+$exportQuery) }}" class="btn btn-primary btn-sm"><i class="fas fa-file-pdf"></i> Download PDF</a>
    </div>
  </div>
  @if($errors->any())<div class="rpt-note rpt-error" role="alert"><strong>Check the report filters</strong>@foreach($errors->all() as $error)<p>{{ $error }}</p>@endforeach</div>@endif
  <div class="rpt-stats">
    @foreach($stats as $stat)<div class="rpt-stat">
      @if($stat['format']==='money')<div class="rpt-stat-unit">{{ $currency }}</div>@endif
      <div class="rpt-stat-value">{{ \App\Support\Reports\ReportCatalog::format($stat['value'],$stat['format']) }}</div>
      <div class="rpt-stat-label">{{ $stat['label'] }}</div>
    </div>@endforeach
  </div>
  @include('reports.enhanced.partials.filters')
  @if(!empty($filterSummary))<div class="rpt-chips">@foreach($filterSummary as $label=>$value)<span class="rpt-chip"><strong>{{ $label }}:</strong> {{ $value }}</span>@endforeach</div>@endif
  <div class="rpt-card">
    <div class="rpt-card-head"><h2>{{ number_format($pageRows->total()) }} matching {{ $type==='students' || ($type==='attendance' && $filters['view']==='summary') ? 'students' : 'rows' }}</h2>
      <span class="rpt-hint">All monetary columns: {{ $currency }} · Generated {{ $generatedAt->format('d M Y, H:i') }}</span></div>
    <div class="rpt-table-scroll">
      <table class="rpt-table">
        <thead><tr>
          @foreach($selectedColumns as $key)
            @php([$label,$format]=$availableColumns[$key])
            <th @class(['rpt-numeric'=>in_array($format,['money','percent','integer'])])><a href="{{ route($listRoute,array_merge($exportQuery,['sort'=>$key,'direction'=>$filters['sort']===$key && $filters['direction']==='asc'?'desc':'asc'])) }}">{{ $label }} @if($filters['sort']===$key)<span aria-label="{{ $filters['direction'] }}">{{ $filters['direction']==='asc'?'↑':'↓' }}</span>@endif</a></th>
          @endforeach
          @if($withStudentActions)<th class="rpt-row-actions">Student report</th>@endif
        </tr></thead>
        <tbody>
          @forelse($pageRows as $row)
          <tr>
            @foreach($selectedColumns as $key)
              @php([$label,$format]=$availableColumns[$key])
              <td @class(['rpt-numeric'=>in_array($format,['money','percent','integer']),'rpt-person'=>$key==='name','rpt-code'=>$key==='student_code'])>
                @if($format==='status')<span class="rpt-status {{ $row[$key] ?? '' }}">{{ \App\Support\Reports\ReportCatalog::format($row[$key]??null,$format) }}</span>
                @elseif(in_array($key,['latest_remark','attendance_remark','payment_notes','notes']))<span title="{{ $row[$key]??'' }}">{{ \Illuminate\Support\Str::limit($row[$key]??'—',160) }}</span>
                @else{{ \App\Support\Reports\ReportCatalog::format($row[$key]??null,$format) }}@endif
              </td>
            @endforeach
            @if($withStudentActions)
              @php($detailType=$type==='attendance'?'attendance':'students')
              <td class="rpt-row-actions">
                <a class="btn btn-outline btn-sm" href="{{ route($links['prefix'].$detailType.'.show',['student'=>$row['_student_id']]+$exportQuery) }}">View</a>
                <a class="btn btn-outline btn-sm" href="{{ route($links['prefix'].$detailType.'.pdf',['student'=>$row['_student_id']]+$exportQuery) }}">PDF</a>
                @if($type==='students' && !$links['accounts'])<a href="{{ route($links['prefix'].'students.show',['student'=>$row['_student_id']]+$exportQuery) }}#remarks" style="color:var(--rpt-accent);display:block">Add remark</a>@endif
              </td>
            @endif
          </tr>
          @empty<tr><td colspan="{{ count($selectedColumns)+($withStudentActions?1:0) }}" class="rpt-empty"><strong>No matching records.</strong><br>Change the dates or filters. Missing data is not replaced with invented values.</td></tr>@endforelse
        </tbody>
      </table>
    </div>
    <div class="rpt-footer"><span class="rpt-hint">{{ $pageRows->firstItem()??0 }}–{{ $pageRows->lastItem()??0 }} of {{ number_format($pageRows->total()) }} · Summary totals and exports include all matching rows, not only this page.</span>{{ $pageRows->links() }}</div>
  </div>
  <details class="rpt-note"><summary><strong>How this report is calculated</strong></summary>@foreach($notes as $note)<p>{{ $note }}</p>@endforeach</details>
</div>
@endsection
