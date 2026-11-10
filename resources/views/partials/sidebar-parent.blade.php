<nav class="sidebar-nav">
  <div class="sidebar-section">Main</div>
  <a href="{{ route('parent.dashboard') }}" class="{{ request()->routeIs('parent.dashboard') ? 'active' : '' }}"><span class="nav-icon"><i class="fas fa-home"></i></span>Dashboard</a>
  <div class="sidebar-section">Assistant</div>
  <a href="javascript:void(0)" onclick="toggleAiAssist()"><span class="nav-icon"><i class="fas fa-robot"></i></span>AI Assist</a>
</nav>
