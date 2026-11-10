<?php

namespace App\Services\Reports;

use Illuminate\Validation\ValidationException;

final class ReportPdf
{
    public function download(string $view, array $data, string $filename, string $orientation = 'landscape')
    {
        abort_unless(class_exists(\Barryvdh\DomPDF\Facade\Pdf::class), 503,
            'PDF renderer is not installed. An administrator must run: composer require barryvdh/laravel-dompdf:^3.1 --with-all-dependencies');
        $count = isset($data['rows']) ? $data['rows']->count() :
            $data['attendanceRecords']->count()+$data['examRows']->count()+$data['feeRows']->count()+$data['payments']->count();
        if ($count > config('reports.max_pdf_rows',2000)) {
            throw ValidationException::withMessages(['pdf' => 'This PDF would be too large. Narrow the dates or filters, or use CSV for the full dataset.']);
        }
        $pdf = \Barryvdh\DomPDF\Facade\Pdf::loadView($view, $data + ['pdfMode' => true])
            ->setPaper('a4',$orientation)->setOptions(['isRemoteEnabled' => false,'isPhpEnabled' => false,'defaultFont' => 'DejaVu Sans']);
        $pdf->render();
        $engine = $pdf->getDomPDF();
        $engine->add_info('Title',$data['title']);
        $canvas = $engine->getCanvas();
        $font = $engine->getFontMetrics()->getFont('DejaVu Sans','normal');
        $canvas->page_text($canvas->get_width()-110,$canvas->get_height()-24,'Page {PAGE_NUM} of {PAGE_COUNT}',$font,8,[0.35,0.35,0.35]);
        return $pdf->download($filename)->withHeaders(['Cache-Control' => 'private, no-store, max-age=0','Pragma' => 'no-cache','X-Content-Type-Options' => 'nosniff']);
    }
}
