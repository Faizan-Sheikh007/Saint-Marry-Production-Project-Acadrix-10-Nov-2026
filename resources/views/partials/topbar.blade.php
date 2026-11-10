<div class="topbar">
  <button class="hamburger" onclick="toggleSidebar()"><i class="fas fa-bars"></i></button>

  <div class="page-title">@yield('page_title', 'Dashboard')</div>

  <div class="search-bar">
    <i class="fas fa-search" style="color:var(--text-muted);font-size:13px"></i>
    <input type="text" placeholder="Search students, teachers..." id="globalSearch">
  </div>

  <div class="topbar-actions">
    <button class="icon-btn" id="darkBtn" onclick="toggleDark()" title="Toggle Dark Mode">
      <i class="fas fa-moon"></i>
    </button>

    <button class="icon-btn notif-btn" onclick="toggleNotif()" title="Notifications">
      <i class="fas fa-bell"></i>
      <span class="notif-dot"></span>
    </button>

    <button class="icon-btn" onclick="toggleSidebarCollapse()" title="Collapse Sidebar" style="display:none" id="collapseBtn">
      <i class="fas fa-sidebar"></i>
    </button>
  </div>
</div>
