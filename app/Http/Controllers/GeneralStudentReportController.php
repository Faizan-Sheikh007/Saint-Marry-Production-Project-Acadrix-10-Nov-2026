<?php

namespace App\Http\Controllers;

use App\Models\AcademicYear;
use App\Models\Program;
use App\Models\SchoolClass;
use App\Models\Student;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use ZipArchive;

class GeneralStudentReportController extends Controller
{
    private const COLUMNS = [
        'name' => 'Name', 'father_name' => 'Father Name', 'address' => 'Address',
        'phone' => 'Mobile', 'student_id' => 'Student ID', 'roll_number' => 'Roll Number',
        'program' => 'Program', 'semester' => 'Semester / Part', 'session' => 'Academic Session',
        'email' => 'Email', 'status' => 'Student Status',
    ];

    public function index(Request $request)
    {
        $selected = $this->columns($request);
        $query = $this->filtered($request);
        $students = $query->paginate(120)->withQueryString();
        return view('reports.general', [
            'students' => $students, 'columns' => self::COLUMNS, 'selected' => $selected,
            'programs' => Program::orderBy('name')->get(),
            'semesters' => SchoolClass::orderBy('name')->get(),
            'sessions' => AcademicYear::orderBy('name')->get(),
        ]);
    }

    public function export(Request $request)
    {
        $format = Validator::make($request->all(), [
            'format' => 'required|in:pdf,csv,xlsx',
        ])->validate()['format'];
        $selected = $this->columns($request);
        $headers = array_map(fn ($k) => self::COLUMNS[$k], $selected);
        $students = $this->filtered($request)->get();
        $rows = $students->map(fn ($student) => array_map(fn ($key) => $this->value($student, $key), $selected))->all();
        $filename = 'general-student-report-' . now()->format('Ymd-His');

        if ($format === 'pdf') {
            return view('reports.print.general', [
                'students' => $students,
                'selected' => $selected,
                'columns' => self::COLUMNS,
                'program' => $request->filled('program_id') ? Program::find($request->program_id) : null,
                'semester' => $request->filled('semester_id') ? SchoolClass::find($request->semester_id) : null,
                'session' => $request->filled('academic_year_id') ? AcademicYear::find($request->academic_year_id) : null,
            ]);
        }

        if ($format === 'csv') {
            return response()->streamDownload(function () use ($headers, $rows) {
                $out = fopen('php://output', 'w');
                fwrite($out, "\xEF\xBB\xBF"); // Excel-friendly UTF-8
                fputcsv($out, $headers);
                foreach ($rows as $row) {
                    // Prevent spreadsheet formula execution on untrusted student data.
                    fputcsv($out, array_map(fn ($v) => preg_match('/^[\s]*[=+@\-]/u', (string) $v) ? "'" . $v : $v, $row));
                }
                fclose($out);
            }, $filename . '.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
        }

        // Dependency-free valid XLSX spreadsheet using Office Open XML.
        abort_unless(class_exists(ZipArchive::class), 503, 'PHP ZipArchive extension is required for Excel exports.');
        $path = tempnam(sys_get_temp_dir(), 'acadrix_xlsx_');
        $zip = new ZipArchive();
        abort_unless($zip->open($path, ZipArchive::OVERWRITE | ZipArchive::CREATE) === true, 500);
        $zip->addFromString('[Content_Types].xml', '<?xml version="1.0" encoding="UTF-8"?><Types xmlns="http://schemas.openxmlformats.org/package/2006/content-types"><Default Extension="rels" ContentType="application/vnd.openxmlformats-package.relationships+xml"/><Default Extension="xml" ContentType="application/xml"/><Override PartName="/xl/workbook.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.sheet.main+xml"/><Override PartName="/xl/worksheets/sheet1.xml" ContentType="application/vnd.openxmlformats-officedocument.spreadsheetml.worksheet+xml"/></Types>');
        $zip->addFromString('_rels/.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/officeDocument" Target="xl/workbook.xml"/></Relationships>');
        $zip->addFromString('xl/workbook.xml', '<?xml version="1.0" encoding="UTF-8"?><workbook xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main" xmlns:r="http://schemas.openxmlformats.org/officeDocument/2006/relationships"><sheets><sheet name="Students" sheetId="1" r:id="rId1"/></sheets></workbook>');
        $zip->addFromString('xl/_rels/workbook.xml.rels', '<?xml version="1.0" encoding="UTF-8"?><Relationships xmlns="http://schemas.openxmlformats.org/package/2006/relationships"><Relationship Id="rId1" Type="http://schemas.openxmlformats.org/officeDocument/2006/relationships/worksheet" Target="worksheets/sheet1.xml"/></Relationships>');
        $xml = '<?xml version="1.0" encoding="UTF-8"?><worksheet xmlns="http://schemas.openxmlformats.org/spreadsheetml/2006/main"><sheetData>';
        foreach (array_merge([$headers], $rows) as $i => $row) {
            $xml .= '<row r="' . ($i + 1) . '">';
            foreach ($row as $value) {
                $safe = htmlspecialchars((string) $value, ENT_XML1 | ENT_QUOTES, 'UTF-8');
                $xml .= '<c t="inlineStr"><is><t xml:space="preserve">' . $safe . '</t></is></c>';
            }
            $xml .= '</row>';
        }
        $xml .= '</sheetData></worksheet>';
        $zip->addFromString('xl/worksheets/sheet1.xml', $xml);
        $zip->close();
        return response()->download($path, $filename . '.xlsx', [
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ])->deleteFileAfterSend(true);
    }

    private function columns(Request $request): array
    {
        $requested = $request->input('columns', ['name', 'father_name', 'address', 'phone']);
        if (!is_array($requested)) $requested = [];
        $selected = array_values(array_unique(array_filter($requested, fn ($key) => is_string($key) && array_key_exists($key, self::COLUMNS))));
        if (!$selected) $selected = ['name', 'father_name', 'address', 'phone'];
        if (!in_array('status', $selected, true)) $selected[] = 'status';
        return $selected;
    }

    private function filtered(Request $request)
    {
        Validator::make($request->all(), [
            'program_id' => 'nullable|integer|exists:programs,id',
            'semester_id' => 'nullable|integer|exists:school_classes,id',
            'academic_year_id' => 'nullable|integer|exists:academic_years,id',
            'student_status' => 'nullable|in:'.implode(',', \App\Support\StudentStatus::VALUES),
        ])->validate();
        return Student::with(['program', 'schoolClass', 'academicYear'])
            ->when($request->filled('program_id'), fn ($q) => $q->where('program_id', $request->program_id))
            ->when($request->filled('semester_id'), fn ($q) => $q->where('school_class_id', $request->semester_id))
            ->when($request->filled('academic_year_id'), fn ($q) => $q->where('academic_year_id', $request->academic_year_id))
            ->when($request->filled('student_status'), fn ($q) => $q->where('status', $request->input('student_status')))
            ->orderBy('name')->orderBy('id');
    }

    private function value(Student $student, string $column): string
    {
        return (string) match ($column) {
            'father_name' => $student->father_name ?: ($student->guardian_name ?: ''),
            'program' => $student->program?->name ?? '',
            'semester' => $student->schoolClass?->name ?? '',
            'session' => $student->academicYear?->name ?? $student->session ?? '',
            'status' => $student->statusLabel(),
            default => $student->{$column} ?? '',
        };
    }
}
