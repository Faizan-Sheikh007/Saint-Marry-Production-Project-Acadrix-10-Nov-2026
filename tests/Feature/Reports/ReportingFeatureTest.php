<?php

namespace Tests\Feature\Reports;

use App\Models\{Attendance, DailyLedgerEntry, Payment, SchoolClass, StudentReportRemark, Teacher, User};
use Illuminate\Support\Facades\{Auth, DB};

class ReportingFeatureTest extends ReportsTestCase
{
    public function test_attendance_has_late_leave_no_data_and_latest_duplicate_semantics(): void
    {
        $s=$this->student();$empty=$this->student();
        foreach([['01','present'],['01','absent'],['02','late'],['03','leave'],['04','present']] as [$day,$status])
            Attendance::create(['student_id'=>$s->id,'school_class_id'=>$this->class->id,'date'=>'2026-09-'.$day,'status'=>$status]);
        $data=$this->report('attendance',['attendance_scope'=>'general']);
        $row=$data['rows']->firstWhere('_student_id',$s->id);
        $this->assertSame(4,$row['sessions']);$this->assertSame(50.0,$row['attendance_rate']);
        $this->assertNull($data['rows']->firstWhere('_student_id',$empty->id)['attendance_rate']);
        $filtered=$this->report('attendance',['attendance_status'=>'absent']);
        $this->assertCount(1,$filtered['rows']);$this->assertSame(50.0,$filtered['rows']->first()['attendance_rate']);
        $details=$this->report('attendance',['view'=>'details','attendance_status'=>'absent']);
        $this->assertCount(1,$details['rows']);$this->assertSame('absent',$details['rows']->first()['attendance_status']);
    }

    public function test_multiple_results_are_weighted_and_cancelled_unmarked_excluded(): void
    {
        $s=$this->student();$this->result($s,60,100);$this->result($s,40,50);
        $this->result($s,null,100);$this->result($s,100,100,['status'=>'cancelled']);
        $row=$this->report('students')['rows']->first();
        $this->assertSame(2,$row['exam_count']);$this->assertSame(66.67,$row['exam_average']);
        $this->assertSame(0,$row['failed_exams']);
    }

    public function test_authoritative_fees_ignore_superseded_rows_and_bundle_each_challan_once(): void
    {
        $s=$this->student();$old=$this->plan($s);$new=$this->plan($s);
        $this->voucher($s,['fee_assignment_id'=>$old->id,'cycle_no'=>1,'amount'=>900]);
        $this->voucher($s,['fee_assignment_id'=>$new->id,'cycle_no'=>1,'installment_no'=>1,'voucher_no'=>'BUNDLE','amount'=>100,'paid_amount'=>40]);
        $this->voucher($s,['fee_assignment_id'=>$new->id,'cycle_no'=>1,'installment_no'=>1,'voucher_no'=>'BUNDLE','amount'=>50]);
        $this->voucher($s,['fee_assignment_id'=>$new->id,'cycle_no'=>1,'installment_no'=>1,'voucher_no'=>'SPLIT','amount'=>50]);
        $this->voucher($s,['amount'=>20]);
        $this->voucher($s,['amount'=>700,'fee_template_id'=>1]);
        $this->voucher($s,['amount'=>300,'status'=>'cancelled']);
        $row=$this->report('students')['rows']->first();
        $this->assertSame(220.0,$row['charged']);$this->assertSame(40.0,$row['paid']);$this->assertSame(180.0,$row['pending']);
        $installments=$this->report('installments',['invoice_status'=>'all']);
        $this->assertCount(2,$installments['rows']);
        $this->assertSame(150.0,$installments['rows']->firstWhere('voucher_no','BUNDLE')['charged']);
        $class=$this->report('class-revenue')['rows']->first();
        $this->assertEquals($row['pending'],$class['pending']);
    }

    public function test_collection_uses_whole_end_date_and_verified_payments_only(): void
    {
        $s=$this->student();$v=$this->voucher($s);
        foreach([['2026-09-08 00:00:00','verified',20],['2026-09-08 23:59:59',null,30],['2026-09-09 00:00:00','verified',40],['2026-09-08 10:00:00','pending',50],['2026-09-08 11:00:00','rejected',60]] as [$date,$status,$amount])
            Payment::create(['voucher_id'=>$v->id,'paid_at'=>$date,'status'=>$status,'amount'=>$amount,'method'=>'cash']);
        $data=$this->report('collection',['from'=>'2026-09-08','to'=>'2026-09-08']);
        $this->assertCount(2,$data['rows']);$this->assertEquals(50,$data['rows']->sum('amount'));
        $all=$this->report('collection',['from'=>'2026-09-08','to'=>'2026-09-08','payment_status'=>'all']);
        $this->assertCount(4,$all['rows']);$this->assertEquals(50,collect($all['stats'])->firstWhere('label','Verified collection')['value']);
    }

    public function test_defaulter_aging_is_by_each_item_and_excludes_due_today(): void
    {
        $s=$this->student();
        $this->voucher($s,['amount'=>100,'due_date'=>today()->subDays(10)]);
        $this->voucher($s,['amount'=>200,'due_date'=>today()->subDays(45)]);
        $this->voucher($s,['amount'=>300,'due_date'=>today()->subDays(91)]);
        $this->voucher($s,['amount'=>400,'due_date'=>today()]);
        $this->voucher($s,['amount'=>500,'paid_amount'=>500,'due_date'=>today()->subDays(50),'status'=>'unpaid']);
        $row=$this->report('defaulters')['rows']->first();
        $this->assertEquals(600,$row['overdue']);$this->assertEquals(100,$row['age_1_30']);$this->assertEquals(200,$row['age_31_60']);$this->assertEquals(300,$row['age_90_plus']);
        $this->assertEquals(200,$this->report('defaulters',['aging'=>'31_60'])['rows']->first()['overdue']);
    }

    public function test_discount_only_plans_are_included_and_current_scope_excludes_old_plans(): void
    {
        $s=$this->student();$this->plan($s,['scholarship_amount'=>900]);$new=$this->plan($s,['discount_amount'=>75]);
        $rows=$this->report('discounts')['rows'];
        $this->assertCount(1,$rows);$this->assertSame($new->id,$rows->first()['plan_id']);$this->assertSame(75.0,$rows->first()['relief']);
        $this->assertCount(2,$this->report('discounts',['plan_scope'=>'all'])['rows']);
    }

    public function test_income_expense_uses_actual_ledger_expenses_without_duplicate_income(): void
    {
        $s=$this->student();$v=$this->voucher($s);
        foreach([['2026-08-01',999],['2026-08-20',100],['2026-09-02',999]] as [$date,$amount])
            Payment::create(['voucher_id'=>$v->id,'paid_at'=>$date,'amount'=>$amount,'status'=>'verified','method'=>'cash']);
        foreach([['2026-08-20','in',100],['2026-08-20','expense',30],['2026-08-20','out',20],['2026-08-01','expense',999]] as [$date,$type,$amount])
            DailyLedgerEntry::create(['entry_date'=>$date,'type'=>$type,'amount'=>$amount]);
        $row=$this->report('income-expense',['from'=>'2026-08-15','to'=>'2026-08-25'])['rows']->first();
        $this->assertSame(100.0,$row['income']);$this->assertSame(100.0,$row['ledger_in']);$this->assertSame(30.0,$row['expense']);$this->assertSame(70.0,$row['surplus']);$this->assertSame(50.0,$row['net']);
    }

    public function test_csv_matches_filters_columns_and_includes_every_page(): void
    {
        for($i=0;$i<25;$i++)$this->student(['name'=>$i===0?'=DANGEROUS()':'QA Student '.$i]);
        $response=$this->get(route('admin.reports.export',['type'=>'students','columns'=>['student_code','name'],'per_page'=>20]));
        $response->assertOk()->assertHeader('Content-Type','text/csv; charset=UTF-8');
        $csv=$response->streamedContent();
        $this->assertStringContainsString("'=DANGEROUS()",$csv);
        $this->assertCount(26,array_filter(explode("\n",trim($csv))));
        $this->assertStringContainsString('Student ID,Student',str_replace('"','',$csv));
    }

    public function test_invalid_dates_and_unknown_report_types_do_not_return_server_errors(): void
    {
        $this->getJson(route('admin.reports.export',['type'=>'students','from'=>'2026-09-08','to'=>'2026-09-01']))->assertStatus(422)->assertJsonValidationErrors('to');
        $this->getJson(route('admin.reports.export',['type'=>'not-a-report']))->assertNotFound();
        $this->getJson(route('admin.reports.export',['type'=>'students','min_attendance'=>90,'max_attendance'=>20]))->assertStatus(422);
    }

    public function test_teacher_is_scoped_and_remark_author_cannot_be_spoofed(): void
    {
        $s=$this->student();$otherClass=SchoolClass::forceCreate(['name'=>'Other class','program_id'=>$this->program->id]);$other=$this->student(['school_class_id'=>$otherClass->id]);
        $user=User::forceCreate(['name'=>'QA Teacher','email'=>'qa-teacher@example.test','password'=>'test-password','role'=>'teacher']);
        $teacher=Teacher::forceCreate(['user_id'=>$user->id,'name'=>'QA Teacher']);
        $fake=Teacher::forceCreate(['name'=>'Other teacher']);
        DB::table('class_subject')->insert(['school_class_id'=>$this->class->id,'subject_id'=>$this->subject->id,'teacher_id'=>$teacher->id]);
        $this->actingAs($user,'web');
        $this->get(route('teacher.reports.students.print',$other))->assertForbidden();
        $this->assertCount(1,$this->report('students')['rows']);
        $this->post(route('teacher.reports.students.remarks.store',$s),['remark_date'=>'2026-09-08','category'=>'academic','remarks'=>'Manual note','teacher_id'=>$fake->id,'subject_id'=>$this->subject->id])->assertRedirect();
        $note=StudentReportRemark::first();$this->assertSame($teacher->id,$note->teacher_id);$this->assertSame($user->id,$note->created_by);
    }

    public function test_accounts_can_read_but_has_no_academic_remark_write_route(): void
    {
        $s=$this->student();$accountant=User::forceCreate(['name'=>'QA Accounts','email'=>'qa-accounts@example.test','password'=>'test-password','role'=>'accountant']);
        Auth::guard('web')->logout();$this->actingAs($accountant,'accounts');
        $this->get(route('accounts.reports.students.print',$s))->assertOk();
        $this->post('/accounts/reports/students/'.$s->id.'/remarks',['remarks'=>'Forbidden'])->assertNotFound();
        $this->assertSame(0,StudentReportRemark::count());
    }

    public function test_printed_remarks_are_escaped_and_remain_in_history(): void
    {
        $s=$this->student();
        foreach(['<script>alert(1)</script>','Correction retained'] as $text)
            $this->post(route('admin.reports.students.remarks.store',$s),['remark_date'=>'2026-09-08','category'=>'general','remarks'=>$text])->assertRedirect();
        $this->assertSame(2,StudentReportRemark::count());
        $response=$this->get(route('admin.reports.students.print',$s));
        $response->assertOk()->assertSee('&lt;script&gt;alert(1)&lt;/script&gt;',false)->assertDontSee('<script>alert(1)</script>',false)->assertSee('Correction retained');
    }

    public function test_list_and_individual_pdf_endpoints_return_real_pdf_files(): void
    {
        if(!class_exists(\Barryvdh\DomPDF\Facade\Pdf::class))$this->markTestSkipped('Install barryvdh/laravel-dompdf before running PDF integration tests.');
        $s=$this->student();$this->result($s);$this->voucher($s);
        foreach([route('admin.reports.pdf',['type'=>'students']),route('admin.reports.students.pdf',$s),route('admin.reports.attendance.pdf',$s)] as $url){
            $response=$this->get($url);$response->assertOk()->assertHeader('Content-Type','application/pdf');
            $this->assertStringStartsWith('%PDF-',$response->getContent());
            $this->assertStringContainsString('attachment;',$response->headers->get('Content-Disposition'));
        }
    }
}
