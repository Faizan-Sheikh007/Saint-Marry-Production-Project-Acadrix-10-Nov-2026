<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title>Question Paper</title>
<style>
  :root { --primary:#8b0c22; --ink:#1a1a1a; --muted:#555; --line:#333; --hair:#d4d4d4; }
  * { box-sizing:border-box; }
  body { font-family:Georgia,'Times New Roman',Times,serif; background:#e9edf2; margin:0; padding:24px; color:var(--ink); }
  .toolbar { max-width:210mm; margin:0 auto 14px; display:flex; justify-content:flex-end; gap:10px; font-family:'Segoe UI',Arial,sans-serif; }
  .toolbar button, .toolbar a { padding:9px 18px; border-radius:7px; border:none; background:var(--primary); color:#fff; font-size:13px; font-weight:600; cursor:pointer; text-decoration:none; display:inline-flex; align-items:center; gap:6px; }
  .toolbar a.secondary { background:#fff; color:var(--primary); border:1px solid var(--primary); }
  .print-help { max-width:210mm; margin:0 auto 16px; color:var(--muted); font:12px/1.5 'Segoe UI',Arial,sans-serif; }

  /* Unbordered A4 pages: screen and print use identical measurements, so the
     pagination calculated in the browser matches the printable pages. */
  .sheet { width:210mm; height:297mm; margin:0 auto 18px; padding:16mm 16mm 19mm; position:relative; background:#fff; border:0; box-shadow:0 1px 7px rgba(0,0,0,.14); }
  .sheet-inner, .page-content { width:100%; height:100%; padding:0; margin:0; border:0; outline:0; }
  .page-content { overflow:hidden; }
  .paper-pages { display:none; }
  body.is-paginated .paper-pages { display:block; }
  body.is-paginated #paper-source { display:none; }
  /* The source remains readable and printable if scripts are unavailable. */
  #paper-source { height:auto; min-height:297mm; }
  #paper-source .sheet-inner { height:auto; }

  .letterhead { text-align:center; }
  .letterhead .doc-name { font-size:14px; font-weight:700; letter-spacing:.25px; margin:0; text-transform:uppercase; }
  hr.rule { border:0; border-top:1px solid var(--line); margin:12px 0 11px; }

  .details-grid { display:flex; flex-direction:column; gap:6px; font-size:11.5px; margin-bottom:9px; }
  .metadata-line { display:flex; align-items:baseline; justify-content:space-between; gap:13px; width:100%; min-width:0; white-space:nowrap; }
  .metadata-line .detail { display:inline-flex; align-items:baseline; gap:4px; flex:0 0 auto; min-width:0; }
  .metadata-line .detail.program-detail { margin-right:auto; }
  .metadata-line .detail.subject-detail { margin-right:auto; }
  .metadata-line .lbl { font-weight:700; white-space:nowrap; }
  .metadata-line .lbl::after { content:':'; }
  .metadata-line .val { white-space:nowrap; }

  .instructions-box { font-size:11.5px; margin:10px 0; padding:8px 10px; border:1px dashed var(--hair); background:#fcfcfc; line-height:1.4; }
  .instructions-box strong { display:block; font-size:10px; text-transform:uppercase; letter-spacing:.2px; margin-bottom:3px; color:var(--primary); }
  .section-title { font-size:12px; font-weight:700; text-transform:uppercase; letter-spacing:.25px; margin:14px 0 5px; border-bottom:1px solid var(--line); padding-bottom:4px; display:flex; justify-content:space-between; gap:12px; }
  .section-title.is-continued { margin-top:7px; }
  .section-title.is-continued > span:last-child { display:none; }
  .q-list { list-style:none; margin:0; padding:0; }
  .q-list li { display:flex; gap:10px; padding:6px 0; border-bottom:1px dotted var(--hair); font-size:12px; line-height:1.32; break-inside:avoid; page-break-inside:avoid; overflow-wrap:anywhere; }
  .q-list li:last-child { border-bottom:none; }
  .q-list .qnum { font-weight:700; min-width:19px; flex-shrink:0; }
  .q-list .qtext { flex:1; min-width:0; }
  .q-list .qmarks { font-weight:700; white-space:nowrap; color:var(--primary); flex-shrink:0; }
  .q-list img { max-height:190mm; object-fit:contain; }
  .q-list li.question-continuation { border-top:0; }
  .q-list li.question-continuation .qnum { font-weight:400; color:var(--muted); }

  /* Footer is present only when this document actually has multiple pages.
     It replaces the old application footer, without a box or page border. */
  .page-navigation { position:absolute; left:16mm; right:16mm; bottom:9mm; height:5mm; display:flex; align-items:center; justify-content:center; font:10px/1 Arial,sans-serif; color:#444; }
  .page-navigation .turn-over { position:absolute; right:0; top:0; font-weight:700; letter-spacing:.3px; text-transform:uppercase; }

  /* Suppress automatically generated Chromium URL/date headers and footers.
     White margins are provided inside each A4 sheet above. */
  @page { size:A4 portrait; margin:0; }
  @media print {
    html, body { background:#fff; margin:0; padding:0; }
    .toolbar, .print-help { display:none !important; }
    .sheet { box-shadow:none; margin:0; width:210mm; height:297mm; break-after:page; page-break-after:always; }
    .sheet:last-child { break-after:auto; page-break-after:auto; }
    #paper-source { height:auto; min-height:0; }
    /* On an unusually old/no-JavaScript browser, preserve all questions. */
    body:not(.is-paginated) #paper-source { height:auto; }
    body:not(.is-paginated) #paper-source .sheet-inner { overflow:visible; }
    a[href]::after { content:none !important; }
  }
  @media screen and (max-width:840px) {
    body { padding:12px; }
    .toolbar { flex-wrap:wrap; justify-content:center; }
    .paper-pages, #paper-source { overflow-x:visible; }
  }
</style>
</head>
<body>

<div class="toolbar">
  <button type="button" onclick="window.print()">Download / Print PDF</button>
  <a href="javascript:history.back()" class="secondary">Back</a>
</div>
<p class="print-help">Print on A4 at 100% scale, with Headers and footers disabled. Page numbers and Turn Over appear automatically when the paper spans more than one page.</p>

@php
  $exam = $examPaper->examination;
@endphp

<div class="sheet" id="paper-source">
<div class="sheet-inner" id="paper-content-source">

  <div class="letterhead">
    <div class="doc-name">{{ $exam->name ?? 'Examination' }} — Question Paper</div>
  </div>

  <hr class="rule">

  <div class="details-grid">
    <div class="metadata-line">
      <div class="detail program-detail"><span class="lbl">Program</span><span class="val">{{ $exam->program->name ?? $exam->schoolClass->program->name ?? 'Not assigned' }}</span></div>
      <div class="detail"><span class="lbl">Semester / Part</span><span class="val">{{ $exam->schoolClass->section_label ?? '—' }}</span></div>
      <div class="detail"><span class="lbl">Session</span><span class="val">{{ $exam->session_label }}</span></div>
    </div>
    <div class="metadata-line">
      <div class="detail subject-detail"><span class="lbl">Subject</span><span class="val">{{ $exam->subject->name ?? '—' }}</span></div>
      <div class="detail"><span class="lbl">Total Marks</span><span class="val">{{ $exam->total_marks ?? '—' }}</span></div>
    </div>
  </div>

  @if($examPaper->instructions)
  <div class="instructions-box">
    <strong>Instructions</strong>
    {{ $examPaper->instructions }}
  </div>
  @endif

  @if($shortQuestions->isNotEmpty())
  <div class="section-title"><span>Section A — Short Questions</span><span>{{ $shortQuestions->sum('marks') ?: '' }}</span></div>
  <ul class="q-list">
    @foreach($shortQuestions as $q)
    <li style="flex-direction:column;align-items:stretch">
      <div style="display:flex;gap:10px">
        <span class="qnum">{{ $loop->iteration }}.</span>
        <span class="qtext">{{ $q->question }}</span>
        @if($q->marks)<span class="qmarks">[{{ $q->marks }}]</span>@endif
      </div>
      @if($q->image_path)
        <img src="{{ storage_asset($q->image_path) }}" style="max-width:220px;margin:6px 0 6px 30px;border:1px solid var(--hair)">
      @endif
    </li>
    @endforeach
  </ul>
  @endif

  @if($longQuestions->isNotEmpty())
  <div class="section-title"><span>Section B — Long Questions</span><span>{{ $longQuestions->sum('marks') ?: '' }}</span></div>
  <ul class="q-list">
    @foreach($longQuestions as $q)
    <li style="flex-direction:column;align-items:stretch">
      <div style="display:flex;gap:10px">
        <span class="qnum">{{ $loop->iteration }}.</span>
        <span class="qtext">{{ $q->question }}</span>
        @if($q->marks)<span class="qmarks">[{{ $q->marks }}]</span>@endif
      </div>
      @if($q->image_path)
        <img src="{{ storage_asset($q->image_path) }}" style="max-width:220px;margin:6px 0 6px 30px;border:1px solid var(--hair)">
      @endif
    </li>
    @endforeach
  </ul>
  @endif

  @php $sectionLetter = 67; @endphp

  @if($mcqQuestions->isNotEmpty())
  <div class="section-title"><span>Section {{ chr($sectionLetter) }} — Multiple Choice Questions</span><span>{{ $mcqQuestions->sum('marks') ?: '' }}</span></div>
  @php $sectionLetter++; @endphp
  <ul class="q-list">
    @foreach($mcqQuestions as $q)
    <li style="flex-direction:column;align-items:stretch">
      <div style="display:flex;gap:10px">
        <span class="qnum">{{ $loop->iteration }}.</span>
        <span class="qtext">{{ $q->question }}</span>
        @if($q->marks)<span class="qmarks">[{{ $q->marks }}]</span>@endif
      </div>
      @if($q->image_path)
        <img src="{{ storage_asset($q->image_path) }}" style="max-width:220px;margin:6px 0 6px 30px;border:1px solid var(--hair)">
      @endif
      <div style="margin-left:30px;display:grid;grid-template-columns:1fr 1fr;gap:4px 16px;font-size:12.5px">
        @foreach(['a','b','c','d'] as $opt)
          @if(!empty($q->options[$opt]))
            <div>({{ strtoupper($opt) }}) {{ $q->options[$opt] }}</div>
          @endif
        @endforeach
      </div>
    </li>
    @endforeach
  </ul>
  @endif

  @if($fillBlankQuestions->isNotEmpty())
  <div class="section-title"><span>Section {{ chr($sectionLetter) }} — Fill in the Blanks</span><span>{{ $fillBlankQuestions->sum('marks') ?: '' }}</span></div>
  @php $sectionLetter++; @endphp
  <ul class="q-list">
    @foreach($fillBlankQuestions as $q)
    <li style="flex-direction:column;align-items:stretch">
      <div style="display:flex;gap:10px">
        <span class="qnum">{{ $loop->iteration }}.</span>
        <span class="qtext">{{ $q->question }}</span>
        @if($q->marks)<span class="qmarks">[{{ $q->marks }}]</span>@endif
      </div>
      @if($q->image_path)
        <img src="{{ storage_asset($q->image_path) }}" style="max-width:220px;margin:6px 0 6px 30px;border:1px solid var(--hair)">
      @endif
    </li>
    @endforeach
  </ul>
  @endif

  @if($trueFalseQuestions->isNotEmpty())
  <div class="section-title"><span>Section {{ chr($sectionLetter) }} — True / False</span><span>{{ $trueFalseQuestions->sum('marks') ?: '' }}</span></div>
  @php $sectionLetter++; @endphp
  <ul class="q-list">
    @foreach($trueFalseQuestions as $q)
    <li style="flex-direction:column;align-items:stretch">
      <div style="display:flex;gap:10px">
        <span class="qnum">{{ $loop->iteration }}.</span>
        <span class="qtext">{{ $q->question }} <span style="color:var(--muted)">( True / False )</span></span>
        @if($q->marks)<span class="qmarks">[{{ $q->marks }}]</span>@endif
      </div>
      @if($q->image_path)
        <img src="{{ storage_asset($q->image_path) }}" style="max-width:220px;margin:6px 0 6px 30px;border:1px solid var(--hair)">
      @endif
    </li>
    @endforeach
  </ul>
  @endif

  @if($shortQuestions->isEmpty() && $longQuestions->isEmpty() && $mcqQuestions->isEmpty() && $fillBlankQuestions->isEmpty() && $trueFalseQuestions->isEmpty())
    <p style="text-align:center;color:var(--muted);padding:30px 0">No questions have been added to this paper yet.</p>
  @endif

</div>
</div>
<div class="paper-pages" id="paper-pages" aria-label="Paginated question paper"></div>

<script>
(function () {
  'use strict';
  const source = document.getElementById('paper-content-source');
  const pagesHost = document.getElementById('paper-pages');
  if (!source || !pagesHost) return;

  // Keep an inert master copy so recalculating after fonts/images load cannot
  // lose or duplicate questions. The page builder moves original nodes.
  const original = document.createElement('template');
  Array.from(source.children).forEach(node => original.content.appendChild(node.cloneNode(true)));
  let page = null;
  let currentList = null;
  let currentSection = null;
  let pages = [];

  function newPage() {
    const sheet = document.createElement('div');
    sheet.className = 'sheet';
    const body = document.createElement('div');
    body.className = 'page-content';
    sheet.appendChild(body);
    pagesHost.appendChild(sheet);
    page = { sheet, body };
    pages.push(page);
    return page;
  }

  function fits() {
    // A one-pixel tolerance absorbs fractional mm/pixel rounding in Chromium.
    return page.body.scrollHeight <= page.body.clientHeight + 1;
  }

  function continueSection() {
    newPage();
    const heading = currentSection.cloneNode(true);
    heading.classList.add('is-continued');
    const title = heading.querySelector('span:first-child');
    if (title) title.textContent = title.textContent.trim() + ' (continued)';
    currentList = document.createElement('ul');
    currentList.className = 'q-list';
    page.body.append(heading, currentList);
  }

  function fragment(item, words, start, end, first, includeExtras) {
    const clone = item.cloneNode(true);
    const questionText = clone.querySelector('.qtext');
    if (questionText) questionText.textContent = words.slice(start, end).join(' ') || '(continued)';
    if (!first) {
      clone.classList.add('question-continuation');
      const number = clone.querySelector('.qnum');
      if (number) number.textContent = '↳';
      const marks = clone.querySelector('.qmarks');
      if (marks) marks.remove();
    }
    if (!includeExtras) Array.from(clone.children).slice(1).forEach(extra => extra.remove());
    return clone;
  }

  function splitLargeQuestion(item) {
    const questionText = item.querySelector('.qtext');
    const words = questionText && questionText.textContent.trim()
      ? questionText.textContent.trim().split(/\s+/)
      : [];
    const hasExtras = item.children.length > 1;
    if (!words.length && !hasExtras) {
      // Empty answer prompts can still occupy the full section row.
      currentList.appendChild(item);
      return;
    }
    let position = 0;
    let part = 0;
    let extraPlaced = false;
    let safety = 0;
    while ((position < words.length || (hasExtras && !extraPlaced)) && ++safety < 10000) {
      const remaining = words.length - position;
      let low = 1;
      let high = remaining;
      let best = 0;
      // Find the largest text fragment that fits, including any diagram or
      // choice-grid only on the final fragment of the question.
      while (low <= high) {
        const mid = (low + high) >>> 1;
        const isLast = mid === remaining;
        const candidate = fragment(item, words, position, position + mid, part === 0, isLast);
        currentList.appendChild(candidate);
        const okay = fits();
        candidate.remove();
        if (okay) { best = mid; low = mid + 1; }
        else high = mid - 1;
      }
      if (best > 0) {
        const isLast = best === remaining;
        const candidate = fragment(item, words, position, position + best, part === 0, isLast);
        currentList.appendChild(candidate);
        position += best;
        if (isLast) extraPlaced = true;
        part++;
      } else if (remaining > 0) {
        // If a single unbroken word is too wide for the section's remaining
        // vertical space, retry it on a clean page; CSS wraps long words.
        const empty = currentList.children.length === 0 && page.body.children.length === 2;
        if (empty) {
          // Rare large embedded content: preserve it rather than discard it.
          const leftover = fragment(item, words, position, words.length, part === 0, true);
          leftover.style.breakInside = 'auto';
          currentList.appendChild(leftover);
          return;
        }
      } else if (hasExtras && !extraPlaced) {
        const extras = fragment(item, words, words.length, words.length, false, true);
        currentList.appendChild(extras);
        if (fits()) { extraPlaced = true; break; }
        extras.remove();
      }
      if (position < words.length || (hasExtras && !extraPlaced)) continueSection();
    }
  }

  function addSection(heading, list) {
    currentSection = heading.cloneNode(true);
    currentList = list.cloneNode(false);
    page.body.append(heading, currentList);
    const items = Array.from(list.children);
    for (const item of items) {
      currentList.appendChild(item);
      if (fits()) continue;
      // A single exceptionally long prompt must be split rather than pushed
      // in its entirety onto an otherwise empty second sheet.
      const questionHeight = item.getBoundingClientRect().height;
      item.remove();
      if (questionHeight > page.body.clientHeight - 80) {
        splitLargeQuestion(item);
        continue;
      }
      // Keep each section title with its first question. Move it if a
      // previous section or exam metadata used the remainder of this page.
      if (!currentList.children.length && page.body.children.length > 2) {
        currentList.remove();
        const activeHeading = page.body.lastElementChild;
        if (activeHeading && activeHeading.classList.contains('section-title')) activeHeading.remove();
        newPage();
        page.body.append(heading.cloneNode(true));
        currentList = list.cloneNode(false);
        page.body.appendChild(currentList);
      } else if (currentList.children.length) {
        continueSection();
      }
      currentList.appendChild(item);
      if (!fits()) {
        item.remove();
        splitLargeQuestion(item);
      }
    }
  }

  function fitMetadataOnOneLine(root) {
    root.querySelectorAll('.metadata-line').forEach(line => {
      line.style.fontSize = '';
      line.style.transform = '';
      let size = parseFloat(getComputedStyle(line).fontSize);
      // Long academic program/subject names may require a small reduction.
      // Never truncate the actual metadata, or shift it onto another line.
      while (line.scrollWidth > line.clientWidth + 1 && size > 8.5) {
        size -= 0.25;
        line.style.fontSize = size + 'px';
      }
      if (line.scrollWidth > line.clientWidth + 1) {
        line.style.transformOrigin = 'left center';
        line.style.transform = `scaleX(${line.clientWidth / line.scrollWidth})`;
      }
    });
  }

  function paginate() {
    document.body.classList.remove('is-paginated');
    pagesHost.replaceChildren();
    source.replaceChildren(...Array.from(original.content.cloneNode(true).childNodes));
    pages = [];
    currentSection = currentList = null;
    // Page containers must be visible while measuring scrollHeight.
    document.body.classList.add('is-paginated');
    newPage();
    const blocks = Array.from(source.children);
    for (let i = 0; i < blocks.length; i++) {
      const block = blocks[i];
      if (block.classList.contains('section-title') && blocks[i + 1]?.classList.contains('q-list')) {
        addSection(block, blocks[++i]);
      } else {
        page.body.appendChild(block);
        if (!fits() && page.body.children.length > 1) {
          block.remove();
          newPage();
          page.body.appendChild(block);
        }
      }
    }
    pages.forEach((entry, index) => {
      fitMetadataOnOneLine(entry.body);
      if (pages.length === 1) return;
      const footer = document.createElement('div');
      footer.className = 'page-navigation';
      const number = document.createElement('span');
      number.className = 'page-number';
      number.textContent = `Page ${index + 1} of ${pages.length}`;
      footer.appendChild(number);
      if (index < pages.length - 1) {
        const turnOver = document.createElement('span');
        turnOver.className = 'turn-over';
        turnOver.textContent = 'Turn Over →';
        footer.appendChild(turnOver);
      }
      entry.sheet.appendChild(footer);
    });
    document.body.classList.add('is-paginated');
  }

  let scheduled = false;
  function schedulePagination() {
    if (scheduled) return;
    scheduled = true;
    requestAnimationFrame(() => { scheduled = false; paginate(); });
  }
  // Render once immediately; then reflow when remote diagrams/fonts finish.
  // Both events use the same fixed A4 metrics, including before printing.
  schedulePagination();
  if (document.fonts?.ready) document.fonts.ready.then(schedulePagination);
  window.addEventListener('load', schedulePagination);
  window.addEventListener('beforeprint', paginate);
})();
</script>
</body>
</html>
