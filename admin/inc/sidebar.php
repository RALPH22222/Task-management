<?php
if (!isset($active)) $active = '';
?>
<nav class="sidebar" aria-label="Main sidebar">
  <div class="sidebar-header">
    <button class="sidebar-toggle" aria-label="Toggle sidebar" aria-expanded="true">☰</button>
    <a href="dashboard.php" class="brand d-flex align-items-center">
      <img src="../logo.png" alt="Logo" class="brand-logo">
      <div class="brand-text">
        <div class="title">Task Management</div>
        <small class="muted">Admin</small>
      </div>
    </a>
  </div>

  <ul class="sidebar-nav" role="menu">
    <li role="none"><a role="menuitem" class="nav-link <?php if($active==='dashboard') echo 'active'; ?>" href="dashboard.php">
      <span class="icon" aria-hidden="true">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <path d="M3 11.5L12 4l9 7.5V20a1 1 0 0 1-1 1h-5v-6H9v6H4a1 1 0 0 1-1-1v-8.5z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </span>
      <span class="label">Dashboard</span>
    </a></li>

    <li role="none"><a role="menuitem" class="nav-link <?php if($active==='projects') echo 'active'; ?>" href="manage_projects.php">
      <span class="icon" aria-hidden="true">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <path d="M3 7a2 2 0 0 1 2-2h3l2 2h7a2 2 0 0 1 2 2v7a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V7z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </span>
      <span class="label">Projects</span>
    </a></li>

    <li role="none"><a role="menuitem" class="nav-link <?php if($active==='users') echo 'active'; ?>" href="manage_users.php">
      <span class="icon" aria-hidden="true">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <path d="M16 11a4 4 0 1 0-8 0" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M2 20a6 6 0 0 1 12 0" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </span>
      <span class="label">Users</span>
    </a></li>

    <li role="none"><a role="menuitem" class="nav-link <?php if($active==='logs') echo 'active'; ?>" href="activity_logs.php">
      <span class="icon" aria-hidden="true">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <path d="M9 2h6v4H9z" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M21 14V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v12a2 2 0 0 0 2 2h10" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </span>
      <span class="label">Activity Logs</span>
    </a></li>

    <li role="none" class="logout"><a role="menuitem" class="nav-link danger" href="../auth/logout.php">
      <span class="icon" aria-hidden="true">
        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" xmlns="http://www.w3.org/2000/svg" aria-hidden="true">
          <path d="M16 17l5-5-5-5" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M21 12H9" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
          <path d="M9 19H6a2 2 0 0 1-2-2V7a2 2 0 0 1 2-2h3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" stroke-linejoin="round"/>
        </svg>
      </span>
      <span class="label">Logout</span>
    </a></li>
  </ul>
</nav>
<script src="inc/sidebar.js" defer></script>
