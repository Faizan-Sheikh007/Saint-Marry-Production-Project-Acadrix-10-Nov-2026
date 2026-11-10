<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\ParentModel;
use App\Models\Program;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\Voucher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * Header search bar — one endpoint shared by every role.
 *
 * Returns grouped, role-scoped record matches. Page/menu matches are done
 * client-side from the sidebar links, so every role (including student and
 * parent) gets a working search without exposing extra data.
 */
class GlobalSearchController extends Controller
{
    public function search(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        if (mb_strlen($q) < 2) {
            return response()->json(['groups' => []]);
        }

        $user   = auth()->user();
        $role   = $user?->role;
        $like   = '%' . str_replace(['\\', '%', '_'], ['\\\\', '\\%', '\\_'], $q) . '%';
        $groups = [];

        if (in_array($role, ['admin', 'accountant'], true)) {
            $groups[] = $this->students($like, $role);
            $groups[] = $this->teachers($like, $role);
            $groups[] = $this->vouchers($like);
        }

        if ($role === 'admin') {
            $groups[] = $this->programs($like);
            $groups[] = $this->subjects($like);
            $groups[] = $this->parents($like);
            $groups[] = $this->books($like);
        }

        if ($role === 'teacher') {
            $groups[] = $this->teacherStudents($like, $user);
        }

        return response()->json([
            'groups' => array_values(array_filter($groups, fn ($g) => $g && count($g['items']))),
        ]);
    }

    private function group(string $label, string $icon, $items): array
    {
        return ['label' => $label, 'icon' => $icon, 'items' => $items->values()->all()];
    }

    private function students(string $like, string $role): ?array
    {
        $routeName = $role === 'admin' ? 'students.show' : 'accounts.students.show';
        if (!Route::has($routeName)) return null;

        $rows = Student::with('program')
            ->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                  ->orWhere('student_id', 'like', $like)
                  ->orWhere('roll_number', 'like', $like)
                  ->orWhere('father_name', 'like', $like)
                  ->orWhere('phone', 'like', $like)
                  ->orWhere('cnic', 'like', $like)
                  ->orWhere('ledger_number', 'like', $like);
            })
            ->orderBy('name')->limit(6)->get();

        return $this->group('Students', 'fa-user-graduate', $rows->map(fn ($s) => [
            'title' => $s->name,
            'meta'  => trim(($s->student_id ?: '') . ($s->program?->name ? ' · ' . $s->program->name : '') . ' · Student status: ' . $s->statusLabel()),
            'url'   => route($routeName, $s->id),
        ]));
    }

    private function teachers(string $like, string $role): ?array
    {
        $routeName = $role === 'admin' ? 'admin.teachers.show' : 'accounts.teachers.show';
        if (!Route::has($routeName)) return null;

        $rows = Teacher::where(function ($q) use ($like) {
                $q->where('name', 'like', $like)
                  ->orWhere('employee_id', 'like', $like)
                  ->orWhere('email', 'like', $like)
                  ->orWhere('phone', 'like', $like);
            })
            ->orderBy('name')->limit(5)->get();

        return $this->group('Teachers', 'fa-chalkboard-teacher', $rows->map(fn ($t) => [
            'title' => $t->name,
            'meta'  => trim(($t->employee_id ?: '') . ($t->designation ? ' · ' . $t->designation : '')),
            'url'   => route($routeName, $t->id),
        ]));
    }

    private function vouchers(string $like): ?array
    {
        if (!Route::has('vouchers.show')) return null;

        $rows = Voucher::with('student:id,name,student_id')
            ->where(function ($q) use ($like) {
                $q->where('voucher_no', 'like', $like)
                  ->orWhere('title', 'like', $like)
                  ->orWhereHas('student', fn ($s) => $s->where('name', 'like', $like)->orWhere('student_id', 'like', $like));
            })
            ->latest('id')->limit(6)->get();

        return $this->group('Vouchers', 'fa-file-invoice', $rows->map(fn ($v) => [
            'title' => $v->voucher_no ?: ('Voucher #' . $v->id),
            'meta'  => trim(($v->student?->name ?: '') . ' · ' . ucfirst((string) $v->status), ' ·'),
            'url'   => route('vouchers.show', $v->id),
        ]));
    }

    private function programs(string $like): ?array
    {
        if (!Route::has('programs.show')) return null;
        $rows = Program::where('name', 'like', $like)->orWhere('code', 'like', $like)->orderBy('name')->limit(5)->get();
        return $this->group('Programs', 'fa-sitemap', $rows->map(fn ($p) => [
            'title' => $p->name, 'meta' => $p->code ?: '', 'url' => route('programs.show', $p->id),
        ]));
    }

    private function subjects(string $like): ?array
    {
        if (!Route::has('subjects.show')) return null;
        $rows = Subject::where('name', 'like', $like)->orWhere('code', 'like', $like)->orderBy('name')->limit(5)->get();
        return $this->group('Subjects', 'fa-book', $rows->map(fn ($s) => [
            'title' => $s->name, 'meta' => $s->code ?: '', 'url' => route('subjects.show', $s->id),
        ]));
    }

    private function parents(string $like): ?array
    {
        if (!Route::has('admin.parents.show')) return null;
        $rows = ParentModel::where(function ($q) use ($like) {
                $q->where('name', 'like', $like)->orWhere('email', 'like', $like)
                  ->orWhere('phone', 'like', $like)->orWhere('cnic', 'like', $like);
            })->orderBy('name')->limit(5)->get();
        return $this->group('Parents', 'fa-users', $rows->map(fn ($p) => [
            'title' => $p->name, 'meta' => $p->phone ?: ($p->email ?: ''), 'url' => route('admin.parents.show', $p->id),
        ]));
    }

    private function books(string $like): ?array
    {
        if (!Route::has('admin.library.show')) return null;
        $rows = Book::where('name', 'like', $like)->orWhere('isbn', 'like', $like)->orWhere('author', 'like', $like)->orderBy('name')->limit(5)->get();
        return $this->group('Library', 'fa-book-open', $rows->map(fn ($b) => [
            'title' => $b->name, 'meta' => $b->author ?? '', 'url' => route('admin.library.show', $b->id),
        ]));
    }

    /** Teachers only ever see students in classes they actually teach. */
    private function teacherStudents(string $like, $user): ?array
    {
        $teacher = $user->teacher ?? null;
        if (!$teacher || !Route::has('teacher.students.fee-detail')) return null;

        $classIds = $teacher->classes()->pluck('school_classes.id');
        $rows = Student::whereIn('school_class_id', $classIds)
            ->where(function ($q) use ($like) {
                $q->where('name', 'like', $like)->orWhere('student_id', 'like', $like)->orWhere('roll_number', 'like', $like);
            })
            ->orderBy('name')->limit(8)->get();

        return $this->group('My Students', 'fa-user-graduate', $rows->map(fn ($s) => [
            'title' => $s->name, 'meta' => ($s->student_id ?: '').' · Student status: '.$s->statusLabel(), 'url' => route('teacher.students.fee-detail', $s->id),
        ]));
    }
}
