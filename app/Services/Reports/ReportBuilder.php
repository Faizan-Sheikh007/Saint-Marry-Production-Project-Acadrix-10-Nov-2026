<?php

namespace App\Services\Reports;

use App\Models\{AcademicYear, Attendance, DailyLedgerEntry, Examination, FeeAssignment, Payment, Program, SchoolClass, Setting, Student, Subject, Teacher};
use App\Support\Reports\{ReportCatalog, ReportMath};
use Carbon\Carbon;
use Carbon\CarbonPeriod;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;
use Illuminate\Validation\ValidationException;

/** One data pipeline for the screen, every export and every printable report. */
class ReportBuilder
{
    public function build(string $type, array $f): array
    {
        [$rows, $stats, $notes] = match ($type) {
            'students' => $this->studentRows($f), 'attendance' => $this->studentRows($f, true),
            'collection' => $this->collectionRows($f), 'defaulters' => $this->defaulterRows($f),
            'installments' => $this->installmentRows($f), 'discounts' => $this->discountRows($f),
            'class-revenue' => $this->classRows($f), 'income-expense' => $this->incomeRows($f),
            default => throw ValidationException::withMessages(['report' => 'Unknown report type.']),
        };
        $this->checkSize($rows->count());
        $sort = $f['sort']; $descending = $f['direction'] === 'desc';
        $rows = $rows->sortBy($sort, SORT_REGULAR, $descending)->values();
        return [
            'type' => $type, 'title' => ReportCatalog::title($type), 'filters' => $f,
            'availableColumns' => ReportCatalog::columns($type, $f['view']),
            'selectedColumns' => $f['columns'], 'rows' => $rows, 'stats' => $stats, 'notes' => $notes,
            'filterSummary' => $this->filterSummary($type,$f), 'generatedAt' => now(), 'currency' => Setting::get('currency', config('reports.currency', 'PKR')),
        ];
    }

    public function options(): array
    {
        $classes = SchoolClass::with('program:id,name')->orderBy('name');
        if (auth()->user()?->isTeacher()) $classes->whereIn('id', $this->teacherClassIds());
        return [
            'programs' => Program::orderBy('name')->get(['id','name']), 'classes' => $classes->get(),
            'years' => AcademicYear::orderByDesc('name')->get(['id','name']),
            'subjects' => Subject::orderBy('name')->get(['id','name']),
            'teachers' => Teacher::orderBy('name')->get(['id','name']),
            'banks' => \App\Models\BankAccount::orderBy('id')->get(),
            'exams' => Examination::with(['subject:id,name','schoolClass:id,name'])
                ->where('status','!=','cancelled')
                ->when(auth()->user()?->isTeacher(), fn ($q) => $q->whereIn('school_class_id', $this->teacherClassIds()))
                ->orderByDesc('exam_date')->get(['id','name','subject_id','school_class_id','exam_date','type']),
        ];
    }

    public function teacherClassIds(): array
    {
        $teacher = auth()->user()?->teacher;
        return $teacher ? $teacher->classes()->pluck('school_classes.id')->all() : [];
    }

    private function studentQuery(array $f, bool $historicalAttendance = false): Builder
    {
        $q = Student::query()->with(['program:id,name','schoolClass:id,name,section','academicYear:id,name']);
        if (auth()->user()?->isTeacher()) $q->whereIn('school_class_id', $this->teacherClassIds());
        foreach (['program_id' => 'program_id','academic_year_id' => 'academic_year_id','student_status' => 'status','section' => 'section','student_id' => 'id'] as $input => $column) {
            if (isset($f[$input])) $q->where($column, $f[$input]);
        }
        if (isset($f['class_id'])) {
            if ($historicalAttendance) {
                $q->where(function ($q) use ($f) {
                    $q->where('school_class_id', $f['class_id'])->orWhereHas('attendances', function ($a) use ($f) { $this->scopeAttendance($a, $f); });
                });
            } else $q->where('school_class_id', $f['class_id']);
        }
        if (!empty($f['search'])) {
            $search = trim($f['search']);
            $q->where(function ($q) use ($search) {
                foreach (['name','student_id','roll_number','father_name','guardian_name','phone','guardian_phone','ledger_number'] as $index => $column) {
                    $method = $index === 0 ? 'where' : 'orWhere';
                    $q->$method($column, 'like', '%' . $search . '%');
                }
            });
        }
        return $q;
    }

    private function profile(Student $s): array
    {
        return ['_student_id' => $s->id, 'student_code' => $s->student_id, 'name' => $s->name,
            'program' => $s->program?->name, 'class' => $s->schoolClass?->name,
            'academic_year' => $s->academicYear?->name, 'section' => $s->section ?: $s->schoolClass?->section,
            'roll_number' => $s->roll_number, 'guardian' => $s->guardian_name ?: $s->father_name,
            'phone' => $s->phone, 'guardian_phone' => $s->guardian_phone, 'student_status' => $s->status];
    }

    private function scopeAttendance($q, array $f): void
    {
        $latest = Attendance::query()->selectRaw('MAX(id)')->groupBy('student_id','date','subject_id');
        if (!empty($f['from'])) { $q->whereDate('date','>=',$f['from']); $latest->whereDate('date','>=',$f['from']); }
        if (!empty($f['to'])) { $q->whereDate('date','<=',$f['to']); $latest->whereDate('date','<=',$f['to']); }
        // Nullable subject IDs allowed duplicate daily rows in older databases. Latest entry wins.
        $q->whereIn('id', $latest);
        if (!empty($f['class_id'])) $q->where('school_class_id', $f['class_id']);
        if (!empty($f['subject_id'])) $q->where('subject_id', $f['subject_id']);
        elseif (($f['attendance_scope'] ?? 'all') === 'general') $q->whereNull('subject_id');
        elseif (($f['attendance_scope'] ?? 'all') === 'subject') $q->whereNotNull('subject_id');
    }

    private function scopeExamination($q, array $f): void
    {
        $q->where('status','!=','cancelled')->where('total_marks','>',0);
        if (!empty($f['from'])) $q->whereDate('exam_date','>=',$f['from']);
        if (!empty($f['to'])) $q->whereDate('exam_date','<=',$f['to']);
        if (!empty($f['exam_ids'])) $q->whereIn('id', $f['exam_ids']);
        if (!empty($f['subject_id'])) $q->where('subject_id', $f['subject_id']);
        if (!empty($f['teacher_id'])) $q->where('teacher_id', $f['teacher_id']);
        if (!empty($f['exam_type'])) $q->where('type', $f['exam_type']);
    }

    private function academicRelations(array $f): array
    {
        return [
            'attendances' => function ($q) use ($f) {
                $this->scopeAttendance($q, $f);
                $q->with(['subject:id,name','schoolClass:id,name'])->orderBy('date')->orderBy('id');
            },
            'examResults' => function ($q) use ($f) {
                $q->whereNotNull('marks_obtained')->whereHas('examination', function ($e) use ($f) { $this->scopeExamination($e,$f); })
                    ->with(['examination.subject:id,name','examination.teacher:id,name','gradedBy:id,name']);
            },
            'reportRemarks' => function ($q) use ($f) {
                if (!empty($f['from'])) $q->whereDate('remark_date','>=',$f['from']);
                if (!empty($f['to'])) $q->whereDate('remark_date','<=',$f['to']);
                if (!empty($f['subject_id'])) $q->where(fn ($n) => $n->whereNull('subject_id')->orWhere('subject_id',$f['subject_id']));
                $q->with(['teacher:id,name','subject:id,name','author:id,name'])->orderByDesc('remark_date')->orderByDesc('id');
            },
        ];
    }

    private function feeRelations(): array
    {
        return [
            'feeAssignments' => fn ($q) => $q->select(['id','student_id','fee_template_id','cycle_start','cycle_end','scholarship_amount','discount_amount','financial_aid_amount','total_amount','notes','created_at'])
                ->with(['feeTemplate:id,name','vouchers.bankAccount','vouchers.feeHead:id,name']),
            'vouchers' => fn ($q) => $q->whereNull('fee_assignment_id')->with(['bankAccount','feeHead:id,name']),
        ];
    }

    private function eachStudent(Builder $query, callable $callback): void
    {
        if ((clone $query)->count() > config('reports.max_students',10000)) {
            throw ValidationException::withMessages(['search' => 'Too many students. Narrow the program, class or search filters.']);
        }
        // Eager loading is bounded to one batch; no per-student financial queries.
        $query->orderBy('id')->chunkById(100, function ($students) use ($callback) {
            foreach ($students as $student) $callback($student);
        });
    }

    private function exams(Student $s): Collection
    {
        return $s->examResults->map(function ($r) {
            $e = $r->examination;
            return ['exam_id' => $e->id, 'batch_id' => $e->batch_id, 'name' => $e->name,
                'date' => $e->exam_date?->toDateString(), 'type' => $e->type_label, 'subject' => $e->subject?->name ?: 'General',
                'obtained' => (float) $r->marks_obtained, 'maximum' => (float) $e->total_marks, 'passing' => (float) $e->passing_marks,
                'percentage' => ReportMath::percent((float) $r->marks_obtained,(float) $e->total_marks), 'grade' => $r->grade,
                'outcome' => (float) $r->marks_obtained >= (float) $e->passing_marks ? 'Pass' : 'Below pass mark',
                'teacher' => $r->gradedBy?->name ?: $e->teacher?->name, 'remarks' => $r->remarks,
                'locked' => $e->locked_at !== null ? 'Locked' : 'Not locked'];
        })->sortBy([['date','asc'],['name','asc'],['subject','asc']])->values();
    }

    private function selectedVouchers(Student $s, array $f = []): Collection
    {
        return $s->authoritativeVouchers()->filter(function ($v) use ($f) {
            if ($v->status === 'cancelled') return false;
            if (!empty($f['from']) && (!$v->due_date || $v->due_date->toDateString() < $f['from'])) return false;
            if (!empty($f['to']) && (!$v->due_date || $v->due_date->toDateString() > $f['to'])) return false;
            foreach (['cycle' => 'cycle_no','installment' => 'installment_no','bank_account_id' => 'bank_account_id'] as $input => $field) {
                if (isset($f[$input]) && (string) $v->$field !== (string) $f[$input]) return false;
            }
            return true;
        })->values();
    }

    private function totals(Collection $vouchers): array
    {
        $today = today();
        $data = ['gross' => $vouchers->sum('amount'), 'fine' => 0,
            'discount' => $vouchers->sum('discount_amount'), 'charged' => $vouchers->sum(fn ($v) => $v->total()),
            'paid' => $vouchers->sum('paid_amount'), 'pending' => $vouchers->sum(fn ($v) => $v->balance()),
            'overdue' => $vouchers->filter(fn ($v) => $v->due_date && $v->due_date->lt($today))->sum(fn ($v) => $v->balance())];
        $data = array_map(fn ($value) => round((float) $value,2),$data);
        $data['fee_state'] = ReportMath::feeState($vouchers->count(),$data['paid'],$data['pending']);
        return $data;
    }

    private function studentRows(array $f, bool $attendanceOnly = false): array
    {
        $rows = collect();
        $q = $this->studentQuery($f, $attendanceOnly)->with($this->academicRelations($f));
        if (!$attendanceOnly) $q->with($this->feeRelations());
        $this->eachStudent($q, function ($s) use (&$rows,$f,$attendanceOnly) {
            $att = ReportMath::attendance($s->attendances);
            $base = $this->profile($s) + $att;
            if (!$this->inRange($att['attendance_rate'],$f,'attendance')) return;
            if ($attendanceOnly) {
                // Status filters select matching students, not the rate's denominator.
                if (!empty($f['attendance_status']) && !$s->attendances->contains('status',$f['attendance_status'])) return;
                if ($f['view'] === 'details') {
                    foreach ($s->attendances as $a) {
                        if (!empty($f['attendance_status']) && $a->status !== $f['attendance_status']) continue;
                        $rows->push($base + ['date' => $a->date->toDateString(), 'recorded_class' => $a->schoolClass?->name,
                            'subject' => $a->subject?->name ?: 'General / daily register',
                            'attendance_status' => $a->status, 'attendance_remark' => $a->remarks]);
                    }
                } else $rows->push($base + [
                    'recorded_classes' => $s->attendances->pluck('schoolClass.name')->filter()->unique()->implode(', '),
                    'last_attendance' => $s->attendances->last()?->date?->toDateString(),
                    'attendance_alert' => $att['attendance_rate'] === null ? 'no_records' : ($att['attendance_rate'] < config('reports.low_attendance_threshold',75) ? 'low_attendance' : 'on_track'),
                ]);
            } else {
                $exam = ReportMath::examSummary($this->exams($s));
                if (!$this->inRange($exam['exam_average'],$f,'result')) return;
                $resultStatus = $f['result_status'] ?? null;
                if ($resultStatus === 'has_results' && !$exam['exam_count']) return;
                if ($resultStatus === 'no_results' && $exam['exam_count']) return;
                if ($resultStatus === 'failed' && !$exam['failed_exams']) return;
                if ($resultStatus === 'passed' && (!$exam['exam_count'] || $exam['failed_exams'])) return;
                $vouchers = $this->selectedVouchers($s); // Student fee balances are current, not exam-period cash flows.
                $fee = $this->totals($vouchers);
                if (!$this->feeMatches($fee,$f['fee_status'] ?? null)) return;
                if (isset($f['min_pending']) && $fee['pending'] < $f['min_pending']) return;
                $next = $vouchers->filter(fn ($v) => $v->balance() > 0 && $v->due_date && $v->due_date->gte(today()))->sortBy('due_date')->first();
                $remark = $s->reportRemarks->first();
                $rows->push($base + $exam + $fee + ['next_due' => $next?->due_date?->toDateString(), 'credit' => (float) $s->credit_balance,
                    'latest_remark' => $remark?->remarks, 'remark_author' => $remark?->teacher?->name ?: $remark?->author?->name,
                    'remark_date' => $remark?->remark_date?->toDateString()]);
            }
            $this->checkSize($rows->count());
        });
        $stats = [];
        if ($attendanceOnly && $f['view'] === 'details') {
            $att = ReportMath::attendance($rows->map(fn ($r) => ['status' => $r['attendance_status']]));
            $stats = [$this->stat('Matching records',$rows->count()),$this->stat('Students',$rows->pluck('_student_id')->unique()->count()),
                $this->stat('Present',$att['present']),$this->stat('Absent',$att['absent']),$this->stat('Late',$att['late']),$this->stat('Leave',$att['leave'])];
        } else {
            $rate = ReportMath::percent($rows->sum('present')+$rows->sum('late'),$rows->sum('sessions'));
            $stats = [$this->stat('Matching students',$rows->count()), $this->stat('Attendance',$rate,'percent')];
            if ($attendanceOnly) $stats = array_merge($stats,[$this->stat('Recorded sessions',$rows->sum('sessions')),
                $this->stat('Absent',$rows->sum('absent')),$this->stat('Late',$rows->sum('late')),$this->stat('No attendance data',$rows->whereNull('attendance_rate')->count())]);
            else $stats = array_merge($stats,[$this->stat('Graded exam entries',$rows->sum('exam_count')),
                $this->stat('Paid fees',$rows->sum('paid'),'money'),$this->stat('Pending fees',$rows->sum('pending'),'money'),$this->stat('Overdue fees',$rows->sum('overdue'),'money')]);
        }
        return [$rows,$stats,$attendanceOnly ? $this->attendanceNotes() : array_merge($this->attendanceNotes(),[
            'Exam results and teacher remarks use the selected date range. Weighted result = total obtained marks ÷ total available marks across the selected graded exams; cancelled exams are excluded. These are internal results, not a publication certificate.',
            'Fees show current authoritative, non-cancelled voucher balances across all dates. Paid means applied to those vouchers; unapplied credit is shown separately. Replaced fee plans are not counted again.',
        ])];
    }

    private function inRange(?float $value, array $f, string $name): bool
    {
        if (isset($f['min_'.$name]) && ($value === null || $value < $f['min_'.$name])) return false;
        if (isset($f['max_'.$name]) && ($value === null || $value > $f['max_'.$name])) return false;
        return true;
    }

    private function feeMatches(array $fee, ?string $state): bool
    {
        return !$state || ($state === 'overdue' ? $fee['overdue'] > 0 : $fee['fee_state'] === $state);
    }

    private function collectionQuery(array $f): Builder
    {
        $q = Payment::query()->where('method', '!=', 'balance_credit')->whereBetween('paid_at', [Carbon::parse($f['from'])->startOfDay(),Carbon::parse($f['to'])->endOfDay()])
            ->whereHas('voucher', fn ($v) => $v->whereIn('student_id',$this->studentQuery($f)->select('students.id')));
        $state = $f['payment_status'] ?? 'verified';
        if ($state === 'verified') $q->where(fn ($s) => $s->where('status','verified')->orWhereNull('status'));
        elseif ($state !== 'all') $q->where('status',$state);
        if (!empty($f['method'])) $q->where('method',$f['method']);
        if (!empty($f['bank_account_id'])) $q->whereHas('voucher',fn ($v) => $v->where('bank_account_id',$f['bank_account_id']));
        return $q;
    }

    private function collectionRows(array $f): array
    {
        $q = $this->collectionQuery($f)->with(['voucher.student.program:id,name','voucher.student.schoolClass:id,name,section',
            'voucher.student.academicYear:id,name','voucher.feeHead:id,name','voucher.bankAccount']);
        $this->checkSize((clone $q)->count());
        $rows = $q->get()->map(fn ($p) => $this->paymentRow($p));
        if (in_array($f['payment_status'] ?? 'verified', ['verified', 'all'], true) && empty($f['bank_account_id'])) {
            // Advance receipts have no voucher bank assignment; do not invent a bank mapping.
            $creditQuery = \App\Services\CashReceiptLedger::extraReceipts(
                Carbon::parse($f['from'])->startOfDay(), Carbon::parse($f['to'])->endOfDay())
                ->whereIn('student_id', $this->studentQuery($f)->select('students.id'))
                ->with('student.program:id,name', 'student.schoolClass:id,name,section', 'student.academicYear:id,name');
            if (! empty($f['method'])) $creditQuery->where('method', $f['method']);
            $this->checkSize($rows->count() + (clone $creditQuery)->count());
            $rows = $rows->concat($creditQuery->get()->map(fn ($entry) => $this->profile($entry->student) + [
                'payment_id' => 'CR-'.$entry->id, 'date' => $entry->paid_at?->format('Y-m-d H:i'),
                'voucher_no' => 'Extra payment / advance', 'fee_head' => null, 'cycle' => null, 'installment' => null,
                'amount' => (float) $entry->amount, 'method' => $entry->method, 'reference' => $entry->reference_no,
                'bank' => null, 'received_by' => $entry->recorded_by, 'payment_status' => 'verified',
                'verified_at' => $entry->created_at?->format('Y-m-d H:i'), 'payment_notes' => $entry->notes,
            ]));
        }
        $verified = $rows->where('payment_status','verified');
        $stats = [$this->stat('Payment entries',$rows->count()),$this->stat('Verified collection',$verified->sum('amount'),'money'),
            $this->stat('Cash (verified)',$verified->where('method','cash')->sum('amount'),'money'),
            $this->stat('Non-cash (verified)',$verified->where('method','!=','cash')->sum('amount'),'money'),
            $this->stat('Unverified / rejected',$rows->where('payment_status','!=','verified')->sum('amount'),'money')];
        return [$rows,$stats,[
            'The paid-at range includes the entire end date. Only verified payments (including legacy null-status payments) count as collected income. Pending and rejected entries never increase the verified collection total.',
            'One row is a direct payment or an extra-payment receipt (CR). Applying previously received credit is excluded: it is not new money. Extra-payment receipts have no voucher bank mapping and are omitted when filtering by voucher bank. Legacy credit balances without a dated receipt are not fabricated as current income.',
        ]];
    }

    private function paymentRow(Payment $p): array
    {
        return $this->profile($p->voucher->student) + ['payment_id' => 'PAY-'.$p->id, 'date' => $p->paid_at?->format('Y-m-d H:i'),
            'voucher_no' => $p->voucher?->voucher_no, 'fee_head' => $p->voucher?->feeHead?->name,
            'cycle' => $p->voucher?->cycle_no, 'installment' => $p->voucher?->installment_no,
            'amount' => (float) $p->amount, 'method' => $p->method, 'reference' => $p->reference_no,
            'bank' => $this->bankName($p->voucher?->bankAccount), 'received_by' => $p->received_by,
            'payment_status' => $p->status ?? 'verified', 'verified_at' => $p->verified_at?->format('Y-m-d H:i'), 'payment_notes' => $p->notes];
    }

    private function bankName($bank): ?string
    {
        return $bank ? trim(($bank->bank_name ?: $bank->name ?: '') . ($bank->account_title ? ' / '.$bank->account_title : '')) : null;
    }

    private function challanRows(Student $s, Collection $vouchers): Collection
    {
        // Never call combinedBalance() on each fee-head row: that would repeat a bundled challan's total.
        return $vouchers->groupBy('voucher_no')->map(function ($items, $number) use ($s) {
            $v = $items->first(); $fee = $this->totals($items);
            $unpaid = $items->filter(fn ($v) => $v->balance() > 0);
            $due = ($unpaid->isNotEmpty() ? $unpaid : $items)->filter(fn ($v) => $v->due_date)->sortBy('due_date')->first()?->due_date;
            $days = $fee['pending'] > 0 && $due && $due->lt(today()) ? (int) $due->diffInDays(today()) : 0;
            $dueState = $fee['pending'] <= 0 ? 'settled' : (!$due ? 'no_due_date' : ($days > 0 ? 'overdue' : ($due->isToday() ? 'due' : 'upcoming')));
            return $this->profile($s) + $fee + ['voucher_no' => (string) $number,'plan_id' => $v->fee_assignment_id,
                'cycle' => $v->cycle_no,'installment' => $v->installment_no,'part' => $v->part_no,
                'due_date' => $due?->toDateString(),'due_state' => $dueState,'days_overdue' => $days,
                'bank' => $items->map(fn ($v) => $this->bankName($v->bankAccount))->filter()->unique()->implode(', ')];
        })->values();
    }

    private function installmentRows(array $f): array
    {
        $rows = collect();
        $this->eachStudent($this->studentQuery($f)->with($this->feeRelations()), function ($s) use (&$rows,$f) {
            $vouchers = $this->selectedVouchers($s,$f)->filter(fn ($v) => $v->installment_no !== null);
            foreach ($this->challanRows($s,$vouchers) as $row) {
                $state = $f['invoice_status'];
                if ($state === 'pending' && $row['pending'] <= 0) continue;
                if (in_array($state,['paid','partial','unpaid']) && $row['fee_state'] !== $state) continue;
                if (in_array($state,['overdue','due','upcoming']) && $row['due_state'] !== $state) continue;
                if (isset($f['min_pending']) && $row['pending'] < $f['min_pending']) continue;
                $rows->push($row);
            }
            $this->checkSize($rows->count());
        });
        return [$rows,[$this->stat('Matching challans',$rows->count()),$this->stat('Net charged',$rows->sum('charged'),'money'),
            $this->stat('Paid / applied',$rows->sum('paid'),'money'),$this->stat('Pending',$rows->sum('pending'),'money'),$this->stat('Overdue',$rows->sum('overdue'),'money')], [
            'Date filters apply to due dates. Balances reflect current applied payments, not a historical snapshot. Select All statuses to include settled installments.',
            'Each row is one challan: fee-head lines are summed once; split challans stay separate. Replaced plans and cancelled voucher lines are excluded.',
        ]];
    }

    private function defaulterRows(array $f): array
    {
        $rows = collect();
        $this->eachStudent($this->studentQuery($f)->with($this->feeRelations()), function ($s) use (&$rows,$f) {
            $overdue = $this->selectedVouchers($s,$f)->filter(function ($v) use ($f) {
                if ($v->balance() <= 0 || !$v->due_date || !$v->due_date->lt(today())) return false;
                $days = (int) $v->due_date->diffInDays(today());
                if (isset($f['min_days']) && $days < $f['min_days']) return false;
                if (isset($f['max_days']) && $days > $f['max_days']) return false;
                if (isset($f['aging']) && ReportMath::agingBucket($days) !== 'age_'.$f['aging']) return false;
                return true;
            });
            if ($overdue->isEmpty()) return;
            $fee = $this->totals($overdue);
            if (isset($f['min_pending']) && $fee['overdue'] < $f['min_pending']) return;
            $aging = ['age_1_30' => 0.0,'age_31_60' => 0.0,'age_61_90' => 0.0,'age_90_plus' => 0.0];
            foreach ($overdue as $v) $aging[ReportMath::agingBucket((int) $v->due_date->diffInDays(today()))] += $v->balance();
            $first = $overdue->sortBy('due_date')->first()->due_date;
            $rows->push($this->profile($s) + $fee + $aging + ['challans' => $overdue->pluck('voucher_no')->unique()->count(),
                'earliest_due' => $first->toDateString(), 'days_overdue' => (int) $first->diffInDays(today())]);
        });
        return [$rows,[$this->stat('Students overdue',$rows->count()),$this->stat('Overdue balance',$rows->sum('overdue'),'money'),
            $this->stat('1–30 days',$rows->sum('age_1_30'),'money'),$this->stat('31–60 days',$rows->sum('age_31_60'),'money'),
            $this->stat('61–90 days',$rows->sum('age_61_90'),'money'),$this->stat('Over 90 days',$rows->sum('age_90_plus'),'money')],[
            'A defaulter has a positive current balance on a voucher due before today. Due-today and upcoming fees are not overdue.',
            'Date filters select voucher due dates; age filters select overdue items before grouping by student. Each remaining voucher balance goes into its own aging band, not the student’s oldest band. All bands reconcile to the displayed overdue total.',
        ]];
    }

    private function classRows(array $f): array
    {
        $rows = collect();
        $this->eachStudent($this->studentQuery($f)->with($this->feeRelations()), function ($s) use (&$rows,$f) {
            $key = (string) ($s->school_class_id ?: 'unassigned') . ':' . (string) ($s->program_id ?: 'none');
            $row = $rows->get($key, ['program' => $s->program?->name ?: 'Unassigned','class' => $s->schoolClass?->name ?: 'Unassigned',
                'section' => $s->schoolClass?->section,'students' => 0,'billed_students' => 0,'challans' => 0,
                'gross' => 0.0,'fine' => 0.0,'discount' => 0.0,'charged' => 0.0,'paid' => 0.0,'pending' => 0.0,'overdue' => 0.0,'defaulters' => 0]);
            $vouchers = $this->selectedVouchers($s,$f); $totals = $this->totals($vouchers);
            $row['students']++; if ($vouchers->isNotEmpty()) $row['billed_students']++;
            $row['challans'] += $vouchers->pluck('voucher_no')->unique()->count();
            if ($totals['overdue'] > 0) $row['defaulters']++;
            foreach (['gross','fine','discount','charged','paid','pending','overdue'] as $field) $row[$field] += $totals[$field];
            $row['collection_rate'] = ReportMath::percent($row['paid'],$row['charged']);
            $row['fee_state'] = ReportMath::feeState($row['challans'],$row['paid'],$row['pending']);
            $rows->put($key,$row);
        });
        if (isset($f['min_pending'])) $rows = $rows->filter(fn ($r) => $r['pending'] >= $f['min_pending']);
        return [$rows->values(),[$this->stat('Class / program groups',$rows->count()),$this->stat('Net charged',$rows->sum('charged'),'money'),
            $this->stat('Paid / applied',$rows->sum('paid'),'money'),$this->stat('Pending',$rows->sum('pending'),'money'),
            $this->stat('Overdue',$rows->sum('overdue'),'money'),$this->stat('Recovery',ReportMath::percent($rows->sum('paid'),$rows->sum('charged')),'percent')],[
            'Revenue here means billed fees and payments applied to the selected authoritative invoices, grouped by the student’s current program and class. It is not cash collected during a payment-date period; use Collection for that.',
            'Optional dates select invoice due dates. Replaced plans and cancelled lines are excluded. Students without invoices are included in the class’s student count; completely empty classes are omitted.',
        ]];
    }

    private function discountRows(array $f): array
    {
        $rows = collect();
        $this->eachStudent($this->studentQuery($f)->with($this->feeRelations()), function ($s) use (&$rows,$f) {
            // Match Student::authoritativeVouchers ownership, including plans without generated rows.
            $owners = [];
            foreach ($s->feeAssignments->sortBy([['cycle_start','asc'],['id','asc']]) as $plan) {
                $start = max(1,(int) ($plan->cycle_start ?: 1)); $end = max($start,(int) ($plan->cycle_end ?: $start));
                for ($cycle = $start; $cycle <= $end; $cycle++) $owners[$cycle] = $plan->id;
            }
            $current = array_unique(array_values($owners));
            foreach ($s->feeAssignments as $plan) {
                $isCurrent = in_array($plan->id,$current);
                if ($f['plan_scope'] === 'current' && !$isCurrent) continue;
                if (!empty($f['from']) && (!$plan->created_at || $plan->created_at->toDateString() < $f['from'])) continue;
                if (!empty($f['to']) && (!$plan->created_at || $plan->created_at->toDateString() > $f['to'])) continue;
                $scholarship = (float) $plan->scholarship_amount; $aid = (float) $plan->financial_aid_amount; $discount = (float) $plan->discount_amount;
                $relief = round($scholarship+$aid+$discount,2);
                if ($relief <= 0 || (isset($f['min_relief']) && $relief < $f['min_relief'])) continue;
                if (($f['relief_type'] ?? '') === 'scholarship' && $scholarship <= 0) continue;
                if (($f['relief_type'] ?? '') === 'financial_aid' && $aid <= 0) continue;
                if (($f['relief_type'] ?? '') === 'discount' && $discount <= 0) continue;
                $rows->push($this->profile($s) + ['plan_id' => $plan->id,'template' => $plan->feeTemplate?->name,
                    'cycles' => ($plan->cycle_start ?: 1).'–'.($plan->cycle_end ?: $plan->cycle_start ?: 1),
                    'plan_created' => $plan->created_at?->toDateString(),'scholarship' => $scholarship,'financial_aid' => $aid,
                    'plan_discount' => $discount,'relief' => $relief,'plan_total' => (float) $plan->total_amount,
                    'plan_scope' => $isCurrent ? 'owns_current_cycles' : 'superseded','notes' => $plan->notes]);
            }
            $this->checkSize($rows->count());
        });
        return [$rows,[$this->stat('Students receiving relief',$rows->pluck('_student_id')->unique()->count()),$this->stat('Plans',$rows->count()),
            $this->stat('Scholarship settings',$rows->sum('scholarship'),'money'),$this->stat('Financial aid settings',$rows->sum('financial_aid'),'money'),
            $this->stat('Discount settings',$rows->sum('plan_discount'),'money'),$this->stat('Total relief settings',$rows->sum('relief'),'money')],[
            'These amounts are stored fee-plan relief settings, not a second deduction from invoice balances. A plan may own only some of its original cycles. Use invoice relief in Class Revenue / Installments for booked voucher-line reductions.',
            'Dates filter plan creation. Current scope shows plans still owning at least one cycle; All plans includes superseded history and its settings must not be treated as current granted relief.',
        ]];
    }

    private function incomeRows(array $f): array
    {
        $from = Carbon::parse($f['from'])->startOfDay(); $to = Carbon::parse($f['to'])->endOfDay();
        if ($from->diffInDays($to) > 3660) throw ValidationException::withMessages(['from' => 'Limit the income / expense window to ten years.']);
        $payments = \App\Services\CashReceiptLedger::entries($from, $to);
        $ledger = DailyLedgerEntry::whereBetween('entry_date',[$f['from'],$f['to']]);
        $this->checkSize($payments->count()+(clone $ledger)->count());
        $ledger = $ledger->get(['id','entry_date','type','amount']);
        $format = match ($f['group_by']) { 'day' => 'Y-m-d','year' => 'Y', default => 'Y-m' };
        $paymentGroups = $payments->groupBy(fn ($p) => $p->paid_at->format($format));
        $ledgerGroups = $ledger->groupBy(fn ($e) => $e->entry_date->format($format));
        $start = $from->copy(); $end = $to->copy();
        if ($f['group_by'] === 'month') { $start->startOfMonth(); $end->startOfMonth(); }
        elseif ($f['group_by'] === 'year') { $start->startOfYear(); $end->startOfYear(); }
        $rows = collect();
        foreach (CarbonPeriod::create($start,'1 '.$f['group_by'],$end) as $date) {
            $key = $date->format($format); $p = $paymentGroups->get($key,collect()); $l = $ledgerGroups->get($key,collect());
            $fees = round((float) $p->sum('amount'),2); $manual = round((float) $l->where('type','in')->sum('amount'),2);
            $expense = round((float) $l->where('type','expense')->sum('amount'),2); $out = round((float) $l->where('type','out')->sum('amount'),2);
            $income = $f['income_basis'] === 'ledger' ? $manual : $fees;
            $rows->push(['period' => $key,'fee_income' => $fees,'ledger_in' => $manual,'income' => $income,'expense' => $expense,
                'surplus' => round($income-$expense,2),'withdrawals' => $out,'net' => round($income-$expense-$out,2),
                'payment_count' => $p->count(),'expense_count' => $l->where('type','expense')->count()]);
        }
        return [$rows,[$this->stat('Selected-basis income',$rows->sum('income'),'money'),$this->stat('Recorded expenses',$rows->sum('expense'),'money'),
            $this->stat('Operating surplus / deficit',$rows->sum('surplus'),'money'),$this->stat('Other outflows',$rows->sum('withdrawals'),'money'),
            $this->stat('Net movement',$rows->sum('net'),'money')],[
            'Income basis: '.($f['income_basis'] === 'ledger' ? 'manual Daily Ledger In entries' : 'verified direct payments and extra-payment receipts by paid-at date (credit transfers excluded)').'. Expenses use actual Daily Ledger Expense entries. Other outflows use Daily Ledger Out entries, shown separately from expenses.',
            'Fee receipts and manual ledger receipts are never added together: the existing ledger has no payment-link field to identify duplicates. The unselected receipt column is informational only.',
            'Surplus = selected income − expenses. Net movement also subtracts other outflows; it is not a bank closing balance or an accrual profit statement. Only records inside the exact selected dates are used, even in partial months. Missing ledger entries are not estimated.',
        ]];
    }

    public function detail(Student $student, array $f, bool $attendanceOnly = false): array
    {
        $student->load(['program:id,name','schoolClass:id,name,section','academicYear:id,name']);
        $student->load($this->academicRelations($f));
        $att = ReportMath::attendance($student->attendances);
        $records = $student->attendances->map(fn ($a) => ['date' => $a->date->toDateString(), 'class' => $a->schoolClass?->name,
            'subject' => $a->subject?->name ?: 'General / daily register','status' => $a->status,'remarks' => $a->remarks]);
        if (!empty($f['attendance_status'])) $records = $records->where('status',$f['attendance_status'])->values();
        $subjectSummary = $student->attendances->groupBy(fn ($a) => $a->subject_id ?: 'general')->map(fn ($items) =>
            ['subject' => $items->first()->subject?->name ?: 'General / daily register'] + ReportMath::attendance($items))->values();
        $monthSummary = $student->attendances->groupBy(fn ($a) => $a->date->format('Y-m'))->map(fn ($items,$month) =>
            ['month' => $month] + ReportMath::attendance($items))->values();
        $exams = $attendanceOnly ? collect() : $this->exams($student);
        $feeRows = collect(); $fee = []; $payments = collect();
        if (!$attendanceOnly) {
            $student->load($this->feeRelations());
            $vouchers = $this->selectedVouchers($student);
            $fee = $this->totals($vouchers) + ['credit' => (float) $student->credit_balance];
            $feeRows = $this->challanRows($student,$vouchers)->sortBy('due_date')->values();
            $paymentQuery = Payment::whereHas('voucher',fn ($v) => $v->where('student_id',$student->id))
                ->whereBetween('paid_at',[Carbon::parse($f['from'])->startOfDay(),Carbon::parse($f['to'])->endOfDay()])
                ->with(['voucher.student.program','voucher.student.schoolClass','voucher.student.academicYear','voucher.feeHead','voucher.bankAccount']);
            $this->checkSize((clone $paymentQuery)->count());
            $payments = $paymentQuery->orderByDesc('paid_at')->get()->map(fn ($p) => $this->paymentRow($p));
        }
        $this->checkSize($records->count()+$exams->count()+$feeRows->count()+$payments->count());
        return ['student' => $student, 'profile' => $this->profile($student), 'attendanceOnly' => $attendanceOnly,
            'title' => $attendanceOnly ? 'Individual Attendance Report' : 'Individual Student Report', 'filters' => $f,
            'attendanceSummary' => $att,'attendanceRecords' => $records,'subjectSummary' => $subjectSummary,'monthSummary' => $monthSummary,
            'examRows' => $exams,'examSummary' => ReportMath::examSummary($exams),'remarks' => $student->reportRemarks,
            'feeSummary' => $fee,'feeRows' => $feeRows,'payments' => $payments,
            'filterSummary' => $this->filterSummary($attendanceOnly ? 'attendance' : 'students',$f),'generatedAt' => now(),'currency' => Setting::get('currency',config('reports.currency','PKR')), 'notes' => $this->attendanceNotes()];
    }


    public function filterSummary(string $type, array $f): array
    {
        $periodLabel = in_array($type,['defaulters','installments','class-revenue']) ? 'Invoice due dates' : ($type === 'discounts' ? 'Plan creation dates' : 'Period');
        $items = [$periodLabel => ($f['from'] ?? 'Any date').' to '.($f['to'] ?? 'Any date')];
        if (in_array($type,['students','defaulters','installments','class-revenue'])) $items['Balance date'] = today()->toDateString().' (current)';
        if ($type !== 'income-expense') {
            foreach (['program_id' => [Program::class,'Program'],'class_id' => [SchoolClass::class,'Class'],'academic_year_id' => [AcademicYear::class,'Academic year']] as $key => [$model,$label]) {
                if (isset($f[$key])) $items[$label] = $model::whereKey($f[$key])->value('name') ?? '#'.$f[$key];
            }
            foreach (['search' => 'Student search','section' => 'Section','student_status' => 'Student status'] as $key => $label) {
                if (isset($f[$key])) $items[$label] = (string) $f[$key];
            }
        }
        $keys = match ($type) {
            'students' => ['attendance_scope','subject_id','min_attendance','max_attendance','fee_status','min_pending','exam_ids','exam_type','teacher_id','result_status','min_result','max_result'],
            'attendance' => ['view','attendance_scope','subject_id','attendance_status','min_attendance','max_attendance'],
            'collection' => ['payment_status','method','bank_account_id'],
            'defaulters' => ['aging','min_days','max_days','min_pending'],
            'installments' => ['invoice_status','cycle','installment','bank_account_id','min_pending'],
            'class-revenue' => ['min_pending'],
            'discounts' => ['relief_type','plan_scope','min_relief'],
            'income-expense' => ['income_basis','group_by'], default => [],
        };
        $labels = ['attendance_scope'=>'Attendance register','subject_id'=>'Subject','teacher_id'=>'Exam teacher','exam_ids'=>'Selected exams',
            'min_attendance'=>'Minimum attendance %','max_attendance'=>'Maximum attendance %','min_result'=>'Minimum weighted result %','max_result'=>'Maximum weighted result %',
            'min_pending'=>$type==='defaulters'?'Minimum overdue balance':'Minimum pending fee','min_relief'=>'Minimum relief setting',
            'bank_account_id'=>'Voucher bank','min_days'=>'Minimum item days overdue','max_days'=>'Maximum item days overdue'];
        foreach ($keys as $key) {
            if (!isset($f[$key]) || $f[$key] === [] || $f[$key] === '') continue;
            $label = $labels[$key] ?? ucwords(str_replace('_',' ',$key)); $value = $f[$key];
            if ($key === 'exam_ids') $value = Examination::with('subject:id,name')->whereIn('id',$value)->get()->map(fn($e)=>'#'.$e->id.' '.$e->name.' ('.($e->subject?->name ?: 'General').')')->implode('; ');
            elseif ($key === 'subject_id') $value = Subject::whereKey($value)->value('name') ?? '#'.$value;
            elseif ($key === 'teacher_id') $value = Teacher::whereKey($value)->value('name') ?? '#'.$value;
            elseif ($key === 'bank_account_id') $value = $this->bankName(\App\Models\BankAccount::find($value));
            elseif ($key === 'income_basis') $value = $value === 'fees' ? 'Verified fee receipts' : 'Manual ledger receipts';
            elseif ($key === 'attendance_scope') $value = match ($value) { 'general'=>'General / daily register','subject'=>'Subject registers',default=>'All recorded sessions' };
            elseif ($key === 'aging') $value = match ($value) { '1_30'=>'1–30 days','31_60'=>'31–60 days','61_90'=>'61–90 days',default=>'Over 90 days' };
            else $value = ucwords(str_replace('_',' ',(string)$value));
            $items[$label] = (string) $value;
        }
        return $items;
    }

    private function attendanceNotes(): array
    {
        return [
            'Attendance % = (Present + Late) ÷ (Present + Late + Absent + Leave) × 100. Leave remains in the denominator; late counts as attended. No records means N/A, not 0%. This is based on marked sessions, not a scheduled-school-day calendar.',
            'All registers combines general daily and subject registers as separate recorded sessions. Choose General register or a subject for a comparable rate. Duplicate student/date/subject entries use the newest record. Unmarked days are not assumed absent.',
            'Profile program / semester fields are current. Attendance’s recorded-class fields retain the class saved on each attendance record. In summary view, attendance-status filters select students while their percentages still use all matching sessions.',
        ];
    }

    private function stat(string $label, mixed $value, string $format = 'integer'): array
    {
        return ['label' => $label,'value' => $value,'format' => $format];
    }

    private function checkSize(int $count): void
    {
        if ($count > config('reports.max_rows',30000)) throw ValidationException::withMessages(['report' => 'This report is too large. Narrow the dates, program, class or search filters, then try again.']);
    }
}
