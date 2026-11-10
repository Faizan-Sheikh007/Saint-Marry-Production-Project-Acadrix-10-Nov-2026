<?php

namespace App\Services;

/**
 * Tiny dependency-free PDF writer used for report downloads.
 *
 * It intentionally focuses on text/table reports and uses the PDF core fonts
 * (Helvetica/Courier), so deployments do not need a separate PDF package,
 * wkhtmltopdf, Chromium, or a composer dependency just to download a report.
 */
class SimplePdfDocument
{
    private float $pageWidth;
    private float $pageHeight;
    private float $margin = 36;
    private array $pages = [[]];
    private int $pageIndex = 0;
    private float $cursorY;

    public function __construct(string $orientation = 'portrait')
    {
        if ($orientation === 'landscape') {
            $this->pageWidth = 841.89;
            $this->pageHeight = 595.28;
        } else {
            $this->pageWidth = 595.28;
            $this->pageHeight = 841.89;
        }

        $this->cursorY = $this->pageHeight - $this->margin;
    }

    public function title(string $text): self
    {
        $this->ensureSpace(28);
        $this->writeLine($text, 16, 'F2', 20);
        $this->cursorY -= 3;
        return $this;
    }

    public function subtitle(string $text): self
    {
        $this->writeWrapped($text, 9, 'F1', 12, 95);
        $this->cursorY -= 5;
        return $this;
    }

    public function section(string $text): self
    {
        $this->cursorY -= 6;
        $this->ensureSpace(22);
        $this->writeLine(strtoupper($text), 11, 'F2', 16);
        return $this;
    }

    public function keyValue(string $label, mixed $value): self
    {
        $value = $value === null || $value === '' ? '—' : (string) $value;
        $this->writeWrapped($label . ': ' . $value, 9, 'F1', 12, 100);
        return $this;
    }

    public function text(string $text, int $size = 9, bool $bold = false): self
    {
        $this->writeWrapped($text, $size, $bold ? 'F2' : 'F1', $size + 3, 105);
        return $this;
    }

    public function blank(float $height = 7): self
    {
        $this->cursorY -= $height;
        return $this;
    }

    /**
     * Render a fixed-width text table. Widths are expressed in characters.
     * Cells are truncated rather than wrapped so each record remains one row.
     */
    public function table(array $headers, iterable $rows, array $widths): self
    {
        $widths = array_values($widths);
        $headers = array_values($headers);
        $separator = implode('-+-', array_map(fn ($w) => str_repeat('-', max(1, (int) $w)), $widths));

        $renderHeader = function () use ($headers, $widths, $separator): void {
            $this->writeLine($this->formatTableRow($headers, $widths), 7, 'F4', 10);
            $this->writeLine($separator, 7, 'F3', 10);
        };

        $this->ensureSpace(30);
        $renderHeader();

        $hasRows = false;
        foreach ($rows as $row) {
            $hasRows = true;
            if ($this->cursorY < $this->margin + 24) {
                $this->newPage();
                $renderHeader();
            }
            $this->writeLine($this->formatTableRow(array_values($row), $widths), 7, 'F3', 10);
        }

        if (! $hasRows) {
            $this->writeLine('No records found.', 8, 'F1', 11);
        }

        $this->cursorY -= 4;
        return $this;
    }

    public function footer(string $text): self
    {
        $this->cursorY -= 8;
        $this->writeWrapped($text, 7, 'F1', 10, 110);
        return $this;
    }

    public function output(): string
    {
        $objects = [];

        // 1 = Catalog, 2 = Pages. Fonts live at 3..6.
        $objects[1] = '<< /Type /Catalog /Pages 2 0 R >>';
        $objects[3] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>';
        $objects[4] = '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica-Bold >>';
        $objects[5] = '<< /Type /Font /Subtype /Type1 /BaseFont /Courier >>';
        $objects[6] = '<< /Type /Font /Subtype /Type1 /BaseFont /Courier-Bold >>';

        $pageObjectIds = [];
        $nextId = 7;

        foreach ($this->pages as $pageCommands) {
            $pageId = $nextId++;
            $contentId = $nextId++;
            $pageObjectIds[] = $pageId;

            $stream = implode("\n", $pageCommands);
            $objects[$contentId] = "<< /Length " . strlen($stream) . " >>\nstream\n{$stream}\nendstream";
            $objects[$pageId] = sprintf(
                '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 %.2f %.2f] /Resources << /Font << /F1 3 0 R /F2 4 0 R /F3 5 0 R /F4 6 0 R >> >> /Contents %d 0 R >>',
                $this->pageWidth,
                $this->pageHeight,
                $contentId
            );
        }

        $kids = implode(' ', array_map(fn ($id) => $id . ' 0 R', $pageObjectIds));
        $objects[2] = '<< /Type /Pages /Kids [' . $kids . '] /Count ' . count($pageObjectIds) . ' >>';
        ksort($objects);

        $pdf = "%PDF-1.4\n%\xE2\xE3\xCF\xD3\n";
        $offsets = [0 => 0];
        foreach ($objects as $id => $body) {
            $offsets[$id] = strlen($pdf);
            $pdf .= "{$id} 0 obj\n{$body}\nendobj\n";
        }

        $xrefOffset = strlen($pdf);
        $maxId = max(array_keys($objects));
        $pdf .= "xref\n0 " . ($maxId + 1) . "\n";
        $pdf .= "0000000000 65535 f \n";
        for ($id = 1; $id <= $maxId; $id++) {
            $offset = $offsets[$id] ?? 0;
            $pdf .= sprintf("%010d 00000 n \n", $offset);
        }
        $pdf .= "trailer\n<< /Size " . ($maxId + 1) . " /Root 1 0 R >>\nstartxref\n{$xrefOffset}\n%%EOF";

        return $pdf;
    }

    /** Begin another copy without exposing drawing internals. */
    public function pageBreak(): self
    {
        $this->newPage();
        return $this;
    }

    private function newPage(): void
    {
        $this->pages[] = [];
        $this->pageIndex++;
        $this->cursorY = $this->pageHeight - $this->margin;
    }

    private function ensureSpace(float $height): void
    {
        if ($this->cursorY - $height < $this->margin) {
            $this->newPage();
        }
    }

    private function writeWrapped(string $text, int $size, string $font, int $lineHeight, int $maxChars): void
    {
        $clean = trim(preg_replace('/\s+/', ' ', $this->latin($text)) ?? '');
        if ($clean === '') {
            $this->cursorY -= $lineHeight;
            return;
        }

        $wrapped = wordwrap($clean, $maxChars, "\n", true);
        foreach (explode("\n", $wrapped) as $line) {
            $this->writeLine($line, $size, $font, $lineHeight);
        }
    }

    private function writeLine(string $text, int $size, string $font, int $lineHeight): void
    {
        $this->ensureSpace($lineHeight + 2);
        $x = $this->margin;
        $y = $this->cursorY;
        $escaped = $this->escapePdfText($this->latin($text));
        $this->pages[$this->pageIndex][] = sprintf(
            'BT /%s %d Tf 1 0 0 1 %.2f %.2f Tm (%s) Tj ET',
            $font,
            $size,
            $x,
            $y,
            $escaped
        );
        $this->cursorY -= $lineHeight;
    }

    private function formatTableRow(array $row, array $widths): string
    {
        $cells = [];
        foreach ($widths as $i => $width) {
            $value = $this->latin((string) ($row[$i] ?? ''));
            $width = max(1, (int) $width);
            if (strlen($value) > $width) {
                $value = $width <= 1 ? substr($value, 0, $width) : substr($value, 0, $width - 1) . '…';
                $value = $this->latin($value);
            }
            $cells[] = str_pad($value, $width);
        }
        return implode(' | ', $cells);
    }

    private function latin(string $text): string
    {
        $text = str_replace(["\r", "\n", "\t"], ' ', $text);
        if (function_exists('iconv')) {
            $converted = @iconv('UTF-8', 'Windows-1252//TRANSLIT//IGNORE', $text);
            if ($converted !== false) {
                return $converted;
            }
        }
        return preg_replace('/[^\x20-\x7E]/', '?', $text) ?? '';
    }

    private function escapePdfText(string $text): string
    {
        return str_replace(['\\', '(', ')'], ['\\\\', '\\(', '\\)'], $text);
    }
}
