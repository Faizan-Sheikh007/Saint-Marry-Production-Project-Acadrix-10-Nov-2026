<?php

namespace App\Http\Controllers;

use App\Models\MedicalLabInventory;
use Illuminate\Http\Request;

class MedicalLabInventoryController extends Controller
{
    public function index(Request $request)
    {
        $selectedLab = trim((string) $request->query('lab', ''));
        $itemSearch = trim((string) $request->query('item', ''));

        $query = MedicalLabInventory::query();
        if ($selectedLab !== '') {
            $query->where('category', $selectedLab);
        }
        if ($itemSearch !== '') {
            // Inventory search is intentionally limited to the item name.
            // It does not search lab/category, remarks, or descriptions.
            $query->where('name', 'like', '%' . $itemSearch . '%');
        }

        $items = $query->latest()->paginate(20)->withQueryString();
        $labs = MedicalLabInventory::query()
            ->whereNotNull('category')
            ->where('category', '<>', '')
            ->distinct()
            ->orderBy('category')
            ->pluck('category');

        return view('medical-lab-inventory.index', compact('items', 'labs', 'selectedLab', 'itemSearch'));
    }

    /**
     * Download the inventory list as a PDF without adding a third-party PDF
     * dependency. The same optional lab/category filter used on the index
     * page is respected by the export.
     */
    public function exportPdf(Request $request)
    {
        $selectedLab = trim((string) $request->query('lab', ''));
        $itemSearch = trim((string) $request->query('item', ''));

        $query = MedicalLabInventory::query()->orderBy('category')->orderBy('name');
        if ($selectedLab !== '') {
            $query->where('category', $selectedLab);
        }
        if ($itemSearch !== '') {
            $query->where('name', 'like', '%' . $itemSearch . '%');
        }

        $items = $query->get();
        $pdf = $this->buildInventoryPdf($items, $selectedLab, $itemSearch);
        $fileName = 'medical-lab-inventory-list-report-' . now()->format('Y-m-d') . '.pdf';

        return response($pdf, 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $fileName . '"',
            'Content-Length' => (string) strlen($pdf),
        ]);
    }

    public function create()
    {
        return view('medical-lab-inventory.form');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'category'     => 'nullable|string|max:100',
            'quantity'     => 'required|integer|min:0',
            'unit'         => 'nullable|string|max:20',
            'price'        => 'nullable|numeric',
            'min_quantity' => 'nullable|integer|min:0',
            'description'  => 'nullable|string',
            'expiry_date'  => 'nullable|date',
            'remarks'      => 'nullable|string',
        ]);
        MedicalLabInventory::create($validated);

        return redirect()->route('admin.medical-lab-inventory.index')->with('success', 'Item added!');
    }

    public function show(MedicalLabInventory $medical_lab_inventory)
    {
        return view('medical-lab-inventory.show', ['item' => $medical_lab_inventory]);
    }

    public function edit(MedicalLabInventory $medical_lab_inventory)
    {
        return view('medical-lab-inventory.form', ['item' => $medical_lab_inventory]);
    }

    public function update(Request $request, MedicalLabInventory $medical_lab_inventory)
    {
        $validated = $request->validate([
            'name'         => 'required|string|max:255',
            'category'     => 'nullable|string|max:100',
            'quantity'     => 'required|integer|min:0',
            'unit'         => 'nullable|string|max:20',
            'price'        => 'nullable|numeric',
            'min_quantity' => 'nullable|integer|min:0',
            'description'  => 'nullable|string',
            'expiry_date'  => 'nullable|date',
            'remarks'      => 'nullable|string',
        ]);
        $medical_lab_inventory->update($validated);

        return redirect()->route('admin.medical-lab-inventory.index')->with('success', 'Inventory updated!');
    }

    public function destroy(MedicalLabInventory $medical_lab_inventory)
    {
        $medical_lab_inventory->delete();

        return redirect()->route('admin.medical-lab-inventory.index')->with('success', 'Item deleted!');
    }

    private function buildInventoryPdf($items, string $selectedLab, string $itemSearch = ''): string
    {
        $lines = [];
        $lines[] = 'MEDICAL LAB INVENTORY - LIST REPORT';
        $lines[] = 'Lab: ' . ($selectedLab !== '' ? $selectedLab : 'All Labs');
        $lines[] = 'Item search: ' . ($itemSearch !== '' ? $itemSearch : 'All Items');
        $lines[] = 'Generated: ' . now()->format('d-m-Y H:i');
        $lines[] = str_repeat('-', 119);
        $lines[] = sprintf(
            '%-4s %-34s %-26s %7s %-7s %11s %-11s %-10s',
            '#', 'ITEM NAME', 'LAB / CATEGORY', 'QTY', 'UNIT', 'UNIT PRICE', 'EXPIRY', 'STATUS'
        );
        $lines[] = str_repeat('-', 119);

        foreach ($items as $index => $item) {
            $status = $item->quantity <= $item->min_quantity ? 'Low Stock' : 'Active';
            $price = $item->price !== null ? 'Rs.' . number_format((float) $item->price, 0) : '-';
            $expiry = $item->expiry_date ? $item->expiry_date->format('d-m-Y') : '-';

            $lines[] = sprintf(
                '%-4s %-34s %-26s %7s %-7s %11s %-11s %-10s',
                $index + 1,
                $this->pdfColumn($item->name, 34),
                $this->pdfColumn($item->category ?: '-', 26),
                $this->pdfColumn((string) $item->quantity, 7),
                $this->pdfColumn($item->unit ?: '-', 7),
                $this->pdfColumn($price, 11),
                $this->pdfColumn($expiry, 11),
                $this->pdfColumn($status, 10)
            );
        }

        if ($items->isEmpty()) {
            $lines[] = 'No inventory records found for this filter.';
        }

        $lines[] = str_repeat('-', 119);
        $lines[] = 'Total records: ' . $items->count()
            . '    Total quantity: ' . number_format((float) $items->sum('quantity'), 0);

        // Landscape A4. Keep enough room for a repeated report heading on
        // every page while fitting the inventory list cleanly.
        $pageLineLimit = 36;
        $pages = array_chunk($lines, $pageLineLimit);
        $pageCount = count($pages);

        foreach ($pages as $pageIndex => &$pageLines) {
            if ($pageIndex > 0) {
                array_unshift(
                    $pageLines,
                    'MEDICAL LAB INVENTORY - LIST REPORT - continued (page ' . ($pageIndex + 1) . ' of ' . $pageCount . ')',
                    str_repeat('-', 119)
                );
            }
        }
        unset($pageLines);

        return $this->makeSimplePdf($pages);
    }

    private function pdfColumn($value, int $width): string
    {
        $text = preg_replace('/\s+/u', ' ', trim((string) $value)) ?? '';
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
            if ($converted !== false) {
                $text = $converted;
            }
        }
        $text = str_replace(["\r", "\n", "\t"], ' ', $text);

        if (strlen($text) > $width) {
            $text = substr($text, 0, max(0, $width - 3)) . '...';
        }

        return $text;
    }

    /**
     * Tiny dependency-free text PDF writer for the inventory report.
     * It uses the PDF built-in Courier font, so deployment only requires the
     * existing Laravel/PHP application files.
     */
    private function makeSimplePdf(array $pages): string
    {
        $objects = [];
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>';

        $pageRefs = [];
        $nextObject = 4;

        foreach ($pages as $pageLines) {
            $pageObject = $nextObject++;
            $contentObject = $nextObject++;
            $pageRefs[] = $pageObject . ' 0 R';

            $content = "BT\n/F1 9 Tf\n24 560 Td\n13 TL\n";
            foreach ($pageLines as $line) {
                $line = $this->escapePdfText($line);
                $content .= '(' . $line . ") Tj\nT*\n";
            }
            $content .= "ET\n";

            $objects[$contentObject] = "<< /Length " . strlen($content) . " >>\nstream\n" . $content . "endstream";
            $objects[$pageObject] = '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 842 595] '
                . '/Resources << /Font << /F1 3 0 R >> >> /Contents ' . $contentObject . ' 0 R >>';
        }

        $objects[2] = '<< /Type /Pages /Kids [' . implode(' ', $pageRefs) . '] /Count ' . count($pageRefs) . ' >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0 => 0];
        foreach ($objects as $number => $object) {
            $offsets[$number] = strlen($pdf);
            $pdf .= $number . " 0 obj\n" . $object . "\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $maxObject = max(array_keys($objects));
        $pdf .= "xref\n0 " . ($maxObject + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($i = 1; $i <= $maxObject; $i++) {
            $pdf .= sprintf('%010d 00000 n ', $offsets[$i] ?? 0) . "\n";
        }
        $pdf .= "trailer\n<< /Size " . ($maxObject + 1) . " /Root 1 0 R >>\n";
        $pdf .= "startxref\n" . $xrefOffset . "\n%%EOF";

        return $pdf;
    }

    private function escapePdfText(string $text): string
    {
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
            if ($converted !== false) {
                $text = $converted;
            }
        }

        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
