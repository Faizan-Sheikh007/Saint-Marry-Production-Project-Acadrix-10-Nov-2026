<?php

use App\Http\Controllers\ReportController;
use Illuminate\Support\Facades\Route;

/* Loaded from web.php. Existing report URLs and route names are retained. */
$registerEnhancedReports = function (bool $teacher = false, bool $writeRemarks = false): void {
    $types = $teacher ? ['students','attendance'] : \App\Support\Reports\ReportCatalog::TYPES;
    Route::get('/reports/{type}/pdf',[ReportController::class,'pdfReport'])->whereIn('type',$types)->name('reports.pdf');
    Route::get('/reports/{type}/print',[ReportController::class,'printReport'])->whereIn('type',$types)->name('reports.print');
    Route::get('/reports/students/{student}',[ReportController::class,'studentShow'])->whereNumber('student')->name('reports.students.show');
    Route::get('/reports/students/{student}/pdf',[ReportController::class,'studentPdf'])->whereNumber('student')->name('reports.students.pdf');
    Route::get('/reports/students/{student}/print',[ReportController::class,'studentPrint'])->whereNumber('student')->name('reports.students.print');
    Route::get('/reports/attendance/{student}',[ReportController::class,'attendanceShow'])->whereNumber('student')->name('reports.attendance.show');
    Route::get('/reports/attendance/{student}/pdf',[ReportController::class,'attendanceStudentPdf'])->whereNumber('student')->name('reports.attendance.pdf');
    Route::get('/reports/attendance/{student}/print',[ReportController::class,'attendanceStudentPrint'])->whereNumber('student')->name('reports.attendance.print');
    if ($writeRemarks) Route::post('/reports/students/{student}/remarks',[ReportController::class,'storeStudentRemark'])
        ->whereNumber('student')->middleware('throttle:30,1')->name('reports.students.remarks.store');
};

Route::middleware(['auth:web','role:admin'])->prefix('admin')->name('admin.')->group(function () use ($registerEnhancedReports) {
    $registerEnhancedReports(false,true);
    foreach (['collection' => 'collection','defaulters' => 'defaulters','installments' => 'installments','discounts' => 'discounts','class-revenue' => 'classRevenue','income-expense' => 'incomeExpense'] as $path => $action) {
        Route::get('/reports/'.$path,[ReportController::class,$action])->name('reports.'.$path);
    }
});

Route::middleware(['auth:accounts','role:accountant'])->prefix('accounts')->name('accounts.')->group(function () use ($registerEnhancedReports) {
    $registerEnhancedReports();
});

Route::middleware(['auth:web','role:teacher,admin'])->prefix('teacher')->name('teacher.')->group(function () use ($registerEnhancedReports) {
    $registerEnhancedReports(true,true);
    Route::get('/reports/students',[ReportController::class,'studentsReport'])->name('reports.students');
    Route::get('/reports/attendance',[ReportController::class,'attendanceReport'])->name('reports.attendance');
    Route::get('/reports/export/{type}',[ReportController::class,'export'])->whereIn('type',['students','attendance'])->name('reports.export');
});
unset($registerEnhancedReports);
