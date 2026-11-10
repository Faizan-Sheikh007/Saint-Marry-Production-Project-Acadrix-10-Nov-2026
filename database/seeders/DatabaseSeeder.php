<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use App\Models\{
    User, Subject, Teacher, Program, SchoolClass, Student, ParentModel,
    FeeHead, FeeTemplate, Voucher, Payment,
    Activity, Setting, Book,
    Course, Lesson, Assignment, Quiz, QuizQuestion, LmsAnnouncement, CourseEnrollment
};

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        // ── 1. Core accounts: admin, accountant, one parent (portal logins) ──
        User::updateOrCreate(['email' => 'admin@acadrix.com'], [
            'name' => 'Admin User', 'password' => Hash::make('password'), 'role' => User::ROLE_ADMIN,
        ]);
        User::updateOrCreate(['email' => 'accounts@acadrix.com'], [
            'name' => 'Accounts Officer', 'password' => Hash::make('password'), 'role' => User::ROLE_ACCOUNTANT,
        ]);
        $parentUser = User::updateOrCreate(['email' => 'parent@acadrix.com'], [
            'name' => 'Parent User', 'password' => Hash::make('password'), 'role' => User::ROLE_PARENT,
        ]);
        $parent = ParentModel::firstOrCreate(
            ['email' => 'parent@acadrix.com'],
            ['name' => 'Parent User', 'phone' => '0300-1112223', 'user_id' => $parentUser->id]
        );
        $this->command->info('admin@acadrix.com / accounts@acadrix.com / parent@acadrix.com — password: password');

        // ── 2. Subjects ──────────────────────────────────────────
        $subjectData = [
            ['name' => 'Mathematics', 'code' => 'MATH'], ['name' => 'English Language', 'code' => 'ENG'],
            ['name' => 'Physics', 'code' => 'PHY'], ['name' => 'Chemistry', 'code' => 'CHEM'],
            ['name' => 'Computer Science', 'code' => 'CS'], ['name' => 'Business Studies', 'code' => 'BUS'],
        ];
        $subjects = collect($subjectData)->map(fn ($s) => Subject::firstOrCreate(['code' => $s['code']], $s));

        // ── 3. Teachers (with login accounts) ─────────────────────
        $teacherData = [
            ['Muhammad Ali', 'mali@acadrix.com', '0321-1234567', 'male', 'M.Sc Mathematics', 45000],
            ['Ayesha Siddiqui', 'asiddiqui@acadrix.com', '0312-2345678', 'female', 'M.A English', 40000],
            ['Tariq Mahmood', 'tmahmood@acadrix.com', '0333-3456789', 'male', 'M.Sc Physics', 48000],
            ['Fatima Noor', 'fnoor@acadrix.com', '0345-4567890', 'female', 'M.Sc Chemistry', 42000],
            ['Hassan Raza', 'hraza@acadrix.com', '0311-5678901', 'male', 'BS Computer Science', 50000],
        ];
        $teachers = collect($teacherData)->map(function ($t, $i) use ($subjects) {
            $user = User::updateOrCreate(['email' => $t[1]], [
                'name' => $t[0], 'password' => Hash::make('password'), 'role' => User::ROLE_TEACHER,
            ]);
            $teacher = Teacher::firstOrCreate(['email' => $t[1]], [
                'user_id' => $user->id, 'employee_id' => 'EMP-' . str_pad($i + 1, 4, '0', STR_PAD_LEFT),
                'name' => $t[0], 'email' => $t[1], 'phone' => $t[2], 'gender' => $t[3],
                'qualification' => $t[4], 'salary' => $t[5], 'subject_id' => $subjects[$i]->id,
                'joining_date' => now()->subMonths(rand(6, 36))->format('Y-m-d'), 'status' => 'active',
            ]);
            $teacher->update(['user_id' => $user->id]);
            return $teacher;
        });
        $this->command->info('5 teacher accounts created (password: password)');

        // ── 4. Programs -> Classes/Sections (shared academic reference data) ──
        $programData = [
            ['name' => 'Grade 9',  'code' => 'G9',   'level' => 'college'],
            ['name' => 'Grade 10', 'code' => 'G10',  'level' => 'college'],
            ['name' => 'BSCS',     'code' => 'BSCS', 'level' => 'university', 'duration_terms' => 8],
        ];
        $programs = collect($programData)->map(fn ($p) => Program::firstOrCreate(
            ['code' => $p['code']],
            ['name' => $p['name'], 'level' => $p['level'], 'duration_terms' => $p['duration_terms'] ?? null]
        ));

        $classData = [
            ['program' => 'G9',   'name' => 'Grade 9',  'section' => 'A', 'level' => 'college'],
            ['program' => 'G10',  'name' => 'Grade 10', 'section' => 'A', 'level' => 'college'],
            ['program' => 'G10',  'name' => 'Grade 10', 'section' => 'B', 'level' => 'college'],
            ['program' => 'BSCS', 'name' => 'Semester 3', 'section' => null, 'level' => 'university'],
        ];
        $classes = collect($classData)->map(function ($c) use ($programs) {
            $program = $programs->firstWhere('code', $c['program']);
            return SchoolClass::firstOrCreate(
                ['program_id' => $program->id, 'name' => $c['name'], 'section' => $c['section']],
                ['level' => $c['level'], 'capacity' => 40]
            );
        });

        // ── 5. Students (with login accounts + one family linked to the demo parent) ──
        $studentData = [
            ['Aisha Khan', 'female', 'Ahmed Khan', '0321-9876543'],
            ['Muhammad Raza', 'male', 'Raza Hussain', '0312-8765432'],
            ['Sara Ahmed', 'female', 'Zafar Ahmed', '0333-7654321'],
            ['Fatima Hussain', 'female', 'Ali Hussain', '0345-6543210'],
            ['Omar Baig', 'male', 'Baig Sahib', '0311-5432109'],
            ['Zara Malik', 'female', 'Iqbal Malik', '0321-4321098'],
            ['Ahmad Tariq', 'male', 'Tariq Sahib', '0312-3210987'],
            ['Bilal Mehmood', 'male', 'Mehmood Sahib', '0333-2109876'],
        ];
        $students = collect($studentData)->map(function ($s, $i) use ($classes, $parent) {
            $email = strtolower(str_replace(' ', '.', $s[0])) . '@student.acadrix.com';
            $user  = User::updateOrCreate(['email' => $email], [
                'name' => $s[0], 'password' => Hash::make('password'), 'role' => User::ROLE_STUDENT,
            ]);
            $class = $classes[$i % $classes->count()];
            $studentId = 'STU-' . now()->year . '-' . str_pad($i + 1001, 4, '0', STR_PAD_LEFT);

            $student = Student::firstOrCreate(['student_id' => $studentId], [
                'user_id' => $user->id, 'student_id' => $studentId, 'name' => $s[0],
                'gender' => $s[1], 'guardian_name' => $s[2], 'guardian_phone' => $s[3], 'phone' => $s[3],
                'school_class_id' => $class->id, 'program_id' => $class->program_id,
                'section' => $class->section, 'level' => $class->level,
                'roll_number' => str_pad($i + 1, 2, '0', STR_PAD_LEFT),
                'session' => now()->year . '-' . (now()->year + 1),
                'admission_date' => now()->subMonths(rand(1, 12))->format('Y-m-d'),
                'date_of_birth' => now()->subYears(rand(12, 22))->format('Y-m-d'),
                'status' => 'active', 'fee_status' => 'unpaid',
                'address' => 'Gujranwala, Punjab, Pakistan',
                'attendance_percentage' => rand(70, 100),
                // First two demo students belong to the demo parent, so the Parent
                // portal has real children to show installment plans/vouchers for.
                'parent_id' => $i < 2 ? $parent->id : null,
            ]);
            $student->update(['user_id' => $user->id]);
            return $student;
        });
        $this->command->info('8 student accounts (e.g. aisha.khan@student.acadrix.com / password)');

        // ── 6. Fee Heads & a sample Fee Template (Accounts module) ──────
        $feeHeadData = [
            ['name' => 'Tuition Fee',   'category' => 'tuition',   'type' => 'recurring', 'default_amount' => 3500],
            ['name' => 'Admission Fee', 'category' => 'admission', 'type' => 'one_time',  'default_amount' => 10000],
            ['name' => 'Examination Fee', 'category' => 'exam',    'type' => 'one_time',  'default_amount' => 1500],
            ['name' => 'Lab Fee',       'category' => 'lab',       'type' => 'recurring', 'default_amount' => 800],
            ['name' => 'Transport Fee', 'category' => 'transport', 'type' => 'recurring', 'default_amount' => 2000],
            ['name' => 'ID Card Fee',   'category' => 'id_card',   'type' => 'one_time',  'default_amount' => 300],
        ];
        $feeHeads = collect($feeHeadData)->map(fn ($f) => FeeHead::firstOrCreate(['name' => $f['name']], $f));

        $template = FeeTemplate::firstOrCreate(
            ['name' => 'Grade 9/10 Monthly Fee Plan'],
            ['school_class_id' => $classes[0]->id, 'billing_cycle' => 'monthly', 'is_active' => true]
        );
        if ($template->items()->count() === 0) {
            $template->items()->create(['fee_head_id' => $feeHeads[0]->id, 'amount' => 3500]);
            $template->items()->create(['fee_head_id' => $feeHeads[3]->id, 'amount' => 800]);
        }

        // ── 7. Sample Vouchers + Payments (single source of truth for fees) ──
        $receiptSeq = 1;
        foreach ($students as $i => $student) {
            foreach (range(0, 2) as $m) {
                $month = now()->startOfYear()->addMonths($m);
                $voucher = Voucher::firstOrCreate(
                    ['student_id' => $student->id, 'fee_head_id' => $feeHeads[0]->id, 'month' => $month->format('F Y')],
                    [
                        'voucher_no' => 'VCH-' . now()->year . '-' . str_pad($receiptSeq++, 5, '0', STR_PAD_LEFT),
                        'title'      => 'Tuition Fee - ' . $month->format('F Y'),
                        'amount'     => 3500,
                        'due_date'   => $month->copy()->addDays(10),
                        'issued_at'  => $month,
                        'status'     => $m < 2 ? 'paid' : 'unpaid',
                        'paid_amount' => $m < 2 ? 3500 : 0,
                    ]
                );
                if ($m < 2 && $voucher->payments()->count() === 0) {
                    Payment::create([
                        'voucher_id' => $voucher->id, 'amount' => 3500, 'method' => 'cash',
                        'paid_at' => $month->copy()->addDays(5), 'received_by' => 'Admin User',
                    ]);
                }
            }
        }
        $this->command->info('Sample vouchers + payments seeded for all students.');

        // ── 8. LMS: one sample course per teacher, auto-enrol classmates ──
        $courseTitles = [
            ['Mathematics Grade 9', 'master algebra, geometry and problem solving'],
            ['English Communication', 'improve reading, writing and speaking skills'],
            ['Physics Fundamentals', 'explore mechanics, electricity and modern physics'],
            ['Chemistry Lab Course', 'understand chemical reactions and laboratory techniques'],
            ['Computer Science 101', 'learn programming, algorithms and digital literacy'],
        ];
        foreach ($teachers as $i => $teacher) {
            [$title, $desc] = $courseTitles[$i];
            $course = Course::firstOrCreate(['title' => $title, 'teacher_id' => $teacher->id], [
                'description' => 'This course helps students ' . $desc . '.',
                'subject_id' => $subjects[$i]->id,
                'school_class_id' => $classes[$i % $classes->count()]->id,
                'teacher_id' => $teacher->id,
                'status' => 'published', 'order' => $i + 1,
            ]);

            $lessonTitles = ['Introduction & Overview', 'Core Concepts Part 1', 'Core Concepts Part 2', 'Practice & Application', 'Review & Assessment'];
            foreach ($lessonTitles as $j => $lessonTitle) {
                Lesson::firstOrCreate(['course_id' => $course->id, 'title' => $lessonTitle], [
                    'description' => 'Lesson ' . ($j + 1) . ' of the ' . $title . ' course.',
                    'content' => '<h2>' . $lessonTitle . '</h2><p>This lesson covers important concepts related to ' . $title . '.</p>',
                    'type' => $j === 1 ? 'video' : 'text',
                    'video_url' => $j === 1 ? 'https://www.youtube.com/watch?v=dQw4w9WgXcQ' : null,
                    'order' => $j + 1, 'duration_minutes' => ($j + 1) * 10, 'is_free' => $j === 0,
                ]);
            }

            Assignment::firstOrCreate(['course_id' => $course->id, 'title' => 'Assignment 1: Fundamentals'], [
                'description' => 'Complete the following exercises to demonstrate your understanding.',
                'instructions' => 'Answer all questions. Submit before the deadline.',
                'due_date' => now()->addDays(14), 'total_marks' => 50, 'status' => 'active',
            ]);

            $quiz = Quiz::firstOrCreate(['course_id' => $course->id, 'title' => 'Quiz 1: Basic Concepts'], [
                'description' => 'A short quiz to test your understanding of the core concepts.',
                'time_limit' => 20, 'total_marks' => 10, 'passing_marks' => 5,
                'attempts_allowed' => 2, 'status' => 'active', 'due_date' => now()->addDays(7),
            ]);
            if ($quiz->questions()->count() === 0) {
                QuizQuestion::create(['quiz_id' => $quiz->id, 'question' => 'Which of the following is correct?', 'type' => 'mcq', 'options' => ['Option A', 'Option B', 'Option C', 'Option D'], 'correct_answer' => 'Option A', 'marks' => 2, 'order' => 1]);
                QuizQuestion::create(['quiz_id' => $quiz->id, 'question' => 'The Earth revolves around the Sun.', 'type' => 'true_false', 'options' => ['True', 'False'], 'correct_answer' => 'True', 'marks' => 1, 'order' => 2]);
            }

            LmsAnnouncement::firstOrCreate(['course_id' => $course->id, 'title' => 'Welcome to ' . $title], [
                'content' => 'Welcome to the course! Please go through all lessons and complete assignments on time.',
                'teacher_id' => $teacher->id, 'pinned' => true,
            ]);

            foreach (Student::where('school_class_id', $course->school_class_id)->get() as $student) {
                CourseEnrollment::firstOrCreate(
                    ['course_id' => $course->id, 'student_id' => $student->id],
                    ['enrolled_at' => now(), 'status' => 'active', 'progress_percent' => rand(0, 60)]
                );
            }
        }
        $this->command->info('5 LMS courses with lessons, quizzes, assignments seeded.');

        // ── 9. Misc EMS reference data (library) ──────────────────────────
        foreach ([
            ['Mathematics Grade 9', 'Punjab Textbook Board', 'Textbook', 20],
            ['English Grammar Advanced', 'Raymond Murphy', 'Reference', 10],
        ] as $b) {
            Book::firstOrCreate(['name' => $b[0]], ['author' => $b[1], 'category' => $b[2], 'copies' => $b[3], 'available_copies' => $b[3]]);
        }

        // ── 10. Settings (institution profile + accounts config) ─────────
        $settingsData = [
            'institution_name' => 'Acadrix Institute', 'campus_name' => 'Main Campus',
            'academic_session' => now()->year . '-' . (now()->year + 1), 'currency' => 'PKR',
            'installment_max' => '5', 'fine_type' => 'percent', 'fine_percent' => '2',
            'fine_fixed' => '200', 'grace_days' => '5',
            'attendance_hold_enabled' => '1', 'attendance_threshold' => '75',
            'id_card_reissue_fee' => '300', 'bank_name' => '', 'bank_account_title' => '', 'bank_account_no' => '',
        ];
        foreach ($settingsData as $key => $value) {
            Setting::firstOrCreate(['key' => $key], ['value' => $value]);
        }

        if (Activity::count() === 0) {
            foreach ([
                ['New student Aisha Khan enrolled', 'fas fa-user-plus', 'success'],
                ['Voucher paid by Muhammad Raza', 'fas fa-money-bill', 'success'],
                ['Midterm exam scheduled for Grade 9', 'fas fa-file-pen', 'primary'],
                ['LMS course Mathematics published', 'fas fa-book-open', 'purple'],
            ] as $a) {
                Activity::create(['description' => $a[0], 'icon' => $a[1], 'color' => $a[2]]);
            }
        }

        $this->command->info('');
        $this->command->info('✅  Acadrix (EMS + Accounts) seeded! Login credentials:');
        $this->command->info('   Admin      → admin@acadrix.com          / password');
        $this->command->info('   Accountant → accounts@acadrix.com       / password');
        $this->command->info('   Teacher    → mali@acadrix.com           / password');
        $this->command->info('   Student    → aisha.khan@student.acadrix.com / password');
        $this->command->info('   Parent     → parent@acadrix.com         / password');
    }
}
