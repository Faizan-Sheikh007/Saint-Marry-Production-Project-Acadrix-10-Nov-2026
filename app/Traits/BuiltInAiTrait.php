<?php

namespace App\Traits;

/**
 * BuiltInAiTrait
 * ───────────────
 * A small, fully offline "AI" text generator used for the admin briefing
 * and report summary features.
 *
 * IMPORTANT: this is intentionally NOT wired to any external LLM API
 * (OpenAI, Claude, etc.) — it is a deterministic, rule-based summarizer
 * that reads the "Label: value" data lines embedded in the prompt and
 * turns them into a short, natural-sounding paragraph with a couple of
 * threshold-based observations. Zero network calls, zero API cost,
 * works instantly even with no internet connection.
 *
 * If a real LLM is ever wired in later, this method's signature
 * ($prompt -> string) can stay exactly the same, so none of the
 * call sites (AdminDashboardController, ReportController) need to change.
 */
trait BuiltInAiTrait
{
    public function builtInAI(string $prompt): string
    {
        $metrics = $this->extractMetrics($prompt);

        if (empty($metrics)) {
            return "Not enough data yet to generate an insight — check back once there's more activity recorded.";
        }

        $sentences = [];

        // Opening line: weave the first couple of metrics into a sentence.
        $headline = array_slice($metrics, 0, 2);
        $parts = array_map(fn ($m) => "{$m['label']} at {$m['display']}", $headline);
        $sentences[] = ucfirst(implode(', ', $parts)) . '.';

        // Threshold-based observations (rule-based "insight").
        foreach ($metrics as $m) {
            $label = strtolower($m['label']);
            $value = $m['value'];

            if (str_contains($label, 'attendance') && $m['isPercent']) {
                if ($value < 70) {
                    $sentences[] = "Attendance is running low today ({$m['display']}) — worth a quick nudge to class teachers.";
                } elseif ($value >= 95) {
                    $sentences[] = "Attendance is excellent today at {$m['display']}.";
                }
            }

            if ((str_contains($label, 'outstanding') || str_contains($label, 'unpaid') || str_contains($label, 'overdue')) && $value > 0) {
                $sentences[] = "There {$this->isAre($value)} {$m['display']} outstanding — a reminder push could help close the gap.";
            }

            if (str_contains($label, 'pending') && $value > 0) {
                $sentences[] = ucfirst($m['label']) . " currently stand{$this->s($value)} at {$m['display']} and may need review.";
            }
        }

        // Compare a "highest" figure against an "average" figure if both exist.
        $highest = $this->findMetric($metrics, 'highest');
        $average = $this->findMetric($metrics, 'average');
        if ($highest && $average && $average['value'] > 0 && $highest['value'] > $average['value'] * 1.8) {
            $sentences[] = "One figure ({$highest['display']}) is well above the average of {$average['display']} — worth a closer look.";
        }

        // Keep it concise: cap at 4 sentences.
        $sentences = array_slice(array_unique($sentences), 0, 4);

        if (count($sentences) === 1) {
            $sentences[] = "Nothing looks unusual right now — everything is tracking within a normal range.";
        }

        return implode(' ', $sentences);
    }

    /**
     * Pulls "Label: 1,234" / "Label: 87%" style pairs out of the free-text
     * prompt (the controllers embed data as bullet lines before asking a
     * question). Returns them in the order they appear.
     */
    protected function extractMetrics(string $prompt): array
    {
        $metrics = [];

        preg_match_all('/([A-Za-z][A-Za-z \/]{2,40}?):\s*(Rs\.?\s*)?([\d,]+(?:\.\d+)?)(%?)/', $prompt, $matches, PREG_SET_ORDER);

        foreach ($matches as $match) {
            $label      = trim($match[1]);
            $isCurrency = trim($match[2]) !== '';
            $rawValue   = str_replace(',', '', $match[3]);
            $isPercent  = $match[4] === '%';
            $value      = (float) $rawValue;

            $display = $isCurrency
                ? 'Rs.' . number_format($value)
                : ($isPercent ? $value . '%' : number_format($value));

            $metrics[] = [
                'label'     => $label,
                'value'     => $value,
                'display'   => $display,
                'isPercent' => $isPercent,
            ];
        }

        return $metrics;
    }

    protected function findMetric(array $metrics, string $needle): ?array
    {
        foreach ($metrics as $m) {
            if (str_contains(strtolower($m['label']), $needle)) {
                return $m;
            }
        }
        return null;
    }

    protected function isAre(float $value): string
    {
        return $value == 1 ? 'is' : 'are';
    }

    protected function s(float $value): string
    {
        return $value == 1 ? 's' : '';
    }
}
