<nav class="sidebar-nav">
  <div class="nav-section-label">Main</div>

  <a href="{{ route('accounts.dashboard') }}" class="nav-item {{ request()->routeIs('accounts.dashboard') ? 'active' : '' }}">
    <div class="nav-icon"><i class="fas fa-grid-2"></i></div>
    <span>Dashboard</span>
  </a>

  <div class="nav-section-label">Fees &amp; Vouchers</div>

  <a href="{{ route('fee-heads.index') }}" class="nav-item {{ request()->routeIs('fee-heads.*') ? 'active' : '' }}">
    <div class="nav-icon"><i class="fas fa-tags"></i></div>
    <span>Fee Heads</span>
  </a>

  <a href="{{ route('fee-templates.index') }}" class="nav-item {{ request()->routeIs('fee-templates.*') ? 'active' : '' }}">
    <div class="nav-icon"><i class="fas fa-layer-group"></i></div>
    <span>Fee Templates</span>
  </a>

  <a href="{{ route('installment-date-defaults.index') }}" class="nav-item {{ request()->routeIs('installment-date-defaults.*') ? 'active' : '' }}">
    <div class="nav-icon"><i class="fas fa-calendar-check"></i></div>
    <span>Default Installment Dates</span>
  </a>

  <a href="{{ route('vouchers.index') }}" class="nav-item {{ request()->routeIs('vouchers.*') ? 'active' : '' }}">
    <div class="nav-icon"><i class="fas fa-file-invoice"></i></div>
    <span>Vouchers &amp; Installments</span>
    @php $overdue = \App\Models\Voucher::where('status','overdue')->count(); @endphp
    @if($overdue > 0)
      <span class="nav-badge orange">{{ $overdue }}</span>
    @endif
  </a>

  <a href="{{ route('payments.receive-form') }}" class="nav-item {{ request()->routeIs('payments.receive-form') ? 'active' : '' }}">
    <div class="nav-icon"><i class="fas fa-hand-holding-dollar"></i></div>
    <span>Receive Payment</span>
  </a>

  <a href="{{ route('live-payments.index') }}" class="nav-item {{ request()->routeIs('live-payments.*') ? 'active' : '' }}">
    <div class="nav-icon"><i class="fas fa-tower-broadcast"></i></div>
    <span>Live Payments</span>
    @php $pendingCount = \App\Models\Payment::where('status','pending')->count(); @endphp
    @if($pendingCount > 0)
      <span class="nav-badge orange">{{ $pendingCount }}</span>
    @endif
  </a>

  <a href="{{ route('ledger.index') }}" class="nav-item {{ request()->routeIs('ledger.*') ? 'active' : '' }}">
    <div class="nav-icon"><i class="fas fa-book"></i></div>
    <span>Ledger &amp; Accounting</span>
  </a>

  <a href="{{ route('daily-ledger.index') }}" class="nav-item {{ request()->routeIs('daily-ledger.*') ? 'active' : '' }}">
    <div class="nav-icon"><i class="fas fa-money-bill-wave"></i></div>
    <span>Daily Ledger</span>
  </a>

  <div class="nav-section-label">Student &amp; Teacher Records</div>

  <a href="{{ route('accounts.students.index') }}" class="nav-item {{ request()->routeIs('accounts.students.*') ? 'active' : '' }}">
    <div class="nav-icon"><i class="fas fa-user-graduate"></i></div>
    <span>Students <small style="opacity:.65">(read-only)</small></span>
  </a>

  <a href="{{ route('accounts.teachers.index') }}" class="nav-item {{ request()->routeIs('accounts.teachers.*') ? 'active' : '' }}">
    <div class="nav-icon"><i class="fas fa-chalkboard-teacher"></i></div>
    <span>Teachers <small style="opacity:.65">(read-only)</small></span>
  </a>

  <div class="nav-section-label">Reports &amp; Compliance</div>

  <a href="{{ route('reports.index') }}" class="nav-item {{ request()->routeIs('reports.*', 'accounts.reporting.*', 'accounts.reports.*') ? 'active' : '' }}">
    <div class="nav-icon"><i class="fas fa-chart-bar"></i></div>
    <span>Reports</span>
  </a>

  <a href="{{ route('audit-log.index') }}" class="nav-item {{ request()->routeIs('audit-log.*') ? 'active' : '' }}">
    <div class="nav-icon"><i class="fas fa-clipboard-list"></i></div>
    <span>Audit Log</span>
  </a>

  <div class="nav-section-label">Other</div>

  <a href="{{ route('settings.index') }}" class="nav-item {{ request()->routeIs('settings.*') ? 'active' : '' }}">
    <div class="nav-icon"><i class="fas fa-gear"></i></div>
    <span>Settings</span>
  </a>

  <div class="nav-section-label">Assistant</div>

  <a href="javascript:void(0)" onclick="toggleAiAssist()" class="nav-item">
    <div class="nav-icon"><i class="fas fa-robot"></i></div>
    <span>AI Assist</span>
  </a>
</nav>
