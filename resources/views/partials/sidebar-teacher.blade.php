<nav class="sidebar-nav">
  <div class="sidebar-section">Main</div>
  <a href="{{ route('teacher.dashboard') }}" class="{{ request()->routeIs('teacher.dashboard') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-home"></i></span>Dashboard</a>

  <div class="sidebar-section">LMS</div>
  <a href="{{ route('lms.courses.index') }}" class="{{ request()->routeIs('lms.courses*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-laptop-code"></i></span>My Subjects</a>

  <div class="sidebar-section">Academic</div>
  <a href="{{ route('teacher.timetable.index') }}" class="{{ request()->routeIs('teacher.timetable*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-calendar-alt"></i></span>My Timetable</a>
  <a href="{{ route('teacher.attendance.index') }}" class="{{ request()->routeIs('teacher.attendance*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-clipboard-check"></i></span>Attendance</a>
  <a href="{{ route('teacher.examinations.index') }}" class="{{ request()->routeIs('teacher.examinations*') && !request()->routeIs('teacher.examinations.papers*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-file-pen"></i></span>Examinations</a>
  <a href="{{ route('teacher.examinations.papers.index') }}" class="{{ request()->routeIs('teacher.examinations.papers*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-file-circle-check"></i></span>My Exam Papers</a>
  <a href="{{ route('teacher.students.fees') }}" class="{{ request()->routeIs('teacher.students.fee*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-receipt"></i></span>Student Fees</a>

  <div class="sidebar-section">Admin</div>
  <a href="{{ route('teacher.messages.index') }}" class="{{ request()->routeIs('teacher.messages*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-comments"></i></span>Messages</a>
  <a href="{{ route('teacher.activities.index') }}" class="{{ request()->routeIs('teacher.activities*') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-diagram-project"></i></span>Activities</a>

  <div class="sidebar-section">Assistant</div>
  <a href="javascript:void(0)" onclick="toggleAiAssist()"><span class="nav-icon"><i class="fas fa-robot"></i></span>AI Assist</a>
</nav>
