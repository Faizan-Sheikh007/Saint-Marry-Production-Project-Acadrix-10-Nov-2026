<?php
/** Isolated executable regression checks. No Laravel bootstrap, SQL or live records. */
declare(strict_types=1);
namespace App\Http\Controllers { class Controller {} }
namespace Illuminate\Http {
    class Request {
        public function __construct(private array $data = []) {}
        public function input(string $key, mixed $default=null): mixed { return $this->data[$key] ?? $default; }
    }
}
namespace App\Models {
    class Student extends \stdClass {
        public function __construct(array $values=[]) { foreach($values as $k=>$v)$this->$k=$v; }
        public function statusLabel(): string { return \App\Support\StudentStatus::label($this->status ?? null); }
        public function isActiveEnrollment(): bool { return \App\Support\StudentStatus::isActive($this->status ?? null); }
        public function totalPaid():float { return 5000; }
        public function pendingDues():float { return 8000; }
    }
}
namespace {
    $root=dirname(__DIR__,2);
    require $root.'/app/Support/StudentStatus.php';
    require $root.'/app/Http/Controllers/GeneralStudentReportController.php';
    $checks=0;
    function check(bool $ok,string $label):void { global $checks; $checks++; if(!$ok)throw new \RuntimeException('FAIL: '.$label); }
    $controller=new \App\Http\Controllers\GeneralStudentReportController;
    $columns=new \ReflectionMethod($controller,'columns');
    $value=new \ReflectionMethod($controller,'value');
    foreach([[],['columns'=>[]],['columns'=>['name']],['columns'=>['status','name','status']],['columns'=>'bad'],['columns'=>['invalid']]] as $request) {
        $selected=$columns->invoke($controller,new \Illuminate\Http\Request($request));
        check(count(array_keys($selected,'status',true))===1,'exactly one compulsory status column');
        check(count($selected)===count(array_unique($selected)),'no duplicate columns');
    }
    foreach(\App\Support\StudentStatus::VALUES as $status) {
        $s=new \App\Models\Student(['status'=>$status,'fee_status'=>'paid','credit_balance'=>'5000.00']);
        $before=serialize($s);
        check($value->invoke($controller,$s,'status')===ucfirst($status),'general exports show saved '. $status);
        check(serialize($s)===$before,'reading status does not mutate '. $status);
    }
    foreach([null,'','nonsense'] as $status)check($value->invoke($controller,new \App\Models\Student(['status'=>$status]),'status')==='Unknown','missing/invalid status never assumed active');
    // Evaluate the ACTUAL export mapping expressions against explicit fixtures. This is not an HTTP/SQL test.
    $src=file_get_contents($root.'/app/Http/Controllers/ReportController.php');
    $export=substr($src,strpos($src,'public function export('));
    $student=new \App\Models\Student([
        'student_id'=>'PREVIEW-0001','name'=>'Sample Student','program'=>(object)['name'=>'Sample Program'],
        'schoolClass'=>(object)['section_label'=>'Semester 1'],'academicYear'=>(object)['name'=>'2026-2030'],
        'roll_number'=>'01','attendance_total_count'=>1,'attendance_present_count'=>1,'exam_results_count'=>1,'exam_average'=>70,
        'guardian_phone'=>'','phone'=>'','status'=>'left',
    ]);
    $voucher=new class extends \stdClass {
        public function total(){return 13000;} public function balance(){return 8000;} public function autoStatusLabel(){return 'Partial';}
    };
    foreach(['student'=>$student,'voucher_no'=>'VCH-PREVIEW','title'=>'Sample fee','paid_amount'=>5000,'due_date'=>null,'installment_no'=>1,'installment_total'=>2] as $k=>$v)$voucher->$k=$v;
    $attendance=(object)['student'=>$student,'subject'=>(object)['name'=>'Sample subject'],'date'=>null,'status'=>'present','remarks'=>'Retained record'];
    $payment=(object)['student'=>null,'voucher'=>$voucher,'paid_at'=>null,'amount'=>5000,'method'=>'cash','status'=>'verified','reference_no'=>'REF','received_by'=>'Staff','recorded_by'=>null,'id'=>1];
    foreach(['attendance'=>['r'=>$attendance], 'collection'=>['p'=>$payment], 'defaulters'=>['v'=>$voucher], 'installments'=>['v'=>$voucher]] as $type=>$vars) {
        preg_match("~case '".preg_quote($type,'~')."':(.*?)break;~s",$export,$section);
        check(isset($section[1]),'export branch '.$type.' found');
        preg_match('~->map\(fn \(\$[a-z]\) => (\[.*\])\);~',$section[1],$expr);
        preg_match('~\$header = (\[.*\]);~',$section[1],$header);
        check(isset($expr[1],$header[1]),'mapping/header found for '.$type);
        $headers=eval('return '.$header[1].';');
        $statusIndex=array_search('Student Status',$headers,true);
        check($statusIndex!==false,'explicit Student Status export header '.$type);
        $map=function(array $args)use($expr){extract($args);return eval('return '.$expr[1].';');};
        $reference=null;
        foreach(\App\Support\StudentStatus::VALUES as $status) {
            $student->status=$status; $rows=$map($vars);
            check(count($rows)===count($headers),'header/data alignment '.$type.' '.$status);
            check($rows[$statusIndex]===ucfirst($status),'correct exported status '.$type.' '.$status);
            unset($rows[$statusIndex]);
            if($reference===null)$reference=$rows;
            check($reference===$rows,'financial/attendance values independent of student status '.$type.' '.$status);
        }
    }
    // Verify all six student-row CSV types carry the new column.
    foreach(['students','attendance','collection','defaulters','installments','discounts'] as $type){
        preg_match("~case '".$type."':(.*?)break;~s",$export,$m);
        check(str_contains($m[1],"'Student Status'"),'coverage: '.$type.' CSV');
    }
    $general=file_get_contents($root.'/resources/views/reports/general.blade.php');
    check(str_contains($general,"@disabled(\$key === 'status')"),'status column cannot be unchecked in UI');
    check(str_contains($general,'name="student_status"'),'general report status filter visible');
    $att=file_get_contents($root.'/app/Http/Controllers/AttendanceController.php');
    $start=strpos($att,'public function report(');$end=strpos($att,'// ─── Show',$start);
    check(!str_contains(substr($att,$start,$end-$start),"->where('status', 'active')"),'historical attendance no active-only exclusion');
    $save=substr($att,strpos($att,'public function save('),strpos($att,'public function report(')-strpos($att,'public function save('));
    check(str_contains($save,"->where('status', 'active')"),'attendance writes still restricted to active enrollment');
    $result=file_get_contents($root.'/app/Http/Controllers/ResultController.php');
    check(str_contains($result,"->orWhereHas('examResults'"),'historical result roster includes recorded results');
    check(str_contains($result,"\$s['complete'] && \$s['student']->isActiveEnrollment()"),'active ranking unchanged for historical inactive rows');
    $paper=file_get_contents($root.'/resources/views/exam-papers/print.blade.php');
    foreach(['institution_name','institution_logo','inst-logo-row','inst-name','campus_name','sign-block','Main Campus'] as $term) check(!str_contains($paper,$term),'no question-paper branding/signature '.$term);
    foreach(['>Program<','>Session<','>Semester / Part<','>Subject<','>Total Marks<','Question Paper','storage_asset($q->image_path)'] as $term)check(str_contains($paper,$term),'preserved question-paper content '.$term);
    check(!str_contains($paper,'>Teacher<')&&!str_contains($paper,'>Exam Date<')&&!str_contains($paper,'>Time<'),'question-paper omits teacher, exam date, and time');
    check(str_contains(file_get_contents($root.'/resources/views/vouchers/partials/modern-challan.blade.php'),'inst') || str_contains(file_get_contents($root.'/resources/views/vouchers/partials/modern-challan.blade.php'),'institution'),'voucher identity retained');
    echo "PASS: $checks isolated report-status, actual export-mapping and source-contract checks.\n";
    echo "No live Laravel routing, database queries, sessions or actual XLSX export performed.\n";
}
