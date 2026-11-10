<nav class="sidebar-nav">
  <div class="sidebar-section">Main</div>
  <a href="{{ route('admin.dashboard') }}" class="{{ request()->routeIs('admin.dashboard') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-home"></i></span>Dashboard</a>
  <div class="sidebar-section">Academic</div>
  <a href="{{ route('students.index') }}" class="{{ request()->routeIs('students*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-user-graduate"></i></span>Students</a>
  <a href="{{ route('admin.teachers.index') }}" class="{{ request()->routeIs('admin.teachers*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-chalkboard-teacher"></i></span>Teachers</a>
  <a href="{{ route('programs.index') }}" class="{{ request()->routeIs('programs*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-sitemap"></i></span>Programs</a>
  <a href="{{ route('institutions.index') }}" class="{{ request()->routeIs('institutions*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-university"></i></span>Institutions</a>
  <a href="{{ route('academic-levels.index') }}" class="{{ request()->routeIs('academic-levels*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-layer-group"></i></span>Academic Levels</a>
  <a href="{{ route('academic-years.index') }}" class="{{ request()->routeIs('academic-years*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-calendar-days"></i></span>Academic Sessions</a>
  <a href="{{ route('classes.index') }}" class="{{ request()->routeIs('classes*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-school"></i></span>Parts/Semesters</a>
  <a href="{{ route('subjects.index') }}" class="{{ request()->routeIs('subjects*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-book"></i></span>Subjects</a>
  <a href="{{ route('admin.timetable.index') }}" class="{{ request()->routeIs('admin.timetable*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-calendar-alt"></i></span>Timetable</a>
  <a href="{{ route('admin.attendance.index') }}" class="{{ request()->routeIs('admin.attendance*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-clipboard-check"></i></span>Attendance</a>
  <a href="{{ route('admin.examinations.index') }}" class="{{ request()->routeIs('admin.examinations*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-file-pen"></i></span>Examinations</a>
  <a href="{{ route('admin.results.index') }}" class="{{ request()->routeIs('admin.results*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-award"></i></span>Results &amp; Marksheets</a>
  <a href="{{ route('admin.grading-scale.index') }}" class="{{ request()->routeIs('admin.grading-scale*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-percent"></i></span>Grading Scale</a>
  <div class="sidebar-section">LMS</div>
  <a href="{{ route('admin.lms.teachers') }}" class="{{ request()->routeIs('admin.lms.*') || request()->routeIs('lms.*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-laptop-code"></i></span>LMS Monitor</a>
  <a href="{{ route('admin.messages.index') }}" class="{{ request()->routeIs('admin.messages*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-comments"></i></span>Teacher Chat</a>
  <a href="{{ route('admin.announcements.index') }}" class="{{ request()->routeIs('admin.announcements*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-bullhorn"></i></span>Announcements</a>
  <a href="{{ route('admin.activities.index') }}" class="{{ request()->routeIs('admin.activities*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-diagram-project"></i></span>Activities</a>
  <div class="sidebar-section">Finance</div>
  <a href="{{ route('vouchers.index') }}" class="{{ request()->routeIs('vouchers.*') || request()->routeIs('fee-assignments.*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-file-invoice"></i></span>Vouchers &amp; Installments</a>
  <a href="{{ route('installment-date-defaults.index') }}" class="{{ request()->routeIs('installment-date-defaults.*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-calendar-check"></i></span>Default Installment Dates</a>
  <a href="{{ route('admin.internships.index') }}" class="{{ request()->routeIs('admin.internships*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-briefcase"></i></span>Internships</a>
  <a href="{{ route('admin.daily-ledger.index') }}" class="{{ request()->routeIs('admin.daily-ledger*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-money-bill-wave"></i></span>Daily Ledger <small style="opacity:.65">(view only)</small></a>
  <div class="sidebar-note">
    <i class="fas fa-circle-info"></i>
    <span>Fee Heads, Fee Templates, Payments, Ledger, Reports &amp; Audit Log are managed by the Accounts department (separate login). Admin can view the Daily Ledger and its remarks but cannot add entries.</span>
  </div>
  <a href="{{ route('admin.staff.index') }}" class="{{ request()->routeIs('admin.staff*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-people-carry-box"></i></span>Other Staff</a>
  <div class="sidebar-section">Other</div>
  <a href="{{ route('admin.parents.index') }}" class="{{ request()->routeIs('admin.parents*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-users"></i></span>Parents</a>
  <a href="{{ route('admin.library.index') }}" class="{{ request()->routeIs('admin.library*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-book-open"></i></span>Library</a>
  <a href="{{ route('admin.inventory.index') }}" class="{{ request()->routeIs('admin.inventory*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-boxes-stacked"></i></span>Inventory</a>
  <a href="{{ route('admin.medical-lab-inventory.index') }}" class="{{ request()->routeIs('admin.medical-lab-inventory*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-briefcase-medical"></i></span>Medical Lab Inventory</a>
  <a href="{{ route('admin.computer-lab-inventory.index') }}" class="{{ request()->routeIs('admin.computer-lab-inventory*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-computer"></i></span>Computer Lab Inventory</a>
  <a href="{{ route('admin.examinations.papers.index') }}" class="{{ request()->routeIs('admin.examinations.papers*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-file-circle-check"></i></span>Examination Papers</a>
  <a href="{{ route('admin.reports.index') }}" class="{{ request()->routeIs('admin.reports*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-chart-bar"></i></span>Reports</a>
  <a href="{{ route('settings.index') }}" class="{{ request()->routeIs('settings*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-cog"></i></span>Settings</a>
  <div class="sidebar-section">Assistant</div>
  <a href="javascript:void(0)" onclick="toggleAiAssist()"><span class="nav-icon"><i class="fas fa-robot"></i></span>AI Assist</a>
</nav>
