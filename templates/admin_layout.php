<?php
// ============================================================
// Template helper – Layout admin con sidebar
// Uso: $page_title, $active_nav devono essere definiti prima dell'include
// ============================================================
if (!defined('ROOT_PATH')) {
    require_once dirname(__DIR__) . '/config/config.php';
}
require_once LIB_PATH . '/Auth.php';
Auth::requireLogin();
$user = Auth::user();
?>
<!DOCTYPE html>
<html lang="it">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= htmlspecialchars($page_title ?? 'Admin') ?> – <?= APP_NAME ?></title>
<link rel="stylesheet" href="/admin/css/admin.css">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js" defer></script>
</head>
<body>
<div class="admin-layout">

  <!-- Sidebar -->
  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <span class="brand-icon">🎵</span>
      <div>
        <div class="brand-name"><?= APP_NAME ?></div>
        <div class="brand-sub">v<?= APP_VERSION ?></div>
      </div>
    </div>

    <nav class="sidebar-nav">
      <div class="nav-section-title">Principale</div>
      <a href="/admin/index.php"   class="nav-link <?= ($active_nav??'') === 'dashboard' ? 'active':'' ?>">
        <span class="nav-icon">📊</span> Dashboard
      </a>
      <a href="/admin/events.php"  class="nav-link <?= ($active_nav??'') === 'events'   ? 'active':'' ?>">
        <span class="nav-icon">🎉</span> Eventi
      </a>
      <a href="/admin/guests.php"  class="nav-link <?= ($active_nav??'') === 'guests'   ? 'active':'' ?>">
        <span class="nav-icon">👥</span> Guest List
      </a>
      <a href="/admin/checkin.php" class="nav-link <?= ($active_nav??'') === 'checkin'  ? 'active':'' ?>">
        <span class="nav-icon">✅</span> Check-In
      </a>

      <?php if ($user['role'] === ROLE_ADMIN): ?>
      <div class="nav-section-title">Gestione</div>
      <a href="/admin/prs.php"     class="nav-link <?= ($active_nav??'') === 'prs'      ? 'active':'' ?>">
        <span class="nav-icon">🌟</span> PR / Promoter
      </a>
      <a href="/admin/users.php"   class="nav-link <?= ($active_nav??'') === 'users'    ? 'active':'' ?>">
        <span class="nav-icon">🔑</span> Utenti
      </a>
      <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
      <span>👤 <?= htmlspecialchars($user['name']) ?></span>
      <a href="/admin/logout.php" style="color:var(--danger);text-decoration:none;font-size:.8rem;">Esci</a>
    </div>
  </aside>

  <!-- Main -->
  <div class="main-content">
    <header class="topbar">
      <div style="display:flex;align-items:center;gap:.8rem;">
        <button class="btn btn-outline btn-icon" id="sidebarToggle" title="Menu">☰</button>
        <span class="topbar-title"><?= htmlspecialchars($page_title ?? '') ?></span>
      </div>
      <div class="topbar-actions">
        <span class="badge badge-muted"><?= htmlspecialchars(ucfirst($user['role'])) ?></span>
      </div>
    </header>

    <main class="page-body" id="pageBody">
