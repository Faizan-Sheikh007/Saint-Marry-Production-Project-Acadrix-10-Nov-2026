@php
  $curMonth = ($from && $period === 'monthly') ? $from->format('Y-m') : now()->format('Y-m');
  $curYear  = ($from && $period === 'yearly') ? $from->format('Y') : now()->year;
  $curFrom  = ($from && $period === 'range') ? $from->format('Y-m-d') : now()->startOfMonth()->format('Y-m-d');
  $curTo    = ($to && $period === 'range') ? $to->format('Y-m-d') : now()->format('Y-m-d');
@endphp
<div class="card" style="margin-bottom:20px">
  <div class="card-body" style="padding:16px 20px">
    <form method="GET" action="{{ $action }}" style="display:flex;gap:12px;align-items:flex-end;flex-wrap:wrap" data-ajax-filter>

      @if($showSearch ?? false)
      <div class="form-group" style="margin-bottom:0;min-width:180px">
        <label class="form-label">Search Student</label>
        <input type="text" name="search" class="form-control" placeholder="Name / ID / Father's Name" value="{{ $search ?? '' }}">
      </div>
      @endif

      <div class="form-group" style="margin-bottom:0">
        <label class="form-label">Program</label>
        <select name="program_id" class="form-control">
          <option value="all" {{ $programId === 'all' ? 'selected' : '' }}>Overall (All Programs)</option>
          @foreach($programs as $p)
          <option value="{{ $p->id }}" {{ (string) $programId === (string) $p->id ? 'selected' : '' }}>{{ $p->name }}</option>
          @endforeach
        </select>
      </div>

      @if($showAcademicYear ?? false)
      <div class="form-group" style="margin-bottom:0;min-width:170px">
        <label class="form-label">Academic Year</label>
        <select name="academic_year_id" class="form-control">
          <option value="all">All Academic Years</option>
          @foreach(($academicYears ?? collect()) as $academicYear)
          <option value="{{ $academicYear->id }}" {{ (string)($academicYearId ?? 'all') === (string)$academicYear->id ? 'selected' : '' }}>{{ $academicYear->name ?? $academicYear->year ?? $academicYear->id }}</option>
          @endforeach
        </select>
      </div>
      @endif

      @if($showSemester ?? false)
      <div class="form-group" style="margin-bottom:0;min-width:180px">
        <label class="form-label">Semester / Part</label>
        <select name="semester_id" class="form-control">
          <option value="all" {{ ($semesterId ?? 'all') === 'all' ? 'selected' : '' }}>All Semesters</option>
          @foreach(($semesters ?? collect()) as $semester)
          <option value="{{ $semester->id }}" {{ (string)($semesterId ?? 'all') === (string)$semester->id ? 'selected' : '' }}>{{ $semester->display_name }}</option>
          @endforeach
        </select>
      </div>
      @endif

      @if($showFeeStatus ?? false)
      <div class="form-group" style="margin-bottom:0;min-width:140px">
        <label class="form-label">Status</label>
        <select name="fee_status" class="form-control">
          <option value="all" {{ ($feeStatus ?? 'all') === 'all' ? 'selected' : '' }}>Paid + Unpaid</option>
          <option value="paid" {{ ($feeStatus ?? '') === 'paid' ? 'selected' : '' }}>Paid</option>
          <option value="unpaid" {{ ($feeStatus ?? '') === 'unpaid' ? 'selected' : '' }}>Unpaid</option>
        </select>
      </div>
      @endif

      <div class="form-group" style="margin-bottom:0">
        <label class="form-label">Period</label>
        <select name="period" id="periodSelect" class="form-control" onchange="reportPeriodToggle()">
          <option value="all" {{ $period === 'all' ? 'selected' : '' }}>All Time</option>
          <option value="monthly" {{ $period === 'monthly' ? 'selected' : '' }}>Monthly</option>
          <option value="yearly" {{ $period === 'yearly' ? 'selected' : '' }}>Yearly</option>
          <option value="range" {{ $period === 'range' ? 'selected' : '' }}>Custom Range</option>
        </select>
      </div>

      <div class="form-group" id="monthField" style="margin-bottom:0;{{ $period !== 'monthly' ? 'display:none' : '' }}">
        <label class="form-label">Month</label>
        <input type="month" name="month" class="form-control" value="{{ $curMonth }}">
      </div>

      <div class="form-group" id="yearField" style="margin-bottom:0;{{ $period !== 'yearly' ? 'display:none' : '' }}">
        <label class="form-label">Year</label>
        <input type="number" name="year" class="form-control" style="width:100px" min="2000" max="2100" value="{{ $curYear }}">
      </div>

      <div class="form-group" id="fromField" style="margin-bottom:0;{{ $period !== 'range' ? 'display:none' : '' }}">
        <label class="form-label">From</label>
        <input type="date" name="from" class="form-control" value="{{ $curFrom }}">
      </div>
      <div class="form-group" id="toField" style="margin-bottom:0;{{ $period !== 'range' ? 'display:none' : '' }}">
        <label class="form-label">To</label>
        <input type="date" name="to" class="form-control" value="{{ $curTo }}">
      </div>

      <button type="submit" class="btn btn-primary"><i class="fas fa-filter"></i> Apply Filters</button>
    </form>
  </div>
</div>

<script>
function reportPeriodToggle() {
  var v = document.getElementById('periodSelect').value;
  document.getElementById('monthField').style.display = (v === 'monthly') ? '' : 'none';
  document.getElementById('yearField').style.display  = (v === 'yearly')  ? '' : 'none';
  document.getElementById('fromField').style.display  = (v === 'range')   ? '' : 'none';
  document.getElementById('toField').style.display    = (v === 'range')   ? '' : 'none';
}
</script>
