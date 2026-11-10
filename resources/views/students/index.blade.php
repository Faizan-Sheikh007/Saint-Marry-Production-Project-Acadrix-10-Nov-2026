@extends('layouts.app')

@section('title', 'Students')
@section('page_title', 'Student Management')

@section('content')

<!-- Header Actions -->
<div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:20px;flex-wrap:wrap;gap:12px">
  <div>
    <div style="font-size:22px;font-weight:800">Students</div>
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap">
    <a href="{{ route('students.exit-report') }}" class="btn btn-outline"><i class="fas fa-chart-bar"></i> Drop / Left Report</a>
    <a href="{{ route('students.create') }}" class="btn btn-primary">
      <i class="fas fa-plus"></i> Add Student
    </a>
    @php $missingLmsCount = \App\Models\Student::whereNull('user_id')->count(); @endphp
    @if($missingLmsCount > 0)
      <form action="{{ route('students.generate-lms-ids') }}" method="POST"
        data-confirm="This creates LMS logins for {{ $missingLmsCount }} student(s) who don't have one yet."
        data-confirm-title="Generate {{ $missingLmsCount }} LMS login(s)?"
        data-confirm-variant="warning"
        data-confirm-icon="fa-graduation-cap"
        data-confirm-ok-text="Yes, generate all">
        @csrf
        <button type="submit" class="btn" style="background:#d97706;color:#fff;border:none">
          <i class="fas fa-graduation-cap"></i> Generate Missing LMS IDs ({{ $missingLmsCount }})
        </button>
      </form>
    @endif
  </div>
</div>

@if(session('bulkCredentials'))
  <div class="alert alert-success" style="margin-bottom:20px">
    <div style="font-weight:700;margin-bottom:8px"><i class="fas fa-check-circle"></i> LMS logins issued — save these now, passwords won't be shown again:</div>
    <div style="display:flex;flex-direction:column;gap:4px;font-size:13px">
      @foreach(session('bulkCredentials') as $cred)
        <div><strong>{{ $cred['name'] }}</strong> — {{ $cred['email'] }} / {{ $cred['password'] }}</div>
      @endforeach
    </div>
  </div>
@endif

{{-- Session tabs, filter card, and results table — all AJAX. Session-tab
     clicks, filter submits, and pagination links swap this panel in place
     via fetch() instead of a full page reload; see the script below. --}}
<div id="students-panel">
  @include('students._panel')
</div>

<style>
  /* Compact table type — the previous size wrapped Program/Class, Father
     Name, etc. onto multiple lines and made the row cramped. */
  .students-table th { font-size: 11.5px; padding: 10px 12px; white-space: nowrap; }
  .students-table td { font-size: 12.5px; padding: 10px 12px; vertical-align: middle; }
  .students-table td, .students-table td div { line-height: 1.35; }

  /* Session tabs above the filter card — shared look with Accounts'/
     Vouchers' "Browse by Session" tabs. */
  .session-tabs { display:flex; gap:8px; flex-wrap:wrap; margin-bottom:16px; }
  .session-tab { display:flex; align-items:center; gap:7px; padding:7px 14px; font-size:12.5px; font-weight:700; color:var(--text-muted); background:var(--card); border:1px solid var(--border); border-radius:999px; transition:background .12s ease, color .12s ease, border-color .12s ease; }
  .session-tab:hover:not(.active) { color:var(--text); border-color:var(--primary); }
  .session-tab.active { color:#fff; background:var(--primary); border-color:var(--primary); }
  .session-tab-count { font-size:10.5px; font-weight:800; background:rgba(0,0,0,0.12); border-radius:999px; padding:1px 7px; }
  .session-tab.active .session-tab-count { background:rgba(255,255,255,0.25); }
  #students-panel.is-loading { opacity:0.5; pointer-events:none; transition:opacity .1s ease; }

  /* Printing this page previously rendered the on-screen, horizontally-
     scrolled table as-is, so anything past the visible width (Roll No,
     Guardian, Contact, Status...) was cut off on the printout. Force the
     table to lay out at full natural width for print instead of clipping
     it inside the scrollable wrapper. */
  @media print {
    .table-wrap { overflow: visible !important; }
    table { table-layout: auto; font-size: 11px; }
    th, td { padding: 6px 8px !important; }
    .no-print { display: none !important; }
  }
</style>

@push('scripts')
<script>
(() => {
  const structure = @json($filterStructure);
  const panel = document.getElementById('students-panel');

  // ── Session -> Program -> Semester/Part cascading selects. Re-run after
  // every AJAX panel swap since the <select> elements are replaced along
  // with the rest of the panel's markup. ──
  function initFilterCascade() {
    const sessionSelect = document.getElementById('student-filter-session');
    const programSelect = document.getElementById('student-filter-program');
    const classSelect = document.getElementById('student-filter-class');
    if (!sessionSelect || !programSelect || !classSelect) return;

    const params = new URLSearchParams(window.location.search);
    const selectedProgram = params.get('program') || '';
    const selectedClass = params.get('class') || '';

    function currentSession() {
      return structure[String(sessionSelect.value)] || null;
    }

    function currentProgram() {
      const session = currentSession();
      if (!session) return null;
      return (session.programs || []).find(program => String(program.id) === String(programSelect.value)) || null;
    }

    function populateClasses(selected = '') {
      const program = currentProgram();
      classSelect.innerHTML = '';

      if (!program) {
        classSelect.innerHTML = `<option value="">${sessionSelect.value ? 'Select Program first' : 'Select Session first'}</option>`;
        classSelect.disabled = true;
        return;
      }

      classSelect.disabled = false;
      const all = document.createElement('option');
      all.value = '';
      all.textContent = 'All linked Semester / Parts';
      classSelect.appendChild(all);

      (program.terms || []).forEach(term => {
        const option = document.createElement('option');
        option.value = term.id;
        option.textContent = term.name;
        option.selected = String(term.id) === String(selected);
        classSelect.appendChild(option);
      });

      if (!(program.terms || []).length) {
        classSelect.innerHTML = '<option value="">No linked Semester / Part</option>';
        classSelect.disabled = true;
      }
    }

    function populatePrograms(selected = '', selectedTerm = '') {
      const session = currentSession();
      programSelect.innerHTML = '';

      if (!session) {
        programSelect.innerHTML = '<option value="">Select Session first</option>';
        programSelect.disabled = true;
        populateClasses('');
        return;
      }

      programSelect.disabled = false;
      const all = document.createElement('option');
      all.value = '';
      all.textContent = 'All Programs in this Session';
      programSelect.appendChild(all);

      (session.programs || []).forEach(program => {
        const option = document.createElement('option');
        option.value = program.id;
        option.textContent = program.name;
        option.selected = String(program.id) === String(selected);
        programSelect.appendChild(option);
      });

      if (!(session.programs || []).length) {
        programSelect.innerHTML = '<option value="">No Programs linked to this Session</option>';
        programSelect.disabled = true;
      }

      populateClasses(selectedTerm);
    }

    sessionSelect.addEventListener('change', () => populatePrograms('', ''));
    programSelect.addEventListener('change', () => populateClasses(''));
    populatePrograms(selectedProgram, selectedClass);
  }

  // ── AJAX panel swap: session-tab clicks, the filter form, the Reset
  // link, and pagination links all fetch just the panel fragment instead
  // of doing a full page reload. Delegated on `document` (not the panel
  // itself) since the panel's own innerHTML gets replaced on every
  // navigation. ──
  async function loadPanel(url, push = true) {
    if (!panel) return;
    panel.classList.add('is-loading');
    try {
      const res = await fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' } });
      if (!res.ok) throw new Error('Request failed');
      const html = await res.text();
      panel.innerHTML = html;
      if (push) history.pushState({ studentsPanel: true }, '', url);
      initFilterCascade();
      panel.scrollIntoView({ block: 'nearest' });
    } catch (err) {
      window.location.href = url; // AJAX failed — fall back to a normal navigation
    } finally {
      panel.classList.remove('is-loading');
    }
  }

  document.addEventListener('click', function (e) {
    if (!panel) return;
    const link = e.target.closest('a');
    if (!link || !panel.contains(link)) return;
    // Intercept session tabs / Reset (data-ajax-nav) and pagination links
    // (identified by the shared pagination view's aria-label) — but leave
    // row action links (View, Edit, ID Card, delete forms, etc.) alone.
    const isPaginationLink = link.closest('nav[aria-label="Pagination Navigation"]');
    const isAjaxNav = link.hasAttribute('data-ajax-nav');
    if (!isPaginationLink && !isAjaxNav) return;
    e.preventDefault();
    loadPanel(link.getAttribute('href'));
  });

  document.addEventListener('submit', function (e) {
    const form = e.target.closest('[data-ajax-form]');
    if (!form || !panel || !panel.contains(form)) return;
    e.preventDefault();
    const params = new URLSearchParams(new FormData(form));
    loadPanel(form.getAttribute('action') + '?' + params.toString());
  });

  window.addEventListener('popstate', function () {
    loadPanel(window.location.href, false);
  });

  // ── Bulk select / delete. Everything is delegated on `document` and
  // re-synced after each panel swap, since the checkboxes live inside the
  // AJAX-replaced panel. ──
  const csrf = document.querySelector('meta[name="csrf-token"]')?.content || '';
  const rowBoxes  = () => Array.from(document.querySelectorAll('#students-panel .student-select:not(:disabled)'));
  const selected  = () => rowBoxes().filter(b => b.checked);

  function syncBulkBar() {
    const bar = document.getElementById('student-bulk-bar');
    const all = document.getElementById('student-select-all');
    if (!bar) return;
    const boxes = rowBoxes(), picked = selected();
    document.getElementById('student-bulk-count').textContent = picked.length;
    bar.style.display = picked.length ? 'flex' : 'none';
    if (all) {
      all.checked = boxes.length > 0 && picked.length === boxes.length;
      all.indeterminate = picked.length > 0 && picked.length < boxes.length;
    }
    boxes.forEach(b => b.closest('tr')?.style.setProperty('background', b.checked ? 'rgba(137,12,37,.06)' : ''));
  }

  document.addEventListener('change', function (e) {
    if (e.target.id === 'student-select-all') {
      rowBoxes().forEach(b => b.checked = e.target.checked);
      syncBulkBar();
    } else if (e.target.classList?.contains('student-select')) {
      syncBulkBar();
    }
  });

  document.addEventListener('click', async function (e) {
    if (e.target.closest('#student-bulk-clear')) {
      rowBoxes().forEach(b => b.checked = false);
      syncBulkBar();
      return;
    }
    const delBtn = e.target.closest('#student-bulk-delete');
    if (!delBtn) return;

    const ids = selected().map(b => b.value);
    if (!ids.length) return;

    const ok = await acadrixConfirm(
      `This permanently removes ${ids.length} selected student${ids.length === 1 ? '' : 's'} and their records. This can't be undone.`,
      { title: `Delete ${ids.length} student${ids.length === 1 ? '' : 's'}?`, variant: 'danger', icon: 'fa-trash', okText: 'Yes, delete' }
    );
    if (!ok) return;

    delBtn.disabled = true;
    try {
      const res = await fetch(@json(route('students.bulk-destroy')), {
        method: 'DELETE',
        headers: { 'Content-Type': 'application/json', 'Accept': 'application/json', 'X-CSRF-TOKEN': csrf, 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ ids }),
      });
      if (!res.ok) throw new Error('Delete failed');
      // Reload so flash message, counts and pagination are all fresh.
      window.location.reload();
    } catch (err) {
      delBtn.disabled = false;
      alert('Could not delete the selected students. Please try again.');
    }
  });

  // Re-sync after every AJAX swap (selection resets with the new rows).
  new MutationObserver(syncBulkBar).observe(panel, { childList: true });

  initFilterCascade();
  syncBulkBar();
})();

document.addEventListener('click', function (e) {
  const toggle = e.target.closest('.action-menu-toggle');

  document.querySelectorAll('.action-menu-dropdown').forEach(function (menu) {
    if (toggle && menu === toggle.nextElementSibling) {
      if (menu.style.display === 'block') {
        menu.style.display = 'none';
        return;
      }
      // Positioned as `fixed` (not `absolute`) so it renders above the
      // table wrapper instead of being clipped by it — a wrapper with
      // overflow-x:auto implicitly clips overflow-y too, which is why
      // this menu used to get cut off on the last row(s) of the table.
      const rect = toggle.getBoundingClientRect();
      menu.style.display = 'block';
      const menuWidth = menu.offsetWidth || 190;
      let left = rect.right - menuWidth;
      if (left < 8) left = 8;
      let top = rect.bottom + 4;
      // Flip above the button if there isn't room below the viewport.
      if (top + menu.offsetHeight > window.innerHeight - 8) {
        top = rect.top - menu.offsetHeight - 4;
      }
      menu.style.top  = top + 'px';
      menu.style.left = left + 'px';
    } else {
      menu.style.display = 'none';
    }
  });
});

// Keep the open menu glued to its button on scroll (table-wrap scrolls
// horizontally, and the page itself can scroll vertically).
window.addEventListener('scroll', function () {
  document.querySelectorAll('.action-menu-dropdown').forEach(function (menu) {
    menu.style.display = 'none';
  });
}, true);
</script>
@endpush

@endsection
