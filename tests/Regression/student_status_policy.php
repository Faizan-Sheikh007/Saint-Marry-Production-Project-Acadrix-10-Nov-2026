<?php
/**
 * Isolated executable tests of the real status policy, Student helper methods,
 * access middleware and observer, with explicit framework/database doubles.
 * This is NOT an HTTP/database integration test.
 */
declare(strict_types=1);
namespace Illuminate\Database\Eloquent\Factories { trait HasFactory {} }
namespace Illuminate\Database\Eloquent {
    class Model {
        protected array $fixtureAttributes=[];
        public static array $rows=[];
        public function __construct(array $a=[]) {$this->fixtureAttributes=$a;}
        public function __get($k){if(array_key_exists($k,$this->fixtureAttributes))return $this->fixtureAttributes[$k];if(method_exists($this,$k))return $this->$k()->first();return null;}
        public function __set($k,$v){$this->fixtureAttributes[$k]=$v;}
        public function getKey(){return $this->id;}
        public function getAttribute($key){return $this->$key;}
        public function qualifyColumn($k){return 'students.'.$k;}
        public static function whereIn($column,$ids){return new \QueryDouble(array_values(array_filter(self::$rows,fn($s)=>in_array($s->id,$ids,true))));}
        public function belongsTo($type, $key=null){return new \RelationDouble($this->fixtureRelation);}
        public function wasChanged($key){return (bool)$this->changed;}
    }
    class Builder {public function where($c,$v){return $this;}}
}
namespace Symfony\Component\HttpFoundation { class Response {public function __construct(public int $status=200,public array $data=[]){} public function withErrors($e){$this->data['errors']=$e;return $this;}} }
namespace Illuminate\Http {
    class Request {
        public \SessionDouble $sessionObject;
        public function __construct(public string $name,public string $method='POST',public array $parameters=[],public array $body=[],public bool $json=false,public string $path='/admin/test'){$this->sessionObject=new \SessionDouble;}
        public function route($key=null){return $key ? ($this->parameters[$key]??null) : new \RouteDouble($this->name,$this->parameters);}
        public function routeIs($name){return $name===$this->name;}
        public function input($key,$default=null){return $this->body[$key]??$default;}
        public function isMethodSafe(){return in_array($this->method,['GET','HEAD','OPTIONS'],true);}
        public function expectsJson(){return $this->json;}
        public function session(){return $this->sessionObject;}
        public function is($pattern){return str_starts_with(ltrim($this->path,'/'),rtrim($pattern,'*'));}
    }
}
namespace Illuminate\Support\Facades {
    class Auth {public static array $guards=[];public static function guard($g){return self::$guards[$g];}}
    class DB {
        public static int $transactions=0;public static array $deleted=[];
        public static function transaction($f){self::$transactions++;return $f();}
        public static function connection($c){return new self;}
        public function table($t){return new \SessionQueryDouble($t);}
    }
}
namespace Illuminate\Support {class Str {public static function random($n){return str_repeat('x',$n);}}}
namespace Illuminate\Validation {class ValidationException extends \RuntimeException {public static function withMessages($e){return new self(implode(' ',$e));}}}
namespace {
class QueryDouble {
 public static int $locks=0;
 public function __construct(private array $items){}
 public function orderBy($c){return $this;}
 public function lockForUpdate(){self::$locks++;return $this;}
 public function get(){return $this->items;}
}
class RelationDouble {public function __construct(private mixed $value){}public function first(){return $this->value;}}
class RouteDouble {public function __construct(private string $name,private array $params){}public function getName(){return $this->name;}public function parameters(){return $this->params;}}
class SessionDouble {public bool $invalidated=false;public bool $regenerated=false;public function invalidate(){$this->invalidated=true;}public function regenerateToken(){$this->regenerated=true;}}
class SessionQueryDouble {public function __construct(private string $table){}public function where($col,$id){\Illuminate\Support\Facades\DB::$deleted=[$this->table,$col,$id];return $this;}public function delete(){return 1;}}
class GuardDouble {public bool $loggedOut=false;public function __construct(public mixed $person=null){}public function check(){return $this->person!==null;}public function user(){return $this->person;}public function logout(){$this->loggedOut=true;$this->person=null;}}
class UserDouble {public string $remember_token='original';public int $saves=0;public function __construct(public string $role,public mixed $student=null,public int $id=99){}public function isStudent(){return $this->role==='student';}public function student(){return new RelationDouble($this->student);}public function forceFill($a){$this->remember_token=$a['remember_token'];return $this;}public function save(){$this->saves++;}}
class ResponseDouble {public function json($d,$s){return new \Symfony\Component\HttpFoundation\Response($s,$d);}public function view($v,$d,$s){return new \Symfony\Component\HttpFoundation\Response($s,['view'=>$v]+$d);}public function route($r){return new \Symfony\Component\HttpFoundation\Response(302,['route'=>$r]);}}
function response(){return new ResponseDouble;}
function redirect(){return new ResponseDouble;}
function config($k,$default=null){return ['session.driver'=>'database','session.table'=>'sessions','session.connection'=>null][$k]??$default;}
$root=dirname(__DIR__,2);
require $root.'/app/Support/StudentStatus.php';
require $root.'/app/Models/Student.php';
require $root.'/app/Models/Payment.php';
require $root.'/app/Http/Middleware/EnsureStudentIsActive.php';
require $root.'/app/Http/Middleware/ProtectInactiveStudent.php';
require $root.'/app/Observers/StudentStatusObserver.php';
use App\Support\StudentStatus;
use App\Models\{Student,Payment};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Auth,DB};
use App\Http\Middleware\{EnsureStudentIsActive,ProtectInactiveStudent};
use Symfony\Component\HttpFoundation\Response;
$count=0;
function check(bool $test,string $message):void {global $count;$count++;if(!$test)throw new \RuntimeException('FAIL: '.$message);}
function staff():void {Auth::$guards=['web'=>new GuardDouble(new UserDouble('admin')),'accounts'=>new GuardDouble];}
function pupil(string $status,int $id=1):Student {return new Student(['id'=>$id,'status'=>$status,'name'=>'Fixture Student','student_id'=>'FIXTURE-'.$id,'credit_balance'=>'5000.00','fee_status'=>'paid']);}
foreach(StudentStatus::VALUES as $state){
 $s=pupil($state);
 check($s->statusLabel()===ucfirst($state),'label '.$state);
 check($s->isActiveEnrollment()===($state==='active'),'access '.$state);
 check($s->statusBadge()===StudentStatus::badge($state),'shared badge '.$state);
 if($state!=='active'){
  try{$s->assertActiveEnrollment();check(false,'must throw '.$state);}catch(\Illuminate\Validation\ValidationException $e){check(str_contains($e->getMessage(),'Student status: '.ucfirst($state)),'service throws useful '.$state);}
 }else{$s->assertActiveEnrollment();check(true,'active service allowed');}
}
foreach([null,'','bad','LEFT',' active '] as $status){
 check(StudentStatus::isActive($status)===($status===' active '),'normalization/fail closed '.var_export($status,true));
}
check(StudentStatus::badge('left')==='danger','left not neutral');
$left=pupil('left');$active=pupil('active',2);Student::$rows=[$left,$active];staff();
$nextCount=0;$next=function($r)use(&$nextCount){$nextCount++;return new Response(204);};
$guard=new ProtectInactiveStudent;
$mutations=[
 ['students.update',['student'=>$left],[]],
 ['students.destroy',['student'=>$left],[]],
 ['payments.store', ['voucher'=>new \Illuminate\Database\Eloquent\Model(['id'=>44,'student_id'=>1])],[]],
 ['payments.apply-credit',['student'=>$left],[]],
 ['fee-assignments.store',['student'=>$left],[]],
 ['vouchers.store',[],['student_id'=>1]],
 ['students.bulk-destroy',[],['ids'=>[1,2]]],
 ['vouchers.generate-template',[],['student_ids'=>[1,2]]],
 ['lms.assignments.store',[],['target_student_ids'=>[1]]],
 ['teacher.examinations.results.store',[],['marks'=>[1=>12]]],
 ['teacher.attendance.save',[],['attendance'=>[1=>'present']]],
 ['students.reset-lms-password',['student'=>$left],[]],
 ['students.id-card.issue',['student'=>$left],[]],
 ['admin.internships.approve',['application'=>new \Illuminate\Database\Eloquent\Model(['student_id'=>1])],[]],
];
foreach($mutations as [$name,$params,$body]){
 $before=$nextCount;$r=new Request($name,'POST',$params,$body,true);$res=$guard->handle($r,$next);
 check($res->status===423,'locked direct or batch '.$name);
 check($nextCount===$before,'no mutation controller called '.$name);
 check($res->data['student_status']==='left','machine-readable left '.$name);
}
foreach(['students.edit','students.id-card','fee-assignments.renew','vouchers.edit'] as $route){
 $res=$guard->handle(new Request($route,'GET',['student'=>$left]),$next);
 check($res->status===423,'GET mutation page locked '.$route);
 check($res->data['view']==='errors.student-locked','HTML recovery screen '.$route);
}
foreach(['students.show','accounts.students.show','payments.extra-receipts.show','payments.extra-receipts.voucher','vouchers.print','students.export'] as $route){
 check($guard->handle(new Request($route,'GET',['student'=>$left]),$next)->status===204,'historical GET allowed '.$route);
}
foreach(['vouchers.export-selected','accounts.dashboard.student-explorer.print'] as $route){
 check($guard->handle(new Request($route,'POST',[],['student_ids'=>[1]]),$next)->status===204,'read-only POST allowed '.$route);
}
check($guard->handle(new Request('students.update-status','POST',['student'=>$left],['status'=>'active']),$next)->status===204,'admin transition reaches role-guarded controller');
check($guard->handle(new Request('students.update','POST',['student'=>$active]),$next)->status===204,'active mutation allowed');
check($guard->handle(new Request('notifications.read','POST'),$next)->status===204,'unrelated work unaffected');
$payment=new Payment(['fixtureRelation'=>new \Illuminate\Database\Eloquent\Model(['student_id'=>1])]);
check($guard->handle(new Request('payments.verify','POST',['payment'=>$payment],[],true),$next)->status===423,'payment resolves owning student');
check(QueryDouble::$locks>0&&DB::$transactions>0,'targeted mutation locks inside transaction');
check($left->credit_balance==='5000.00'&&$left->fee_status==='paid','lock does not wipe finance');
// Session checks run real middleware with fake auth/session/database relationships.
$portal=new EnsureStudentIsActive;
foreach(StudentStatus::VALUES as $state){
 $student=pupil($state);$user=new UserDouble('student',$student);Auth::$guards=['web'=>new GuardDouble($user),'accounts'=>new GuardDouble];
 $request=new Request('student.dashboard','GET',[],[],false,'/student/dashboard');$result=$portal->handle($request,$next);
 check($result->status===($state==='active'?204:302),'existing portal session '.$state);
 check(Auth::$guards['web']->loggedOut===($state!=='active'),'web logout '.$state);
 check($request->sessionObject->invalidated===($state!=='active'),'session invalidation '.$state);
 if($state!=='active')check(str_contains($result->data['errors']['email'],'Student status: '.ucfirst($state)),'portal reports exact state '.$state);
}
Auth::$guards=['web'=>new GuardDouble(new UserDouble('student',null)),'accounts'=>new GuardDouble];
check($portal->handle(new Request('lms.quiz','POST',[],[],true),$next)->status===403,'unlinked student fails closed');
Auth::$guards=['web'=>new GuardDouble(new UserDouble('student',$left)),'accounts'=>new GuardDouble(new UserDouble('accountant'))];
check($portal->handle(new Request('accounts.students.show','GET',[],[],false,'/accounts/students/1'),$next)->status===204,'separate accounts guard preserved');
staff();check($portal->handle(new Request('students.index','GET'),$next)->status===204,'admin not blocked by student policy');
// Observer: token revocation on deactivation, not deletion or password reset.
$user=new UserDouble('student',$left,99);$left->user_id=99;$left->fixtureRelation=$user;$left->changed=true;
(new \App\Observers\StudentStatusObserver)->updated($left);
check($user->saves===1&&$user->remember_token!=='original','remember token revoked');
check(DB::$deleted===['sessions','user_id',99],'only target user database sessions removed');
$left->status='active';(new \App\Observers\StudentStatusObserver)->updated($left);
check($user->saves===1,'reactivation does not reset login credentials');
// Wiring/source contracts supplement execution, not replace integration tests.
$bootstrap=file_get_contents($root.'/bootstrap/app.php');
foreach(['EnsureStudentIsActive::class','ProtectInactiveStudent::class'] as $name)check(str_contains($bootstrap,$name),'middleware installed '.$name);
$auth=file_get_contents($root.'/app/Http/Controllers/AuthController.php');check(str_contains($auth,'$student->isActiveEnrollment()'),'new login status check');
$routes=file_get_contents($root.'/routes/web.php');check(str_contains($routes,"['auth', 'role:admin']"),'admin route group retained');
$studentCtl=file_get_contents($root.'/app/Http/Controllers/StudentController.php');check(str_contains($studentCtl,"abort_unless(auth('web')->user()?->isAdmin(), 403)"),'controller explicitly admin-only');
check(str_contains($studentCtl,'lockForUpdate()->firstOrFail()'),'status transition row lock');
$bulkCtl=file_get_contents($root.'/app/Http/Controllers/VoucherController.php');
check(str_contains($bulkCtl, '$lockedStudents = Student::whereIn') && str_contains($bulkCtl, '$student && $student->isActiveEnrollment()'), 'class-wide generation rechecks locked student state before replacing vouchers');
$layout=file_get_contents($root.'/resources/views/layouts/app.blade.php');check(str_contains($layout,"\$profileStatusStudent = request()->route('student')"),'directory loop never determines lock');
check(str_contains($layout,'data-student-reactivation'),'UI exception for reactivation');
$panel=file_get_contents($root.'/resources/views/students/_panel.blade.php');check(str_contains($panel,'<th>Student Status</th>'),'visible status column');
$rows=file_get_contents($root.'/resources/views/students/_row.blade.php');check(str_contains($rows,'@disabled(!$student->isActiveEnrollment())'),'disabled bulk deletion selector');
$learning=file_get_contents($root.'/app/Services/LearningAccess.php');check(str_contains($learning,"whereHas('student', fn (\$q) => \$q->activeEnrollment())"),'task audience active enrollment');
$credit=file_get_contents($root.'/app/Services/StudentBalanceService.php');check(substr_count($credit,'$student->assertActiveEnrollment();')===2,'credit receive/apply defense in depth');
echo "PASS: $count isolated status/access/observer/source-contract checks.\n";
echo "Uses explicit framework/database doubles. No live Laravel routing, SQL, sessions or concurrency executed.\n";
}
