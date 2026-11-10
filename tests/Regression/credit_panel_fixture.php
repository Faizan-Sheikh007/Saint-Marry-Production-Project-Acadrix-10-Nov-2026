<?php
/**
 * Render ONLY the shared credit partial through a limited fixture translator.
 * This is not the Laravel Blade compiler. Run blade_update_20261029.php on staging.
 * No DB, real session, real routes or existing student records are accessed.
 */
declare(strict_types=1);
$root = dirname(__DIR__, 2);
$output = getenv('ACADRIX_QA_OUTPUT') ?: sys_get_temp_dir().'/acadrix-credit-ui';
if (!is_dir($output)) mkdir($output, 0770, true);
function route(string $name, mixed $arguments): string {
    $values = is_array($arguments) ? $arguments : [$arguments];
    return '/fixture/'.$name.'/'.implode('/', array_map(static fn ($v) => is_object($v) ? $v->id : (string) $v, $values));
}
function escaped(mixed $value): string { return htmlspecialchars((string) $value, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'); }
function compilePartial(string $source): string {
    $source = preg_replace('/\{\{--.*?--\}\}/s', '', $source);
    $source = preg_replace_callback('/\{\{\s*(.*?)\s*\}\}/s', static fn ($m) => '<?php echo escaped('.$m[1].'); ?>', $source);
    $source = str_replace(['@once', '@endonce', '@csrf'], ['', '', '<input type="hidden" name="_token" value="fixture-only">'], $source);
    foreach (['forelse', 'elseif', 'if'] as $directive) {
        $offset = 0;
        while (($start = strpos($source, '@'.$directive.'(', $offset)) !== false) {
            $open = $start + strlen($directive) + 1;
            $depth = 1; $quote = null; $end = $open + 1;
            for (; $end < strlen($source); $end++) {
                $c = $source[$end];
                if ($quote !== null) { if ($c === '\\') $end++; elseif ($c === $quote) $quote = null; continue; }
                if ($c === '"' || $c === "'") { $quote = $c; continue; }
                if ($c === '(') $depth++;
                if ($c === ')' && --$depth === 0) break;
                // The decrement above only runs for a closing parenthesis due to short-circuit evaluation.
            }
            if ($depth !== 0) throw new RuntimeException('Unclosed fixture directive');
            $expression = substr($source, $open + 1, $end - $open - 1);
            $replacement = $directive === 'forelse'
                ? '<?php $__fixtureEmpty = true; foreach ('.$expression.'): $__fixtureEmpty = false; ?>'
                : '<?php '.$directive.' ('.$expression.'): ?>';
            $source = substr_replace($source, $replacement, $start, $end - $start + 1);
            $offset = $start + strlen($replacement);
        }
    }
    return str_replace(['@endforelse', '@empty', '@else', '@endif'], [
        '<?php endif; ?>', '<?php endforeach; if ($__fixtureEmpty): ?>', '<?php else: ?>', '<?php endif; ?>',
    ], $source);
}
$source = file_get_contents($root.'/resources/views/partials/student-credit.blade.php');
$compiled = compilePartial($source);
token_get_all($compiled, TOKEN_PARSE);
$stylesheetSource = file_get_contents($root.'/resources/views/vouchers/index.blade.php');
preg_match_all('/<style>(.*?)<\/style>/s', $stylesheetSource, $matches);
$css = implode("\n", $matches[1]);
$base = ':root{--primary:#820b2c;--success:#08b47c;--danger:#ee4444;--warning:#d79500;--text-muted:#607088;--card:#fff;--border:#dfe3eb;--radius:14px;--shadow:0 2px 8px #18283d0b;--bg:#f3f5f8}*{box-sizing:border-box}body{margin:0;padding:16px;font-family:Arial,sans-serif;background:#edf0f5;color:#14263a}.fixture{max-width:510px;margin:auto}.badge{display:inline-block;padding:5px 10px;border-radius:16px;font-size:11px}.badge.success{background:#e2f6ed;color:#047954}.fixture-notice{font-size:11px;color:#695132;margin-bottom:10px}.semester-card{min-width:0}.btn{padding:7px;border:1px solid #bfc7d4;border-radius:6px;background:white;font-size:11px}';
$cases = [
    'available' => ['credit' => '10000.00', 'pending' => 0, 'receipt' => true],
    'consumed' => ['credit' => '0.00', 'pending' => 0, 'receipt' => true],
    'no-receipt' => ['credit' => '10000.00', 'pending' => 0, 'receipt' => false],
    'older-dues' => ['credit' => '10000.00', 'pending' => 15000, 'receipt' => true],
    'unicode' => ['credit' => '10000.00', 'pending' => 0, 'receipt' => true],
];
foreach ($cases as $name => $case) {
    $student = (object) ['id' => 740];
    $creditOverview = [
        'available' => $case['credit'], 'available_minor' => (int) round((float) $case['credit'] * 100),
        'display_cycle' => 5,
        'receipts' => $case['receipt'] ? [[
            'source' => 'legacy', 'id' => 123, 'number' => $name === 'unicode' ? '<script>bad</script> & علی' : 'EPR-L-PREVIEW',
            'amount' => '50000.00', 'date_label' => 'Entry recorded', 'date' => '29 Oct 2026',
        ]] : [],
    ];
    $studentPending = $case['pending'];
    ob_start(); eval('?>'.$compiled); $panel = ob_get_clean();
    $rows = '';
    foreach ([1,2,3,4] as $number) {
        $rows .= '<div class="semester-installment-row"><div class="semester-installment-main"><div class="semester-installment-title">Semester 5 | Installment '.$number.'</div><div class="semester-installment-sub">Tuition fee</div></div><div class="semester-installment-amount">Rs. 0</div><span class="badge success">Paid</span></div>';
    }
    $html = '<!doctype html><html><head><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><style>'.$base.$css.'</style></head><body><div class="fixture"><div class="fixture-notice">DEMO - example data, not a live student account.</div><div class="semester-card status-paid"><div class="semester-head"><div><div class="semester-title">Semester 5 of 8</div><div class="semester-sub">HND 5th Semester Fee Plan - 4 installments</div></div><span class="badge success">Paid</span></div><div class="semester-installments">'.$rows.'</div>'.$panel.'<div class="semester-links"><a href="#">Full Plan Details</a><a href="#">Print Challans</a><a href="#">Download Summary</a></div></div></div></body></html>';
    file_put_contents($output.'/credit-'.$name.'.html', $html);
}
echo 'PASS: '.count($cases).' credit-panel HTML fixtures generated with a limited translator (NOT Laravel Blade).' . "\n";
