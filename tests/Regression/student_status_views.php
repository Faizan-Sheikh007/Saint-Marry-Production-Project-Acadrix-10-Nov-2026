<?php
/** Limited Blade fixture renderer, using the real updated row/badge/banner views.
 * Not the Laravel compiler, and no database or live student data is used.
 */
declare(strict_types=1);
require __DIR__.'/student_status_policy.php';
function auth(string $guard='web'){return \Illuminate\Support\Facades\Auth::guard($guard);}
function storage_asset($path){return '/unused-fixture-photo';}
function route($name,$args=[]){return '/fixture/'.$name.'/'.(is_object($args)?$args->id:'1');}
function esc($v){return htmlspecialchars((string)$v,ENT_QUOTES|ENT_SUBSTITUTE,'UTF-8');}
class FixtureStudent extends \App\Models\Student {public function pendingDues():float{return 0.0;}}
function compileStatusView(string $s):string {
 $s=preg_replace('/\{\{--.*?--\}\}/s','',$s);
 $s=str_replace(['@php','@endphp'],['<?php','?>'],$s);
 $s=preg_replace_callback('/\{\{\s*(.*?)\s*\}\}/s',fn($m)=>'<?php echo esc('.$m[1].'); ?>',$s);
 foreach(['include','disabled','selected','foreach','elseif','if','method','json'] as $directive){
  $offset=0;
  while(($start=strpos($s,'@'.$directive.'(',$offset))!==false){
   $open=$start+strlen($directive)+1;$depth=1;$quote=null;$end=$open+1;
   for(;$end<strlen($s);$end++){
    $c=$s[$end];if($quote!==null){if($c==='\\')$end++;elseif($c===$quote)$quote=null;continue;}
    if($c==='"'||$c==="'"){$quote=$c;continue;}
    if($c==='(')$depth++;if($c===')'&&--$depth===0)break;
   }
   if($depth!==0)throw new RuntimeException('Unclosed '.$directive);
   $e=substr($s,$open+1,$end-$open-1);
   $replacement=match($directive){
    'include'=>'<?php echo renderStatusView('.$e.'); ?>',
    'disabled'=>'<?php if ('.$e.') echo "disabled"; ?>',
    'selected'=>'<?php if ('.$e.') echo "selected"; ?>',
    'method'=>'<?php echo \'<input type="hidden" name="_method" value="\'.esc('.$e.').\'">\'; ?>',
    'json'=>'<?php echo json_encode('.$e.'); ?>',
    default=>'<?php '.$directive.' ('.$e.'): ?>',
   };
   $s=substr_replace($s,$replacement,$start,$end-$start+1);$offset=$start+strlen($replacement);
  }
 }
 $s=str_replace(['@endforeach','@else','@endif','@csrf'],['<?php endforeach; ?>','<?php else: ?>','<?php endif; ?>','<input type="hidden" name="_token" value="fixture-token">'],$s);
 token_get_all($s,TOKEN_PARSE);return $s;
}
function renderStatusView(string $v,array $data=[]):string {
 $path=dirname(__DIR__,2).'/resources/views/'.str_replace('.','/',$v).'.blade.php';
 $compiled=compileStatusView(file_get_contents($path));extract($data,EXTR_SKIP);ob_start();
 try{eval('?>'.$compiled);return ob_get_clean();}catch(Throwable $e){ob_end_clean();throw $e;}
}
$fixtureRoot=dirname(__DIR__,2);
$out=getenv('ACADRIX_QA_OUTPUT')?:sys_get_temp_dir().'/acadrix-student-status';
if(!is_dir($out))mkdir($out,0770,true);
// Status-policy test has restored an admin guard; add isAdmin to a fixture actor.
$fixtureAdmin=new class {public function isAdmin(){return true;}};
\Illuminate\Support\Facades\Auth::$guards['web']=new GuardDouble($fixtureAdmin);
$css=preg_replace('/^@import[^\r\n]*$/m','',file_get_contents($fixtureRoot.'/public/css/app.css'));
$base=['student_id'=>'STU-PREVIEW-0740','name'=>'XYZ','id'=>1,'fee_status'=>'paid','credit_balance'=>'5000.00','email'=>null,'phone'=>'—','father_name'=>'—','guardian_name'=>null,'roll_number'=>null,'photo'=>null,'session'=>'2024–2028',
 'program'=>(object)['name'=>'BS (Hons.) Human Nutrition and Dietetics'], 'schoolClass'=>(object)['name'=>'Semester 5']];
$left=new FixtureStudent($base+['status'=>'left']);$active=new FixtureStudent(array_replace($base,['id'=>2,'student_id'=>'STU-PREVIEW-0741','name'=>'Example Active Student','status'=>'active']));
$leftRow=renderStatusView('students._row',['student'=>$left,'sr'=>1]);$activeRow=renderStatusView('students._row',['student'=>$active,'sr'=>2]);
$banner=renderStatusView('partials.student-status-banner',['statusStudent'=>$left]);
$accountBanner='';\Illuminate\Support\Facades\Auth::$guards['web']=new GuardDouble;
$accountBanner=renderStatusView('partials.student-status-banner',['statusStudent'=>$left]);
\Illuminate\Support\Facades\Auth::$guards['web']=new GuardDouble($fixtureAdmin);
check(str_contains($leftRow,'data-student-status="left"'),'row carries actual left');
check(str_contains($leftRow,'Profile disabled'),'visible disabled annotation');
check(!str_contains($leftRow,'/fixture/students.edit/'),'left edit URL removed');
check(!str_contains($leftRow,'/fixture/students.id-card/'),'left ID card URL removed');
check(str_contains($leftRow,'> Reactivate</button>'),'row reactivation visible');
check(str_contains($activeRow,'/fixture/students.edit/'),'active edit retained');
check(!str_contains($activeRow,'class="student-row-locked"'),'active row not locked');
check(str_contains($banner,'Student status: Left'),'banner exact left');
check(str_contains($banner,'Reactivate Student'),'admin banner reactivation');
check(!str_contains($accountBanner,'Reactivate Student'),'non-admin banner cannot reactivate');
$headers='<tr><th>SR #</th><th>Student ID</th><th>Photo</th><th>Roll Number</th><th>Name</th><th>Father Name</th><th>Program</th><th>Contact</th><th>Pending Balance</th><th>Student Status</th><th>Fees Status</th><th>Action</th></tr>';
$head='<!doctype html><html><head><base href="https://preview.invalid/"><meta charset="utf-8"><title>Acadrix status fixture preview</title><style>'.$css.'
body{background:#f5f6f8;padding:32px;font-family:Arial,sans-serif}.fixture-header{display:flex;justify-content:space-between;align-items:center;margin-bottom:22px}.fixture-header h1{font-size:26px}.fixture-note{font-size:12px;color:#5e6878}.students-table th{font-size:11px;padding:12px;white-space:nowrap}.students-table td{font-size:12px;padding:14px 12px;vertical-align:middle}.students-table td div{line-height:1.4}.students-table{min-width:1400px}button:disabled,input:disabled{opacity:.38;cursor:not-allowed} .btn-sm{padding:7px 10px} .student-status-badge{display:inline-flex;gap:4px}.fixture-header strong{color:#8b0a29} .page-content{padding:0!important}.fixture-foot{margin-top:16px;color:#657184;font-size:12px}.badge.danger{background:#fee2e2;color:#a40025}.badge.success{background:#dcfce7;color:#087a52}.fixture-card{padding:20px;margin:16px 0}
.fas{font-style:normal;font-family:Arial,sans-serif}.fa-eye::before{content:"◉"}.fa-edit::before{content:"✎"}.fa-id-card::before{content:"▤"}.fa-lock::before{content:"▣"}.fa-play::before{content:"▶"}.fa-ellipsis-vertical::before{content:"⋮"}
</style></head><body>';
$opening='<div class="fixture-header"><div><strong>ACADRIX</strong><h1>Students</h1></div><div class="fixture-note">LAYOUT PREVIEW · SYNTHETIC DATA · NOT A LIVE SCREEN</div></div>';
file_put_contents($out.'/directory.html',$head.$opening.'<div class="card"><div class="table-wrap"><table class="students-table"><thead>'.$headers.'</thead><tbody>'.$leftRow.$activeRow.'</tbody></table></div></div><div class="fixture-foot">Enrollment status and fee status are separate: a student can be Left and still have Paid fees. Read-only View stays available.</div></body></html>');
$layout=file_get_contents($fixtureRoot.'/resources/views/layouts/app.blade.php');
preg_match('~<script>\n// Lock already-rendered mutation controls.*?</script>~s',$layout,$scripts);
$profile=$head.$opening.'<main class="page-content">'.$banner.'<div class="card fixture-card"><h2>XYZ · STU-PREVIEW-0740</h2><p>BS (Hons.) Human Nutrition and Dietetics · Semester 5</p><p><strong>Outstanding: Rs.0 · Available credit: Rs.5,000</strong></p><p>Existing balances and records are retained.</p><a class="btn btn-outline" href="/admin/students/1/edit">Edit Profile</a> <a class="btn btn-outline" href="/accounts/vouchers/11/print">Print Existing Receipt</a><form method="POST" action="/accounts/students/1/receive" style="margin-top:20px"><label>Payment amount <input name="amount" value="1000"></label> <button class="btn btn-primary" type="submit">Receive Payment</button></form></div></main>'.($scripts[0]??'').'</body></html>';
file_put_contents($out.'/profile.html',$profile);
file_put_contents($out.'/account-banner.html',$head.$accountBanner.'</body></html>');
echo "PASS: 10 row/banner view-fixture checks; real source views via LIMITED translator, not Laravel Blade.\n";
echo "Output: $out\n";
