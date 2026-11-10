<nav class="sidebar-nav">
  <div class="sidebar-section">Main</div>
  <a href="{{ route('student.dashboard') }}" class="{{ request()->routeIs('student.dashboard') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-home"></i></span>Dashboard</a>
  <a href="{{ route('student.timetable.index') }}" class="{{ request()->routeIs('student.timetable*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-calendar-alt"></i></span>My Timetable</a>
  <a href="{{ route('student.exams.index') }}" class="{{ request()->routeIs('student.exams*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-file-pen"></i></span>My Exams</a>
  <a href="{{ route('student.results.index') }}" class="{{ request()->routeIs('student.results*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-award"></i></span>My Results</a>
  <a href="{{ route('student.fees') }}" class="{{ request()->routeIs('student.fees') || request()->routeIs('student.voucher') || request()->routeIs('student.fee-plan') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-file-invoice-dollar"></i></span>My Fees &amp; Payments</a>
  <a href="{{ route('student.internships.index') }}" class="{{ request()->routeIs('student.internships*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-briefcase"></i></span>Internships</a>
  <div class="sidebar-section">Learning</div>
  <a href="{{ route('lms.courses.index') }}" class="{{ request()->routeIs('lms.courses*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-laptop-code"></i></span>My Subjects</a>
  <a href="{{ route('student.learning-tasks.index') }}" class="{{ request()->routeIs('student.learning-tasks*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-tasks"></i></span>Quizzes &amp; Assignments</a>
  <a href="{{ route('student.grades') }}" class="{{ request()->routeIs('student.grades') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-star"></i></span>My Grades</a>
  <div class="sidebar-section">Assistant</div>
  <a href="javascript:void(0)" onclick="toggleAiAssist()"><span class="nav-icon"><i class="fas fa-robot"></i></span>AI Assist</a>
</nav>
