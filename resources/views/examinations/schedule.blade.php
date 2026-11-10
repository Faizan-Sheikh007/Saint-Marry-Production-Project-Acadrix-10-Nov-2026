@extends('layouts.app')
@section('title', 'Schedule Examination')
@section('page_title', 'Schedule Examination')
@section('content')

<style>
.ex-card{background:var(--card);border:1px solid var(--border);border-radius:var(--radius);box-shadow:var(--shadow);margin-bottom:16px;overflow:hidden}
.ex-card-head{padding:15px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;gap:12px}
.ex-card-title{display:flex;align-items:center;gap:10px;font-weight:800;font-size:14px}.ex-step{width:27px;height:27px;border-radius:50%;background:var(--primary);color:#fff;font-size:12px;font-weight:800;display:flex;align-items:center;justify-content:center;flex:0 0 auto}.ex-muted{font-size:12px;color:var(--text-muted);margin-top:2px}
.ex-setup-grid{padding:16px 18px;display:grid;grid-template-columns:2fr 1fr;gap:12px}.ex-program-grid{padding:0 18px 16px;display:grid;grid-template-columns:repeat(auto-fill,minmax(190px,1fr));gap:8px}.ex-chip{border:1.5px solid var(--border);border-radius:10px;background:var(--bg);padding:10px 12px;display:flex;gap:9px;align-items:flex-start;cursor:pointer;transition:.15s}.ex-chip:hover,.ex-chip.checked{border-color:var(--primary);background:rgba(137,12,37,.055)}.ex-chip input{margin-top:2px;accent-color:var(--primary)}.ex-chip strong{display:block;font-size:12.5px;line-height:1.25}.ex-chip small{display:block;color:var(--text-muted);font-size:10.8px;margin-top:2px}
.ex-program-group{border-top:1px dashed var(--border);padding:12px 18px}.ex-program-group:first-child{border-top:0}.ex-program-label{font-size:10.5px;text-transform:uppercase;letter-spacing:.06em;font-weight:800;color:var(--text-muted);margin-bottom:8px;display:flex;justify-content:space-between;align-items:center}.ex-class-grid{display:grid;grid-template-columns:repeat(auto-fill,minmax(210px,1fr));gap:8px}
.ex-defaults{padding:14px 18px;display:grid;grid-template-columns:repeat(auto-fit,minmax(135px,1fr));gap:10px;align-items:end;border-top:1px solid var(--border)}.ex-defaults .form-group{margin:0}.ex-defaults .form-label{font-size:10.5px}.ex-actions{padding:14px 18px;border-top:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap}
.ex-picked{padding:10px 18px;background:var(--bg);border-bottom:1px solid var(--border);display:flex;gap:7px;flex-wrap:wrap;align-items:center}.ex-picked-label{font-size:10px;font-weight:800;text-transform:uppercase;letter-spacing:.05em;color:var(--text-muted);margin-right:2px}.ex-picked-chip{font-size:10.5px;font-weight:700;padding:5px 8px;border:1px solid var(--border);border-radius:16px;background:var(--card)}
.ex-grid-wrap{overflow:auto;-webkit-overflow-scrolling:touch}.ex-grid{width:100%;min-width:920px;border-collapse:separate;border-spacing:0;table-layout:fixed}.ex-grid th{font-size:10px;font-weight:800;letter-spacing:.045em;text-transform:uppercase;color:var(--text-muted);padding:11px 8px;background:var(--bg);border-bottom:1px solid var(--border);border-right:1px solid var(--border);text-align:center}.ex-grid th:first-child{width:145px;text-align:left;padding-left:16px;position:sticky;left:0;z-index:4;background:var(--bg)}.ex-grid td{padding:8px;border-right:1px solid var(--border);border-bottom:1px solid var(--border);vertical-align:middle;background:var(--card);height:122px;overflow:hidden}.ex-grid td:first-child{position:sticky;left:0;z-index:3;background:var(--bg);padding:10px 12px}.ex-grid-date{font-size:12px;font-weight:800}.ex-grid-slot{font-size:10px;color:var(--text-muted);margin-top:4px}
.ex-empty-cell{height:104px;border:1px dashed var(--border);border-radius:9px;display:flex;align-items:center;justify-content:center;cursor:pointer;color:#b88f98;font-size:21px;transition:.15s;background:var(--bg)}.ex-empty-cell:hover{border-color:var(--primary);color:var(--primary);background:rgba(137,12,37,.035);transform:translateY(-1px)}
.ex-exam-cell{height:104px;box-sizing:border-box;border:1px solid rgba(137,12,37,.22);background:rgba(137,12,37,.065);border-radius:9px;padding:8px 9px;position:relative;cursor:pointer;transition:.15s;overflow:hidden;display:flex;flex-direction:column}.ex-exam-cell:hover{border-color:var(--primary);transform:translateY(-1px)}.ex-cell-subject{font-size:10.8px;font-weight:800;line-height:1.2;padding-right:18px;overflow:hidden;display:-webkit-box;-webkit-box-orient:vertical;-webkit-line-clamp:3;line-clamp:3;word-break:break-word}.ex-cell-meta{font-size:9px;color:var(--text-muted);margin-top:auto;line-height:1.3;overflow:hidden;white-space:nowrap;text-overflow:ellipsis}.ex-cell-edit{position:absolute;right:7px;top:7px;color:var(--primary);font-size:10px}.ex-note{margin:12px 18px;padding:10px 12px;border-radius:8px;background:#fff8e6;color:#7a5700;font-size:11.5px}.ex-summary{padding:11px 18px;background:var(--bg);border-bottom:1px solid var(--border);display:flex;gap:18px;flex-wrap:wrap;font-size:12px;color:var(--text-muted)}.ex-summary strong{color:var(--text)}.ex-save{padding:13px 18px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:9px;background:var(--card)}
.ex-modal-backdrop{position:fixed;inset:0;background:rgba(24,11,16,.52);backdrop-filter:blur(4px);z-index:5000;display:none;align-items:center;justify-content:center;padding:18px}.ex-modal-backdrop.show{display:flex}.ex-modal{width:min(650px,100%);background:var(--card);border-radius:16px;box-shadow:0 30px 90px rgba(0,0,0,.28);overflow:hidden}.ex-modal-head{padding:20px 26px 16px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center}.ex-modal-title{font-weight:800;font-size:18px}.ex-modal-close{width:36px;height:36px;border:0;border-radius:9px;background:rgba(137,12,37,.07);color:var(--primary);font-size:18px;cursor:pointer}.ex-modal-body{padding:20px 26px;display:grid;grid-template-columns:repeat(2,1fr);gap:14px}.ex-modal-body .form-group{margin:0}.ex-modal-body .form-label{font-size:11px;text-transform:uppercase;letter-spacing:.04em;font-weight:800}.ex-modal-wide{grid-column:1/-1}.ex-modal-foot{padding:16px 26px;border-top:1px solid var(--border);display:flex;justify-content:flex-end;gap:9px}.ex-readonly{background:var(--bg)!important;color:var(--text-muted)}.ex-remove{margin-right:auto}
@media(max-width:1100px){.ex-defaults{grid-template-columns:repeat(4,1fr)}}
@media(max-width:760px){.ex-setup-grid{grid-template-columns:1fr}.ex-defaults{grid-template-columns:repeat(2,1fr)}.ex-program-grid,.ex-class-grid{grid-template-columns:1fr}.ex-modal-body{grid-template-columns:1fr}.ex-modal-wide{grid-column:auto}}
</style>

<div class="page-header">
  <div>
    <div class="page-title"><i class="fas fa-calendar-plus" style="color:var(--primary)"></i> Schedule Examination</div>
    <div class="page-subtitle">Select programs and semesters first, then click any timetable box to add an exam.</div>
  </div>
  <a href="{{ route('admin.examinations.index') }}" class="btn btn-outline"><i class="fas fa-arrow-left"></i> Back</a>
</div>

@if(session('scheduleError'))
  <div class="alert alert-danger"><i class="fas fa-triangle-exclamation"></i><div><strong>{{ session('scheduleError') }}</strong>@foreach($errors->all() as $msg)<div>{{ $msg }}</div>@endforeach</div></div>
@endif
@if($errors->any() && !session('scheduleError'))
  <div class="alert alert-danger"><i class="fas fa-triangle-exclamation"></i><div>@foreach($errors->all() as $msg)<div>{{ $msg }}</div>@endforeach</div></div>
@endif

<form action="{{ route('admin.examinations.schedule.store') }}" method="POST" id="scheduleForm">
@csrf

<div class="ex-card">
  <div class="ex-card-head"><div class="ex-card-title"><span class="ex-step">1</span><div>Exam &amp; Program Selection<div class="ex-muted">Choose the exam and programs you want to work with</div></div></div></div>
  <div class="ex-setup-grid">
    <div class="form-group" style="margin:0;grid-column:1/-1"><label class="form-label">Academic Session *</label>
      <select name="academic_year_id" class="form-control" required><option value="">Select the session for this examination</option>
        @foreach($academicYears as $year)<option value="{{ $year->id }}" {{ old('academic_year_id', request('academic_year_id')) == $year->id ? 'selected' : '' }}>{{ $year->name }}</option>@endforeach
      </select><p class="text-sm text-muted">Choose semesters linked to this session. The session is saved on each subject paper.</p>
    </div>
    <div class="form-group" style="margin:0"><label class="form-label">Exam Name *</label><input type="text" name="exam_name" class="form-control" required value="{{ old('exam_name') }}" placeholder="e.g. Mid Term Examination 2026"></div>
    <div class="form-group" style="margin:0"><label class="form-label">Type *</label><select name="type" class="form-control" required>@foreach(['midterm'=>'Mid Term','final'=>'Final','quiz'=>'Quiz','unit_test'=>'Unit Test'] as $k=>$v)<option value="{{ $k }}" {{ old('type') === $k ? 'selected' : '' }}>{{ $v }}</option>@endforeach</select></div>
  </div>
  <div style="padding:0 18px 7px;font-size:10.5px;text-transform:uppercase;letter-spacing:.06em;font-weight:800;color:var(--text-muted)">Programs</div>
  <div class="ex-program-grid">
    @forelse($programs as $program)
      <label class="ex-chip" data-program-toggle><input type="checkbox" class="program-checkbox" value="{{ $program->id }}"><span><strong>{{ $program->name }}</strong><small>{{ $program->sections->count() }} semester{{ $program->sections->count() === 1 ? '' : 's' }}</small></span></label>
    @empty
      <div class="ex-muted">No active programs found.</div>
    @endforelse
  </div>
</div>

<div class="ex-card" id="step2Card" style="display:none">
  <div class="ex-card-head"><div class="ex-card-title"><span class="ex-step">2</span><div>Select Semester(s)<div class="ex-muted">Only semesters from your selected programs are available</div></div></div></div>
  <div id="classesContainer"></div>
  <div class="ex-defaults" id="defaultsArea" style="display:none">
    <div class="form-group"><label class="form-label">First Exam Date *</label><input type="date" id="firstDate" class="form-control"></div>
    <div class="form-group"><label class="form-label">Start *</label><input type="time" id="defaultStart" class="form-control" value="09:00"></div>
    <div class="form-group"><label class="form-label">Duration</label><select id="defaultDuration" class="form-control"><option value="30">30 min</option><option value="60">1 hr</option><option value="90">1.5 hrs</option><option value="120" selected>2 hrs</option><option value="150">2.5 hrs</option><option value="180">3 hrs</option><option value="210">3.5 hrs</option><option value="240">4 hrs</option></select></div>
    <div class="form-group"><label class="form-label">Papers per day</label><input type="number" id="papersPerDay" class="form-control" min="1" max="12" value="1"></div><div class="form-group"><label class="form-label">Break between papers</label><select id="paperGap" class="form-control"><option value="30" selected>30 minutes</option><option value="60">1 hour</option><option value="90">1.5 hours</option><option value="120">2 hours</option><option value="150">2.5 hours</option><option value="180">3 hours</option><option value="210">3.5 hours</option><option value="240">4 hours</option></select></div><div class="form-group"><label class="form-label">Gap between exam days</label><select id="dateGap" class="form-control"><option value="1" selected>Next day</option><option value="2">1 day gap</option><option value="3">2 day gap</option></select></div>
    <div class="form-group"><label class="form-label">Default Room</label><input type="text" id="defaultRoom" class="form-control" placeholder="Optional"></div>
    <div class="form-group"><label class="form-label">Total</label><input type="number" id="defaultTotal" class="form-control" value="100" min="1"></div>
    <div class="form-group"><label class="form-label">Pass</label><input type="number" id="defaultPass" class="form-control" value="40" min="1"></div>
  </div>
  <div class="ex-actions" id="step2Actions" style="display:none">
    <label style="display:flex;align-items:center;gap:7px;font-size:12px;color:var(--text-muted);cursor:pointer"><input type="checkbox" id="skipWeekends" checked style="accent-color:var(--primary)"> Skip Saturday &amp; Sunday</label>
    <button type="button" class="btn btn-primary" id="buildTimetableBtn"><i class="fas fa-table-cells"></i> Open Exam Timetable</button>
  </div>
</div>

<div class="ex-card" id="scheduleCard" style="display:none">
  <div class="ex-card-head">
    <div class="ex-card-title"><span class="ex-step">3</span><div>Exam Timetable<div class="ex-muted">Click <b>+</b> in any box to add an exam. Click an existing exam card to edit it. Dates and times entered here override the automatic defaults.</div></div></div>
    <button type="button" class="btn btn-outline btn-sm" id="backToSetupBtn"><i class="fas fa-sliders"></i> Change Selection</button>
  </div>
  <div class="ex-picked" id="pickedSummary"></div>
  <div class="ex-summary" id="scheduleSummary"></div>
  <div id="noSubjectWarnings"></div>
  <div class="ex-grid-wrap"><table class="ex-grid" id="examGrid"></table></div>
  <div class="ex-save"><a href="{{ route('admin.examinations.index') }}" class="btn btn-outline">Cancel</a><button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Save Examination Schedule</button></div>
</div>

<div id="hiddenRows"></div>
</form>

<div class="ex-modal-backdrop" id="examModal" aria-hidden="true">
  <div class="ex-modal">
    <div class="ex-modal-head"><div class="ex-modal-title" id="modalTitle">Add Examination</div><button type="button" class="ex-modal-close" id="modalClose">&times;</button></div>
    <div class="ex-modal-body">
      <div class="form-group"><label class="form-label">Program *</label><input type="text" id="modalProgram" class="form-control ex-readonly" readonly></div>
      <div class="form-group"><label class="form-label">Semester / Part *</label><input type="text" id="modalClass" class="form-control ex-readonly" readonly></div>
      <div class="form-group ex-modal-wide"><label class="form-label">Subject *</label><select id="modalSubject" class="form-control"></select></div>
      <div class="form-group ex-modal-wide"><label class="form-label">Teacher / Invigilator</label><select id="modalTeacher" class="form-control"></select><div class="ex-muted">Curriculum teacher is selected automatically when available; you can still change it.</div></div>
      <div class="form-group"><label class="form-label">Date *</label><input type="date" id="modalDate" class="form-control"></div>
      <div class="form-group"><label class="form-label">Room</label><input type="text" id="modalRoom" class="form-control" placeholder="e.g. Hall A"></div>
      <div class="form-group"><label class="form-label">Start Time *</label><input type="time" id="modalStart" class="form-control"></div>
      <div class="form-group"><label class="form-label">End Time *</label><input type="time" id="modalEnd" class="form-control"></div>
      <div class="form-group"><label class="form-label">Total Marks *</label><input type="number" id="modalTotal" class="form-control" min="1"></div>
      <div class="form-group"><label class="form-label">Passing Marks *</label><input type="number" id="modalPass" class="form-control" min="1"></div>
    </div>
    <div class="ex-modal-foot">
      <button type="button" class="btn btn-outline ex-remove" id="modalRemove" style="display:none"><i class="fas fa-trash"></i> Remove</button>
      <button type="button" class="btn btn-outline" id="modalCancel">Cancel</button>
      <button type="button" class="btn btn-primary" id="modalSave"><i class="fas fa-save"></i> Save Exam</button>
    </div>
  </div>
</div>

@push('scripts')
@php
$teachersJson = $teachers->map(fn($t)=>['id'=>$t->id,'name'=>$t->name])->values();
$classesAllJson = $programs->flatMap(function($p){ return $p->sections->map(fn($c)=>['id'=>$c->id,'program_id'=>$p->id,'program_name'=>$p->name,'name'=>$c->display_name ?? $c->name,'students_count'=>$c->students_count ?? 0]); })->values();
$subjectsAllJson = $subjects->map(fn($s)=>['id'=>$s->id,'name'=>$s->name])->values();
@endphp
<script>
(function(){
  @php $oldScheduleRows = old('rows', []); @endphp
  const teachers=@json($teachersJson), classesAll=@json($classesAllJson), subjectsAll=@json($subjectsAllJson), oldRows=@json($oldScheduleRows);
  const classesJsonUrl=@json(route('admin.examinations.class-subjects'));
  const step2Card=document.getElementById('step2Card'),classesContainer=document.getElementById('classesContainer'),defaultsArea=document.getElementById('defaultsArea'),step2Actions=document.getElementById('step2Actions'),scheduleCard=document.getElementById('scheduleCard'),examGrid=document.getElementById('examGrid'),hiddenRows=document.getElementById('hiddenRows');
  const modal=document.getElementById('examModal');
  let loadedClasses=[],scheduleRows=[],dateSlots=[],activeClassId=null,activeSlotIndex=null,editingRowIndex=null;

  function esc(v){return String(v??'').replace(/[&<>'"]/g,c=>({'&':'&amp;','<':'&lt;','>':'&gt;',"'":'&#039;','"':'&quot;'}[c]));}
  function isoDate(d){return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;}
  function prettyDate(v){if(!v)return '—';return new Date(v+'T00:00:00').toLocaleDateString(undefined,{weekday:'short',day:'2-digit',month:'short',year:'numeric'});}
  function addMinutes(time,mins){const [h,m]=time.split(':').map(Number),n=h*60+m+Number(mins);return `${String(Math.floor(n/60)%24).padStart(2,'0')}:${String(n%60).padStart(2,'0')}`;}
  function advanceDate(date,days,skip){const d=new Date(date);let r=Number(days);while(r>0){d.setDate(d.getDate()+1);if(skip&&(d.getDay()===0||d.getDay()===6))continue;r--;}return d;}
  function selectedProgramIds(){return [...document.querySelectorAll('.program-checkbox:checked')].map(x=>String(x.value));}
  function selectedClassIds(){return [...document.querySelectorAll('.class-checkbox:checked')].map(x=>String(x.value));}
  function classMeta(id){return classesAll.find(c=>String(c.id)===String(id))||{};}
  function loadedClass(id){return loadedClasses.find(c=>String(c.id)===String(id));}

  document.querySelectorAll('[data-program-toggle]').forEach(label=>{
    const cb=label.querySelector('input');
    cb.addEventListener('change',renderClasses);
    label.addEventListener('click',e=>{if(e.target.tagName!=='INPUT'){e.preventDefault();cb.checked=!cb.checked;renderClasses();}});
  });

  function renderClasses(){
    const ids=selectedProgramIds();document.querySelectorAll('[data-program-toggle]').forEach(l=>l.classList.toggle('checked',l.querySelector('input').checked));
    classesContainer.innerHTML='';scheduleCard.style.display='none';loadedClasses=[];scheduleRows=[];dateSlots=[];
    if(!ids.length){step2Card.style.display='none';return;}step2Card.style.display='';
    const grouped={};classesAll.filter(c=>ids.includes(String(c.program_id))).forEach(c=>(grouped[c.program_id]||=[]).push(c));
    Object.entries(grouped).forEach(([pid,items])=>{
      const programName=items[0]?.program_name||'Program';const group=document.createElement('div');group.className='ex-program-group';
      group.innerHTML=`<div class="ex-program-label"><span>${esc(programName)}</span><button type="button" class="btn btn-outline btn-sm ex-select-all">Select all</button></div><div class="ex-class-grid"></div>`;
      const grid=group.querySelector('.ex-class-grid');
      items.forEach(c=>{const label=document.createElement('label');label.className='ex-chip';label.innerHTML=`<input type="checkbox" class="class-checkbox" value="${c.id}"><span><strong>${esc(c.name)}</strong><small>${c.students_count||0} students</small></span>`;const chk=label.querySelector('input');chk.addEventListener('change',()=>{label.classList.toggle('checked',chk.checked);updateStep2State();});label.addEventListener('click',e=>{if(e.target.tagName!=='INPUT'){e.preventDefault();chk.checked=!chk.checked;label.classList.toggle('checked',chk.checked);updateStep2State();}});grid.appendChild(label);});
      group.querySelector('.ex-select-all').onclick=()=>{group.querySelectorAll('.class-checkbox').forEach(c=>{c.checked=true;c.closest('.ex-chip').classList.add('checked')});updateStep2State();};classesContainer.appendChild(group);
    });
    updateStep2State();step2Card.scrollIntoView({behavior:'smooth',block:'start'});
  }
  function updateStep2State(){const has=selectedClassIds().length>0;defaultsArea.style.display=has?'grid':'none';step2Actions.style.display=has?'flex':'none';}

  document.getElementById('buildTimetableBtn').addEventListener('click',function(){
    const ids=selectedClassIds(),first=document.getElementById('firstDate').value,start=document.getElementById('defaultStart').value;
    if(!ids.length){alert('Select at least one semester.');return;}if(!first||!start){alert('Choose the first exam date and start time.');return;}if(Number(document.getElementById('defaultPass').value)>Number(document.getElementById('defaultTotal').value)){alert('Passing marks cannot exceed total marks.');return;}
    this.disabled=true;this.innerHTML='<i class="fas fa-spinner fa-spin"></i> Loading…';
    fetch(`${classesJsonUrl}?class_ids=${ids.join(',')}`).then(r=>{if(!r.ok)throw new Error();return r.json();}).then(data=>{loadedClasses=data.classes||[];buildBlankGrid();}).catch(()=>alert('Subjects could not be loaded. Please refresh and try again.')).finally(()=>{this.disabled=false;this.innerHTML='<i class="fas fa-table-cells"></i> Open Exam Timetable';});
  });

  function buildBlankGrid(){
    const first=document.getElementById('firstDate').value,gap=Number(document.getElementById('dateGap').value||1),skip=document.getElementById('skipWeekends').checked;
    const maxSubjects=Math.max(1,...loadedClasses.map(c=>(c.subjects||[]).length));dateSlots=[];let d=new Date(first+'T00:00:00');
    const perDay=Math.max(1,Math.min(12,Number(document.getElementById('papersPerDay').value)||1));
    for(let i=0;i<maxSubjects;i++){if(i>0 && i%perDay===0)d=advanceDate(d,gap,skip);dateSlots.push(isoDate(d));}
    scheduleRows=[];renderGrid();
  }

  function renderGrid(){
    const classes=loadedClasses.filter(c=>(c.subjects||[]).length),noSubjects=loadedClasses.filter(c=>!(c.subjects||[]).length);
    document.getElementById('pickedSummary').innerHTML=`<span class="ex-picked-label">Selected:</span>${loadedClasses.map(c=>`<span class="ex-picked-chip">${esc(c.name)}</span>`).join('')}`;
    document.getElementById('noSubjectWarnings').innerHTML=noSubjects.length?`<div class="ex-note"><i class="fas fa-triangle-exclamation"></i> ${noSubjects.map(c=>`<strong>${esc(c.name)}</strong>`).join(', ')} ${noSubjects.length===1?'has':'have'} no subjects assigned, so ${noSubjects.length===1?'it is':'they are'} not shown.</div>`:'';
    if(!classes.length){examGrid.innerHTML='<tbody><tr><td style="padding:35px;text-align:center;color:var(--text-muted)">No assigned subjects found for the selected semesters.</td></tr></tbody>';scheduleCard.style.display='';return;}
    // Display rows by their actual examination date and starting time. A manually
    // edited exam may be moved to ANY date/time, independently of its default slot.
    const defaultSlots=dateSlots.map((date,index)=>({date,time:slotTime(index),slotIndex:index}));
    const visualSlots=[...defaultSlots];
    scheduleRows.forEach(r=>{
      if(!visualSlots.some(slot=>slot.date===r.exam_date&&slot.time===r.start_time)){
        visualSlots.push({date:r.exam_date,time:r.start_time,slotIndex:null});
      }
    });
    visualSlots.sort((a,b)=>a.date.localeCompare(b.date)||a.time.localeCompare(b.time));
    let html='<thead><tr><th>Date / Slot</th>'+classes.map(c=>`<th>${esc(c.name)}</th>`).join('')+'</tr></thead><tbody>';
    visualSlots.forEach((slot,index)=>{
      const isDefault=slot.slotIndex!==null;
      html+=`<tr><td><div class="ex-grid-date">${prettyDate(slot.date)}</div><div class="ex-grid-slot">${isDefault?'Paper '+(slot.slotIndex+1)+' · ':''}${esc(slot.time)}</div></td>`;
      classes.forEach(cls=>{
        const matches=scheduleRows.map((r,i)=>({r,i})).filter(({r})=>String(r.school_class_id)===String(cls.id)&&r.exam_date===slot.date&&r.start_time===slot.time);
        if(!matches.length){
          html+=`<td><div class="ex-empty-cell" data-add-class="${cls.id}" data-add-slot="${slot.slotIndex??''}" data-add-date="${esc(slot.date)}" data-add-time="${esc(slot.time)}" title="Add examination"><i class="fas fa-plus"></i></div></td>`;
        }else{
          html+='<td>';
          matches.forEach(({r,i})=>{html+=`<div class="ex-exam-cell" data-edit-row="${i}"><span class="ex-cell-edit"><i class="fas fa-pen"></i></span><div class="ex-cell-subject" title="${esc(r.subject_name)}">${esc(r.subject_name)}</div><div class="ex-cell-meta"><i class="fas fa-user"></i> ${r.teacher_name?esc(r.teacher_name):'Teacher not assigned'}<br><i class="far fa-clock"></i> ${esc(r.start_time)} – ${esc(r.end_time)}${r.room?` &nbsp; · &nbsp; <i class="fas fa-door-open"></i> ${esc(r.room)}`:''}</div></div>`;});
          html+='</td>';
        }
      });
      html+='</tr>';
    });
    html+='</tbody>';examGrid.innerHTML=html;
    examGrid.querySelectorAll('[data-add-class]').forEach(el=>el.onclick=()=>openAdd(Number(el.dataset.addClass),el.dataset.addSlot===''?null:Number(el.dataset.addSlot),el.dataset.addDate,el.dataset.addTime));
    examGrid.querySelectorAll('[data-edit-row]').forEach(el=>el.onclick=()=>openEdit(Number(el.dataset.editRow)));
    const totalSubjects=classes.reduce((n,c)=>n+(c.subjects||[]).length,0);document.getElementById('scheduleSummary').innerHTML=`<span><strong>${classes.length}</strong> semester${classes.length===1?'':'s'}</span><span><strong>${scheduleRows.length}</strong> exam${scheduleRows.length===1?'':'s'} added</span><span><strong>${totalSubjects}</strong> available subjects</span><span>Click any <b>+</b> box to add.</span>`;
    scheduleCard.style.display='';syncHiddenRows();scheduleCard.scrollIntoView({behavior:'smooth',block:'start'});
  }

  function teacherOptions(selected){return '<option value="">Select Teacher</option>'+teachers.map(t=>`<option value="${t.id}" ${String(t.id)===String(selected||'')?'selected':''}>${esc(t.name)}</option>`).join('');}
  function subjectOptions(cls,currentSubjectId=''){
    const used=new Set(scheduleRows.filter(r=>String(r.school_class_id)===String(cls.id)&&String(r.subject_id)!==String(currentSubjectId)).map(r=>String(r.subject_id)));
    return '<option value="">Select Subject</option>'+(cls.subjects||[]).filter(s=>!used.has(String(s.subject_id))).map(s=>`<option value="${s.subject_id}" data-teacher-id="${s.teacher_id||''}" data-teacher-name="${esc(s.teacher_name||'')}">${esc(s.subject_name)}</option>`).join('');
  }
  function slotTime(slotIndex){const first=document.getElementById('defaultStart').value||'09:00';const perDay=Math.max(1,Number(document.getElementById('papersPerDay').value)||1);const minutes=Number(document.getElementById('defaultDuration').value||120)+Number(document.getElementById('paperGap').value||30);return addMinutes(first,(slotIndex%perDay)*minutes);}
  function fillCommonModal(cls,slotIndex,row=null){
    const meta=classMeta(cls.id),start=row?.start_time||slotTime(slotIndex),duration=Number(document.getElementById('defaultDuration').value||120);
    document.getElementById('modalProgram').value=meta.program_name||'';document.getElementById('modalClass').value=cls.name||meta.name||'';document.getElementById('modalSubject').innerHTML=subjectOptions(cls,row?.subject_id||'');
    if(row?.subject_id)document.getElementById('modalSubject').value=String(row.subject_id);document.getElementById('modalTeacher').innerHTML=teacherOptions(row?.teacher_id||'');
    document.getElementById('modalDate').value=row?.exam_date||dateSlots[slotIndex]||'';document.getElementById('modalStart').value=start;document.getElementById('modalEnd').value=row?.end_time||addMinutes(start,duration);document.getElementById('modalRoom').value=row?.room??document.getElementById('defaultRoom').value.trim();document.getElementById('modalTotal').value=row?.total_marks??(document.getElementById('defaultTotal').value||100);document.getElementById('modalPass').value=row?.passing_marks??(document.getElementById('defaultPass').value||40);
  }
  function openAdd(classId,slotIndex,date,time){activeClassId=classId;activeSlotIndex=slotIndex;editingRowIndex=null;const cls=loadedClass(classId);if(!cls)return;document.getElementById('modalTitle').textContent='Add Examination';document.getElementById('modalRemove').style.display='none';fillCommonModal(cls,slotIndex??0,null);if(date)document.getElementById('modalDate').value=date;if(time){document.getElementById('modalStart').value=time;document.getElementById('modalEnd').value=addMinutes(time,Number(document.getElementById('defaultDuration').value||120));}modal.classList.add('show');modal.setAttribute('aria-hidden','false');}
  function openEdit(rowIndex){const r=scheduleRows[rowIndex],cls=loadedClass(r.school_class_id);if(!r||!cls)return;activeClassId=r.school_class_id;activeSlotIndex=r.slot_index;editingRowIndex=rowIndex;document.getElementById('modalTitle').textContent='Edit Examination';document.getElementById('modalRemove').style.display='';fillCommonModal(cls,r.slot_index,r);modal.classList.add('show');modal.setAttribute('aria-hidden','false');}
  function closeModal(){modal.classList.remove('show');modal.setAttribute('aria-hidden','true');activeClassId=null;activeSlotIndex=null;editingRowIndex=null;}
  document.getElementById('modalClose').onclick=closeModal;document.getElementById('modalCancel').onclick=closeModal;modal.addEventListener('click',e=>{if(e.target===modal)closeModal();});
  document.getElementById('modalSubject').addEventListener('change',function(){const opt=this.selectedOptions[0];if(!opt||!opt.value)return;const tid=opt.dataset.teacherId||'';document.getElementById('modalTeacher').value=tid;});
  document.getElementById('modalSave').addEventListener('click',()=>{
    const cls=loadedClass(activeClassId),subjectSel=document.getElementById('modalSubject'),subjectId=subjectSel.value,start=document.getElementById('modalStart').value,end=document.getElementById('modalEnd').value,date=document.getElementById('modalDate').value,total=Number(document.getElementById('modalTotal').value),pass=Number(document.getElementById('modalPass').value);
    if(!cls||!subjectId){alert('Please select a subject.');return;}if(!date||!start||!end){alert('Date, start time and end time are required.');return;}if(end<=start){alert('End time must be after start time.');return;}if(pass>total){alert('Passing marks cannot exceed total marks.');return;}
    const teacherSel=document.getElementById('modalTeacher'),row={slot_index:activeSlotIndex,school_class_id:cls.id,class_name:cls.name,subject_id:subjectId,subject_name:subjectSel.selectedOptions[0]?.text||'',teacher_id:teacherSel.value,teacher_name:teacherSel.value?(teacherSel.selectedOptions[0]?.text||''):'',exam_date:date,start_time:start,end_time:end,room:document.getElementById('modalRoom').value.trim(),total_marks:document.getElementById('modalTotal').value,passing_marks:document.getElementById('modalPass').value};
    if(editingRowIndex===null)scheduleRows.push(row);else scheduleRows[editingRowIndex]=row;closeModal();renderGrid();
  });
  document.getElementById('modalRemove').addEventListener('click',()=>{if(editingRowIndex===null)return;if(confirm('Remove this exam from the timetable?')){scheduleRows.splice(editingRowIndex,1);closeModal();renderGrid();}});

  function syncHiddenRows(){hiddenRows.innerHTML='';scheduleRows.forEach((r,n)=>{const fields={school_class_id:r.school_class_id,subject_id:r.subject_id,teacher_id:r.teacher_id,exam_date:r.exam_date,start_time:r.start_time,end_time:r.end_time,room:r.room,total_marks:r.total_marks,passing_marks:r.passing_marks};Object.entries(fields).forEach(([k,v])=>{const i=document.createElement('input');i.type='hidden';i.name=`rows[${n}][${k}]`;i.value=v??'';hiddenRows.appendChild(i);});});}
  document.getElementById('backToSetupBtn').onclick=()=>{scheduleCard.style.display='none';step2Card.scrollIntoView({behavior:'smooth',block:'start'});};
  document.getElementById('scheduleForm').addEventListener('submit',e=>{syncHiddenRows();if(!scheduleRows.length){e.preventDefault();alert('Please add at least one exam by clicking a + box.');}});

  if(oldRows&&Object.keys(oldRows).length){
    const old=Object.values(oldRows);const classIds=[...new Set(old.map(r=>String(r.school_class_id)))];
    fetch(`${classesJsonUrl}?class_ids=${classIds.join(',')}`).then(r=>r.json()).then(data=>{loadedClasses=data.classes||[];const slots=[...new Set(old.filter(r=>r.exam_date).map(r=>r.exam_date+'|'+(r.start_time||'09:00')))].sort();dateSlots=slots.length?slots.map(x=>x.split('|')[0]):[new Date().toISOString().slice(0,10)];scheduleRows=old.map((r,i)=>{const cls=loadedClass(r.school_class_id),sub=(cls?.subjects||[]).find(s=>String(s.subject_id)===String(r.subject_id)),t=teachers.find(x=>String(x.id)===String(r.teacher_id));let slot=slots.indexOf(r.exam_date+'|'+(r.start_time||'09:00'));if(slot<0){dateSlots.push(r.exam_date);slot=dateSlots.length-1;}return {slot_index:slot,school_class_id:r.school_class_id,class_name:cls?.name||`Class #${r.school_class_id}`,subject_id:r.subject_id,subject_name:sub?.subject_name||subjectsAll.find(s=>String(s.id)===String(r.subject_id))?.name||`Subject #${r.subject_id}`,teacher_id:r.teacher_id||'',teacher_name:t?.name||'',exam_date:r.exam_date||'',start_time:r.start_time||'09:00',end_time:r.end_time||'11:00',room:r.room||'',total_marks:r.total_marks||100,passing_marks:r.passing_marks||40};});step2Card.style.display='';scheduleCard.style.display='';renderGrid();});
  }
})();
</script>
@endpush
@endsection
