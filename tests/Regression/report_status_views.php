<?php
/** Limited standalone view fixtures — NOT Laravel Blade, routing or database tests. */
declare(strict_types=1);
require __DIR__.'/report_status_policy.php';
set_error_handler(function(int $s,string $m,string $f,int $l):never{throw new ErrorException($m,0,$s,$f,$l);});
$root=dirname(__DIR__,2);
$out=getenv('ACADRIX_QA_OUTPUT')?:sys_get_temp_dir().'/acadrix-report-status';
if(!is_dir($out))mkdir($out,0770,true);
function escaped(mixed $v):string{return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
function optional($x){return $x ?? new class{function __call($n,$a){return null;}};}
function storage_asset($path){return $path;}
function now(){return new DateTimeImmutable('2026-10-29 14:00:00');}
function reportCompile(string $src):string {
    $src=preg_replace('/\{\{--.*?--\}\}/s','',$src);
    $src=str_replace(['@php','@endphp'],['<?php','?>'],$src);
    $src=preg_replace_callback('/\{\{\s*(.*?)\s*\}\}/s',fn($m)=>'<?php echo escaped('.$m[1].'); ?>',$src);
    $src=preg_replace_callback('/@(include|foreach|forelse|elseif|if|for|switch|case|isset|unless|json|selected|checked|disabled|section|extends|push|method)\s*\(/',fn($m)=>'@'.$m[1].'(',$src);
    foreach(['include','foreach','forelse','elseif','if','for','switch','case','isset','unless','json','selected','checked','disabled','section','extends','push','method'] as $d){
        $offset=0;
        while(($start=strpos($src,'@'.$d.'(',$offset))!==false){
            $open=$start+strlen($d)+1;$depth=1;$quote=null;$end=$open+1;
            for(;$end<strlen($src);$end++){
                $c=$src[$end];if($quote!==null){if($c==='\\')$end++;elseif($c===$quote)$quote=null;continue;}
                if($c==='"'||$c==="'"){$quote=$c;continue;}
                if($c==='(')$depth++;if($c===')'&&--$depth===0)break;
            }
            if($depth!==0)throw new RuntimeException('Unclosed '.$d);
            $e=substr($src,$open+1,$end-$open-1);
            $loopStart='$__qaLoops[] = 0; foreach ('.$e.'): $loop=(object)["iteration"=>++$__qaLoops[count($__qaLoops)-1],"index"=>$__qaLoops[count($__qaLoops)-1]-1];';
            $rep=match($d){
                'include'=>'<?php echo reportRender('.$e.'); ?>',
                'foreach'=>'<?php '.$loopStart.' ?>',
                'forelse'=>'<?php $__qaEmpty[]=true; '.$loopStart.' $__qaEmpty[count($__qaEmpty)-1]=false; ?>',
                'json'=>'<?php echo json_encode('.$e.'); ?>',
                'selected','checked','disabled'=>'<?php if('.$e.') echo "'.$d.'"; ?>',
                'case'=>'<?php case '.$e.': ?>',
                'unless'=>'<?php if(!('.$e.')): ?>',
                'section','extends','push','method'=>'',
                default=>'<?php '.$d.'('.$e.'): ?>',
            };
            $src=substr_replace($src,$rep,$start,$end-$start+1);$offset=$start+strlen($rep);
        }
    }
    $loopEnd='endforeach; array_pop($__qaLoops); $loop=(object)["iteration"=>(end($__qaLoops)?:0),"index"=>max(0,(end($__qaLoops)?:0)-1)];';
    $src=str_replace(['@endforelse','@endforeach','@empty','@elseif','@else','@endif','@endfor','@endswitch','@default','@break','@endisset','@endunless','@endsection','@endpush','@once','@endonce','@csrf'],
        ['<?php endif; ?>','<?php '.$loopEnd.' ?>','<?php '.$loopEnd.' if(array_pop($__qaEmpty)): ?>','@elseif','<?php else: ?>','<?php endif; ?>','<?php endfor; ?>','<?php endswitch; ?>','<?php default: ?>','<?php break; ?>','<?php endif; ?>','<?php endif; ?>','','','','',''],$src);
    $src=preg_replace('/(<\?php switch\([^?]*?: \?>)\s+(<\?php case)/','$1$2',$src);
    token_get_all($src,TOKEN_PARSE);return $src;
}
function reportRender(string $view,array $data=[]):string{
    global $root;
    $compiled=reportCompile(file_get_contents($root.'/resources/views/'.str_replace('.','/',$view).'.blade.php'));
    extract($data,EXTR_SKIP);$__qaLoops=[];$__qaEmpty=[];
    ob_start();try{eval('?>'.$compiled);return ob_get_clean();}catch(Throwable $e){ob_end_clean();throw $e;}
}
final class PreviewRows implements IteratorAggregate,Countable{
    public function __construct(public array $items=[]){}
    public function getIterator():Traversable{return new ArrayIterator($this->items);}
    public function count():int{return count($this->items);}
    public function isEmpty():bool{return !$this->items;}
    public function isNotEmpty():bool{return !!$this->items;}
    public function get($key){return $this->items[$key]??null;}
    public function sum($key){return array_sum(array_map(fn($x)=>is_array($x)?$x[$key]:$x->$key,$this->items));}
}
$baseCount=$checks;
foreach([...\App\Support\StudentStatus::VALUES,null,'<script>alert(1)</script>'] as $status){
    $html=reportRender('partials.report-student-status',['statusStudent'=>new \App\Models\Student(['status'=>$status])]);
    check(str_contains($html,'<strong>'.\App\Support\StudentStatus::label($status).'</strong>'),'real partial renders current '.($status??'null'));
    check(!str_contains($html,'<script>'),'status partial escapes unknown data');
}
$student=new \App\Models\Student(['status'=>'left']);
check(str_contains(reportRender('partials.report-student-status',['statusStudent'=>$student]),'<strong>Left</strong>'),'left appears before reactivation');
$student->status='active';
check(str_contains(reportRender('partials.report-student-status',['statusStudent'=>$student]),'<strong>Active</strong>'),'fresh report reflects reactivation');
check(str_contains(reportRender('partials.report-student-status',['statusStudent'=>null]),'Unknown'),'orphan report row never labelled active');
// Render the actual question-paper template. No Setting model is available, so any branding lookup fails this fixture.
$exam=(object)['name'=>'Mid Term Exam — Fall 2026','program'=>(object)['name'=>'BS (Hons.) Human Nutrition and Dietetics'],
    'session_label'=>'2024–2028','schoolClass'=>(object)['section_label'=>'Semester 5'],
    'subject'=>(object)['name'=>'Micronutrients in Human Nutrition'],'exam_date'=>new DateTimeImmutable('2026-12-02'),
    'time_label'=>'10:00 AM – 12:30 PM','total_marks'=>20];
$paper=(object)['examination'=>$exam,'teacher'=>(object)['name'=>'Sample Teacher'],'instructions'=>'Attempt all questions. Read each question carefully before answering.'];
function questions(array $text,float $marks):PreviewRows{return new PreviewRows(array_map(fn($s)=>(object)['question'=>$s,'marks'=>$marks,'image_path'=>null,'options'=>[]],$text));}
$paperData=['examPaper'=>$paper,'shortQuestions'=>questions(['Define food security and identify its major dimensions.','Differentiate between malnutrition and hidden hunger.','List two causes of post-harvest food losses.','Explain how purchasing power can influence food choices.','Identify two factors that may affect food availability.'],2),
 'longQuestions'=>questions(['Discuss the relationship between food availability, access and utilization. Support your answer with suitable examples.'],10),
 'mcqQuestions'=>new PreviewRows,'fillBlankQuestions'=>new PreviewRows,'trueFalseQuestions'=>new PreviewRows];
$html=reportRender('exam-papers.print',$paperData);
check(!str_contains($html,'inst-logo-row')&&!str_contains($html,'<img'),'unbranded paper renders without logo');
check(substr_count($html,'Mid Term Exam')===1,'single exam heading');
foreach(['Program','Session','Semester 5','Micronutrients','Total Marks','Short Questions','Long Questions'] as $term)check(str_contains($html,$term),'paper retains '.$term);
check(!str_contains($html,'Sample Teacher')&&!str_contains($html,'Exam Date')&&!str_contains($html,'>Time<'),'unrequested exam metadata removed');
$html=str_replace('<div class="sheet-inner">','<div class="sheet-inner"><p style="font:9px Arial;text-align:right;color:#666;margin:0 0 8px">LAYOUT PREVIEW — SAMPLE QUESTIONS ONLY</p>',$html);
file_put_contents($out.'/paper.html',$html);
$paperData['shortQuestions']=questions([],2);$paperData['longQuestions']=questions([],10);
check(str_contains(reportRender('exam-papers.print',$paperData),'No questions have been added'),'empty paper renders');
$paperData['shortQuestions']=questions(['Question with a diagram.'],2);$paperData['shortQuestions']->items[0]->image_path='data:image/png;base64,PREVIEW';
check(str_contains(reportRender('exam-papers.print',$paperData),'data:image/png;base64,PREVIEW'),'question diagrams are retained while branding removed');
// Actual report table partial, with synthetic students in every enrollment status.
$rows=[];
foreach(\App\Support\StudentStatus::VALUES as $i=>$status){
 $s=new \App\Models\Student(['name'=>'Example Student '.($i+1),'student_id'=>'PREVIEW-'.str_pad((string)($i+1),3,'0',STR_PAD_LEFT),'status'=>$status,
   'father_name'=>'Example Parent','program'=>(object)['name'=>'Sample Program'],'schoolClass'=>(object)['section_label'=>'Semester 5']]);
 $paid=in_array($status,['active','left'],true);
 $rows[]=['student'=>$s,'total_fee'=>50000,'total_installments'=>2,'installments'=>new PreviewRows([
    1=>['pending'=>$paid?0:10000,'total'=>25000,'due_date'=>new DateTimeImmutable('2026-09-01'),'status'=>$paid?'Paid':'Overdue'],
    2=>['pending'=>$paid?0:25000,'total'=>25000,'due_date'=>new DateTimeImmutable('2026-12-01'),'status'=>$paid?'Paid':'Due'],
 ]),'total_paid'=>$paid?50000:15000,'total_pending'=>$paid?0:35000,'outstanding'=>$paid?0:10000,'fee_status'=>$paid?'paid':'unpaid','remarks'=>'Synthetic preview'];
}
$table=reportRender('admin.reporting.partials.fee-plan-print-table',['rows'=>$rows,'maxInstallments'=>2]);
foreach(\App\Support\StudentStatus::VALUES as $status)check(str_contains($table,'<strong>'.ucfirst($status).'</strong>'),'fee print row status '.$status);
check(str_contains($table,'Fee Status'),'fee status separate from enrollment');
check(substr_count($table,'report-student-status')===8,'one status label per report row');
$styles=file_get_contents($root.'/resources/views/admin/reporting/partials/report-print-style.blade.php');
$html='<!doctype html><html lang="en"><head><meta charset="utf-8"><title>Report Status Preview</title>'.$styles.'<style>@page{size:A4 landscape;margin:10mm}.sheet{max-width:1250px}table.rpt{font-size:10px!important}table.rpt th{font-size:9px}td{vertical-align:top} .preview-note{font:11px Arial;color:#555;margin:0 0 12px}h1{font-size:22px;color:#8b0c22;margin:0 0 10px}@media print{.sheet-inner{padding:18px}.sheet{max-width:none}}</style></head><body><div class="sheet"><div class="sheet-inner"><p class="preview-note">LAYOUT PREVIEW — SYNTHETIC RECORDS — NOT A LIVE REPORT</p><h1>Student Status in Fee Reports</h1><p>Current enrollment status is displayed below each student’s name. Fee Status and all financial amounts remain separate.</p>'.$table.'<p class="preview-note">The student marked Left can also have Paid fees. Reactivation changes the enrollment label, not the fee history.</p></div></div></body></html>';
file_put_contents($out.'/status-report.html',$html);
// A real dependency-free PDF table uses the same headers/widths as the changed controller.
require $root.'/app/Services/SimplePdfDocument.php';
$source=file_get_contents($root.'/app/Http/Controllers/ReportController.php');
$m=[];
// Avoid PHP variable interpolation in the matcher above.
if(!$m){$line=trim(array_values(array_filter(explode("\n",$source),fn($l)=>str_contains($l,"->table(['ID', 'Student', 'Student Status'")))[0]);$code=str_replace('->table(','',$line);$code=substr($code,0,-1);[$h,$w]=explode(', $perStudent, ',$code);$headers=eval('return '.$h.';');$widths=eval('return '.$w.';');}else{$headers=eval('return '.$m[1].';');$widths=eval('return '.$m[2].';');}
$pdf=new \App\Services\SimplePdfDocument('landscape');$pdf->title('Attendance report layout preview')->subtitle('Synthetic status data only');
$pdfRows=[];foreach(\App\Support\StudentStatus::VALUES as $i=>$status)$pdfRows[]=['PREVIEW-'.($i+1),'Example Student',ucfirst($status),'Sample Program','Semester 5','2024-2028',10,8,1,0,1,'80%'];
$pdf->table($headers,$pdfRows,$widths);
file_put_contents($out.'/attendance-status.pdf',$pdf->output());
check($widths[array_search('Student Status',$headers,true)]>=14,'PDF header and longest status labels not truncated');
echo 'PASS: '.($checks-$baseCount)." limited-renderer view and print-fixture checks.\nOutput: $out\n";
