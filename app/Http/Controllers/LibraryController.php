<?php

namespace App\Http\Controllers;

use App\Models\Book;
use App\Models\BookIssue;
use App\Models\Program;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\Request;
use Carbon\Carbon;

class LibraryController extends Controller
{
    public function index(Request $request)
    {
        $query = Book::with(['program', 'schoolClass']);

        if ($request->search) {
            $query->where(function ($q) use ($request) {
                $q->where('name', 'like', "%{$request->search}%")
                  ->orWhere('author', 'like', "%{$request->search}%")
                  ->orWhere('isbn', 'like', "%{$request->search}%")
                  ->orWhere('category', 'like', "%{$request->search}%");
            });
        }
        if ($request->filled('category')) {
            $query->where('category', $request->category);
        }

        $items = $query->latest()->paginate(20)->withQueryString();

        $stats = [
            'titles'       => Book::count(),
            'copies'       => Book::sum('copies'),
            'available'    => Book::sum('available_copies'),
            'issued_now'   => BookIssue::where('status', 'issued')->count(),
            'overdue_now'  => BookIssue::where('status', 'issued')->where('due_date', '<', Carbon::today())->count(),
        ];

        $categories = Book::whereNotNull('category')->distinct()->pluck('category')->filter()->sort()->values();

        return view('library.index', compact('items', 'stats', 'categories'));
    }

    /** Library-wide list of every issuance (issued, overdue, and returned), with filters. */
    public function issuedBooks(Request $request)
    {
        $query = BookIssue::with(['book', 'student']);

        if ($request->filled('search')) {
            $search = $request->input('search');
            $query->where(function ($q) use ($search) {
                $q->where('student_name', 'like', "%{$search}%")
                  ->orWhere('student_roll_no', 'like', "%{$search}%")
                  ->orWhere('contact_number', 'like', "%{$search}%")
                  ->orWhereHas('book', fn ($b) => $b->where('name', 'like', "%{$search}%"));
            });
        }

        if ($request->input('status') === 'overdue') {
            $query->where('status', 'issued')->where('due_date', '<', Carbon::today());
        } elseif ($request->input('status') === 'issued') {
            $query->where('status', 'issued')->where('due_date', '>=', Carbon::today());
        } elseif ($request->input('status') === 'returned') {
            $query->where('status', 'returned');
        }

        $issues = $query->latest('issue_date')->paginate(20)->withQueryString();

        $stats = [
            'issued'   => BookIssue::where('status', 'issued')->where('due_date', '>=', Carbon::today())->count(),
            'overdue'  => BookIssue::where('status', 'issued')->where('due_date', '<', Carbon::today())->count(),
            'returned' => BookIssue::where('status', 'returned')->count(),
        ];

        return view('library.issued', compact('issues', 'stats'));
    }

    public function create()
    {
        $programs   = Program::orderBy('name')->get();
        $classes    = SchoolClass::with('program')->orderBy('name')->get();
        $categories = Book::whereNotNull('category')->distinct()->pluck('category')->filter()->sort()->values();
        return view('library.form', compact('programs', 'classes', 'categories'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'isbn'             => 'nullable|string|max:20|unique:books',
            'author'           => 'nullable|string|max:255',
            'category'         => 'nullable|string|max:100',
            'copies'           => 'required|integer|min:1',
            'rack'             => 'nullable|string|max:20',
            'program_id'       => 'nullable|exists:programs,id',
            'school_class_id'  => 'nullable|exists:school_classes,id',
            'industry'         => 'nullable|string|max:255',
        ]);
        $validated['available_copies'] = $validated['copies'];
        Book::create($validated);
        return redirect()->route('admin.library.index')->with('success', 'Book added to the library!');
    }

    public function show(Book $library)
    {
        $library->load(['program', 'schoolClass']);
        $issues = $library->issues()->with('student')->latest('issue_date')->get();
        return view('library.show', compact('library', 'issues'));
    }

    public function edit(Book $library)
    {
        $programs   = Program::orderBy('name')->get();
        $classes    = SchoolClass::with('program')->orderBy('name')->get();
        $categories = Book::whereNotNull('category')->distinct()->pluck('category')->filter()->sort()->values();
        return view('library.form', compact('library', 'programs', 'classes', 'categories'));
    }

    public function update(Request $request, Book $library)
    {
        $validated = $request->validate([
            'name'             => 'required|string|max:255',
            'isbn'             => 'nullable|string|max:20|unique:books,isbn,' . $library->id,
            'author'           => 'nullable|string|max:255',
            'category'         => 'nullable|string|max:100',
            'copies'           => 'required|integer|min:1',
            'rack'             => 'nullable|string|max:20',
            'program_id'       => 'nullable|exists:programs,id',
            'school_class_id'  => 'nullable|exists:school_classes,id',
            'industry'         => 'nullable|string|max:255',
        ]);

        // Keep available_copies consistent if the total copy count changes.
        $diff = $validated['copies'] - $library->copies;
        $validated['available_copies'] = max(0, $library->available_copies + $diff);

        $library->update($validated);
        return redirect()->route('admin.library.index')->with('success', 'Book updated!');
    }

    public function destroy(Book $library)
    {
        $library->delete();
        return redirect()->route('admin.library.index')->with('success', 'Book deleted!');
    }

    /** Detailed "Issue Book" form for a specific title. */
    public function issueForm(Book $library)
    {
        if ($library->available_copies < 1) {
            return redirect()->route('admin.library.show', $library)->with('error', 'No copies available to issue right now.');
        }
        return view('library.issue', compact('library'));
    }

    /** Store a new issuance against a book, linked to a student. */
    public function issue(Request $request, Book $library)
    {
        if ($library->available_copies < 1) {
            return back()->with('error', 'No copies available!');
        }

        $validated = $request->validate([
            'student_id'       => 'nullable|exists:students,id',
            'student_name'     => 'required|string|max:255',
            'student_roll_no'  => 'nullable|string|max:50',
            'class_name'       => 'nullable|string|max:255',
            'program_name'     => 'nullable|string|max:255',
            'semester'         => 'nullable|string|max:50',
            'contact_number'   => 'required|string|max:30',
            'days'             => 'required|integer|min:1|max:180',
            'fine_per_day'     => 'nullable|numeric|min:0',
        ]);

        $issueDate = Carbon::today();
        $dueDate   = $issueDate->copy()->addDays((int) $validated['days']);

        BookIssue::create([
            'book_id'          => $library->id,
            'student_id'       => $validated['student_id'] ?? null,
            'student_name'     => $validated['student_name'],
            'student_roll_no'  => $validated['student_roll_no'] ?? null,
            'class_name'       => $validated['class_name'] ?? null,
            'program_name'     => $validated['program_name'] ?? null,
            'semester'         => $validated['semester'] ?? null,
            'contact_number'   => $validated['contact_number'],
            'issue_date'       => $issueDate,
            'days'             => $validated['days'],
            'due_date'         => $dueDate,
            'fine_per_day'     => $validated['fine_per_day'] ?? 0,
            'status'           => 'issued',
        ]);

        $library->decrement('available_copies');

        return redirect()->route('admin.library.show', $library)
            ->with('success', "Book issued to {$validated['student_name']} — due back " . $dueDate->format('d M, Y') . '.');
    }

    /** Mark a specific issuance as returned and calculate any overdue fine. */
    public function returnIssue(BookIssue $bookIssue)
    {
        if ($bookIssue->status === 'returned') {
            return back()->with('error', 'This copy has already been returned.');
        }

        $bookIssue->fine_amount    = $bookIssue->estimatedFine();
        $bookIssue->status         = 'returned';
        $bookIssue->returned_date  = Carbon::today();
        $bookIssue->save();

        $book = $bookIssue->book;
        if ($book && $book->available_copies < $book->copies) {
            $book->increment('available_copies');
        }

        $msg = 'Book returned successfully!';
        if ($bookIssue->fine_amount > 0) {
            $msg .= ' Overdue fine: Rs. ' . number_format($bookIssue->fine_amount, 2) . '.';
        }

        return back()->with('success', $msg);
    }

    /**
     * AJAX lookup used on the Issue Book form: type a Student ID / Roll
     * Number / Name and get matching students with all their details,
     * so the rest of the form can auto-fill.
     */
    public function studentLookup(Request $request)
    {
        $q = trim((string) $request->get('q'));
        if ($q === '') {
            return response()->json([]);
        }

        $students = Student::with(['schoolClass.program'])
            ->where('status', 'active')
            ->where(function ($query) use ($q) {
                $query->where('student_id', 'like', "%{$q}%")
                      ->orWhere('roll_number', 'like', "%{$q}%")
                      ->orWhere('name', 'like', "%{$q}%");
            })
            ->limit(10)
            ->get();

        return response()->json($students->map(function (Student $s) {
            return [
                'id'          => $s->id,
                'name'        => $s->name,
                'student_id'  => $s->student_id,
                'roll_number' => $s->roll_number,
                'class_name'  => $s->schoolClass?->section_label ?? $s->schoolClass?->name,
                'program_name'=> $s->schoolClass?->program?->name,
                'semester'    => $s->schoolClass?->name,
                'phone'       => $s->phone,
                'photo'       => $s->photo ? storage_asset($s->photo) : null,
            ];
        }));
    }
}
