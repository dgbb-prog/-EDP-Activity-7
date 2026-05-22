<?php
// ============================================================
// GymPulse — Shared Layout Header
// Usage: include this at top of every dashboard page
// $pageId    = active nav item id (e.g. 'dashboard')
// $pageTitle = displayed page title
// ============================================================
require_once __DIR__ . '/auth.php';
requireLogin();

$user     = currentUser();
$unread   = getUnreadNotificationCount($user['id']);
$pageId    = $pageId ?? 'dashboard';
$pageTitle = $pageTitle ?? 'Dashboard';
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GymPulse — <?= htmlspecialchars($pageTitle) ?></title>
<script src="https://cdnjs.cloudflare.com/ajax/libs/xlsx/0.18.5/xlsx.full.min.js"></script>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Rajdhani:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap');
  :root{--red:#e8192c;--red-dark:#b01020;--red-glow:rgba(232,25,44,0.3);--bg:#0a0a0a;--surface:#141414;--surface2:#1c1c1c;--surface3:#242424;--border:#2a2a2a;--text:#f0f0f0;--muted:#888;--active:#22c55e;--pending:#f59e0b;--expired:#ef4444;}
  *{margin:0;padding:0;box-sizing:border-box;}
  body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;}
  .sidebar{width:220px;min-height:100vh;background:var(--surface);border-right:1px solid var(--border);display:flex;flex-direction:column;position:fixed;left:0;top:0;bottom:0;z-index:100;}
  .sidebar-logo{padding:20px 18px;border-bottom:1px solid var(--border);display:flex;align-items:center;gap:10px;}
  .logo-icon{width:36px;height:36px;background:var(--red);border-radius:8px;display:flex;align-items:center;justify-content:center;font-size:18px;}
  .logo-text{font-family:'Bebas Neue',sans-serif;font-size:22px;letter-spacing:2px;}
  .logo-text span{color:var(--red);}
  .sidebar-section{padding:12px 10px 4px;font-size:10px;color:var(--muted);letter-spacing:2px;font-weight:600;}
  .nav-item{display:flex;align-items:center;gap:10px;padding:9px 14px;margin:2px 8px;border-radius:8px;cursor:pointer;font-size:13px;font-weight:500;color:var(--muted);transition:all .2s;text-decoration:none;}
  .nav-item:hover{background:var(--surface2);color:var(--text);}
  .nav-item.active{background:var(--red);color:#fff;}
  .nav-item .icon{font-size:15px;width:20px;text-align:center;}
  .sidebar-footer{margin-top:auto;padding:14px;border-top:1px solid var(--border);}
  .sidebar-footer .user-row{display:flex;align-items:center;gap:10px;margin-bottom:8px;}
  .avatar{width:32px;height:32px;border-radius:50%;background:var(--red);display:flex;align-items:center;justify-content:center;font-weight:700;font-size:12px;flex-shrink:0;}
  .avatar-info .name{font-size:12px;font-weight:600;}
  .avatar-info .role{font-size:10px;color:var(--muted);text-transform:capitalize;}
  .logout-btn{display:flex;align-items:center;gap:8px;width:100%;padding:8px 10px;border-radius:8px;background:var(--surface2);border:1px solid var(--border);color:var(--muted);font-size:12px;font-weight:600;cursor:pointer;transition:all .2s;text-decoration:none;}
  .logout-btn:hover{background:rgba(239,68,68,.15);border-color:var(--red);color:#ef4444;}
  .main{margin-left:220px;flex:1;min-height:100vh;}
  .topbar{height:56px;background:var(--surface);border-bottom:1px solid var(--border);display:flex;align-items:center;justify-content:space-between;padding:0 28px;position:sticky;top:0;z-index:50;}
  .page-title{font-family:'Rajdhani',sans-serif;font-size:22px;font-weight:700;letter-spacing:1px;}
  .topbar-right{display:flex;align-items:center;gap:14px;}
  .gym-name{font-size:13px;color:var(--muted);}
  .notif-btn{position:relative;background:var(--surface2);border:1px solid var(--border);color:var(--text);padding:6px 12px;border-radius:8px;cursor:pointer;font-size:13px;display:flex;align-items:center;gap:6px;transition:all .2s;}
  .notif-btn:hover{background:var(--surface3);}
  .notif-count{background:var(--red);color:#fff;font-size:10px;font-weight:700;padding:1px 6px;border-radius:10px;}
  .page{display:none;padding:28px;}
  .page.active{display:block;}
  .kpi-grid{display:grid;grid-template-columns:repeat(4,1fr);gap:16px;margin-bottom:24px;}
  .kpi-card{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:18px;position:relative;overflow:hidden;}
  .kpi-card::before{content:'';position:absolute;top:0;left:0;right:0;height:3px;background:var(--red);}
  .kpi-icon{font-size:22px;margin-bottom:10px;}
  .kpi-val{font-family:'Bebas Neue',sans-serif;font-size:36px;letter-spacing:1px;color:var(--text);}
  .kpi-label{font-size:11px;color:var(--muted);margin-top:2px;}
  .kpi-badge{position:absolute;top:14px;right:14px;font-size:10px;font-weight:600;color:#22c55e;background:rgba(34,197,94,.15);padding:2px 8px;border-radius:20px;}
  .kpi-badge.warn{color:var(--expired);background:rgba(239,68,68,.15);}
  .section-head{display:flex;align-items:center;justify-content:space-between;margin-bottom:14px;}
  .section-head h3{font-family:'Rajdhani',sans-serif;font-size:18px;font-weight:700;letter-spacing:.5px;}
  .table-wrap{background:var(--surface);border:1px solid var(--border);border-radius:12px;overflow:hidden;margin-bottom:20px;}
  table{width:100%;border-collapse:collapse;font-size:13px;}
  th{background:var(--surface2);padding:11px 14px;text-align:left;font-size:10px;letter-spacing:1.5px;color:var(--muted);font-weight:600;text-transform:uppercase;}
  td{padding:11px 14px;border-top:1px solid var(--border);}
  tr:hover td{background:var(--surface2);}
  .status-badge{padding:3px 10px;border-radius:20px;font-size:11px;font-weight:600;}
  .status-active{background:rgba(34,197,94,.15);color:#22c55e;}
  .status-pending{background:rgba(245,158,11,.15);color:#f59e0b;}
  .status-expired{background:rgba(239,68,68,.15);color:#ef4444;}
  .status-paid{background:rgba(34,197,94,.15);color:#22c55e;}
  .status-unpaid{background:rgba(239,68,68,.15);color:#ef4444;}
  .status-present{background:rgba(34,197,94,.15);color:#22c55e;}
  .status-absent{background:rgba(239,68,68,.15);color:#ef4444;}
  .status-on.leave{background:rgba(245,158,11,.15);color:#f59e0b;}
  .btn{display:inline-flex;align-items:center;gap:6px;padding:8px 16px;border-radius:8px;font-size:13px;font-weight:600;cursor:pointer;border:none;transition:all .2s;font-family:'Inter',sans-serif;text-decoration:none;}
  .btn-red{background:var(--red);color:#fff;}
  .btn-red:hover{background:var(--red-dark);}
  .btn-outline{background:transparent;border:1px solid var(--border);color:var(--text);}
  .btn-outline:hover{background:var(--surface2);}
  .btn-green{background:#22c55e;color:#fff;}
  .btn-green:hover{background:#16a34a;}
  .btn-sm{padding:5px 12px;font-size:12px;}
  .modal-bg{display:none;position:fixed;inset:0;background:rgba(0,0,0,.7);z-index:200;align-items:center;justify-content:center;}
  .modal-bg.open{display:flex;}
  .modal{background:var(--surface);border:1px solid var(--border);border-radius:16px;padding:28px;width:520px;max-height:90vh;overflow-y:auto;position:relative;}
  .modal h2{font-family:'Rajdhani',sans-serif;font-size:22px;font-weight:700;margin-bottom:4px;}
  .modal-sub{font-size:12px;color:var(--muted);margin-bottom:20px;}
  .modal-close{position:absolute;top:16px;right:16px;background:var(--surface2);border:none;color:var(--text);width:28px;height:28px;border-radius:50%;cursor:pointer;font-size:16px;display:flex;align-items:center;justify-content:center;}
  .form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;margin-bottom:12px;}
  .form-row.full{grid-template-columns:1fr;}
  .form-group{display:flex;flex-direction:column;gap:5px;}
  .form-group label{font-size:11px;font-weight:600;color:var(--muted);letter-spacing:1px;text-transform:uppercase;}
  .form-group input,.form-group select{background:var(--surface2);border:1px solid var(--border);color:var(--text);padding:9px 12px;border-radius:8px;font-size:13px;font-family:'Inter',sans-serif;transition:border-color .2s;}
  .form-group input:focus,.form-group select:focus{outline:none;border-color:var(--red);}
  .form-group select option{background:var(--surface2);}
  .modal-actions{display:flex;gap:10px;margin-top:20px;justify-content:flex-end;}
  .report-layout{display:grid;grid-template-columns:1fr 340px;gap:20px;}
  .report-type-grid{display:grid;grid-template-columns:1fr 1fr;gap:10px;margin-bottom:16px;}
  .report-type-btn{background:var(--surface2);border:2px solid var(--border);border-radius:10px;padding:14px;cursor:pointer;transition:all .2s;text-align:left;}
  .report-type-btn.selected{border-color:var(--red);background:rgba(232,25,44,.08);}
  .report-type-btn .rt-icon{font-size:22px;margin-bottom:6px;}
  .report-type-btn .rt-title{font-weight:700;font-size:14px;}
  .report-type-btn .rt-sub{font-size:11px;color:var(--muted);margin-top:2px;}
  .filters-box{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:18px;}
  .filters-box h4{font-family:'Rajdhani',sans-serif;font-weight:700;font-size:16px;margin-bottom:4px;}
  .filters-box .sub{font-size:11px;color:var(--muted);margin-bottom:14px;}
  .filter-label{font-size:10px;font-weight:600;color:var(--muted);letter-spacing:1px;text-transform:uppercase;margin-bottom:4px;margin-top:10px;}
  .filter-input{width:100%;background:var(--surface2);border:1px solid var(--border);color:var(--text);padding:8px 10px;border-radius:8px;font-size:12px;font-family:'Inter',sans-serif;}
  .filter-input:focus{outline:none;border-color:var(--red);}
  .export-btns{display:flex;gap:8px;margin-top:10px;}
  .export-btn{flex:1;padding:8px;border-radius:8px;border:1px solid var(--border);background:var(--surface2);color:var(--text);font-size:12px;font-weight:600;cursor:pointer;display:flex;align-items:center;justify-content:center;gap:5px;transition:all .2s;}
  .export-btn.primary{background:var(--red);border-color:var(--red);color:#fff;}
  .export-btn:hover{opacity:.85;}
  .card{background:var(--surface);border:1px solid var(--border);border-radius:12px;padding:20px;}
  .card h4{font-family:'Rajdhani',sans-serif;font-weight:700;font-size:17px;margin-bottom:14px;}
  .toast{position:fixed;bottom:28px;right:28px;background:var(--surface);border:1px solid var(--border);border-left:4px solid var(--red);border-radius:10px;padding:12px 18px;font-size:13px;z-index:999;transform:translateY(80px);opacity:0;transition:all .3s;min-width:240px;}
  .toast.show{transform:translateY(0);opacity:1;}
  .toast .toast-title{font-weight:700;margin-bottom:2px;}
  .toast .toast-msg{color:var(--muted);font-size:12px;}
  /* Notifications panel */
  .notif-panel{display:none;position:absolute;top:56px;right:20px;width:340px;background:var(--surface);border:1px solid var(--border);border-radius:12px;z-index:300;box-shadow:0 8px 32px rgba(0,0,0,.6);overflow:hidden;}
  .notif-panel.open{display:block;}
  .notif-header{padding:14px 16px;border-bottom:1px solid var(--border);display:flex;justify-content:space-between;align-items:center;}
  .notif-header span{font-family:'Rajdhani',sans-serif;font-weight:700;font-size:16px;}
  .notif-mark{font-size:11px;color:var(--red);cursor:pointer;background:none;border:none;}
  .notif-item{padding:12px 16px;border-bottom:1px solid var(--border);cursor:default;}
  .notif-item.unread{background:rgba(232,25,44,.05);}
  .notif-item:last-child{border-bottom:none;}
  .notif-item .n-title{font-size:13px;font-weight:600;margin-bottom:2px;}
  .notif-item .n-msg{font-size:12px;color:var(--muted);}
  .notif-item .n-time{font-size:10px;color:var(--muted);margin-top:4px;}
  .notif-empty{padding:24px;text-align:center;color:var(--muted);font-size:13px;}
  ::-webkit-scrollbar{width:6px;}
  ::-webkit-scrollbar-track{background:var(--surface);}
  ::-webkit-scrollbar-thumb{background:var(--border);border-radius:3px;}
</style>
</head>
<body>

<!-- SIDEBAR -->
<div class="sidebar">
  <div class="sidebar-logo">
    <div class="logo-icon">🏋️</div>
    <div class="logo-text">GYM<span>PULSE</span></div>
  </div>
  <div class="sidebar-section">MAIN</div>
  <a class="nav-item <?= $pageId==='dashboard'?'active':'' ?>" href="dashboard.php"><span class="icon">🏠</span> Dashboard</a>
  <a class="nav-item <?= $pageId==='members'?'active':'' ?>" href="members.php"><span class="icon">👥</span> Members</a>
  <a class="nav-item <?= $pageId==='trainers'?'active':'' ?>" href="trainers.php"><span class="icon">🥋</span> Trainers</a>
  <a class="nav-item <?= $pageId==='memberships'?'active':'' ?>" href="memberships.php"><span class="icon">🪪</span> Memberships</a>
  <div class="sidebar-section">TRANSACTIONS</div>
  <a class="nav-item <?= $pageId==='membership-tx'?'active':'' ?>" href="enrollment.php"><span class="icon">📋</span> Membership Enrollment</a>
  <a class="nav-item <?= $pageId==='payment-tx'?'active':'' ?>" href="payments.php"><span class="icon">💳</span> Payment Recording</a>
  <a class="nav-item <?= $pageId==='attendance-tx'?'active':'' ?>" href="attendance.php"><span class="icon">✅</span> Attendance Log</a>
  <div class="sidebar-section">SYSTEM</div>
  <a class="nav-item <?= $pageId==='reports'?'active':'' ?>" href="reports.php"><span class="icon">📊</span> Reports</a>
  <div class="sidebar-footer">
    <div class="user-row">
      <div class="avatar"><?= htmlspecialchars($user['initials']) ?></div>
      <div class="avatar-info">
        <div class="name"><?= htmlspecialchars($user['full_name']) ?></div>
        <div class="role"><?= htmlspecialchars($user['role']) ?></div>
      </div>
    </div>
    <a class="logout-btn" href="logout.php" onclick="return confirm('Sign out of GymPulse?')">
      🚪 Sign Out
    </a>
  </div>
</div>

<!-- MAIN WRAPPER -->
<div class="main">
  <div class="topbar">
    <div class="page-title"><?= htmlspecialchars($pageTitle) ?></div>
    <div class="topbar-right">
      <span class="gym-name">🥋 GymPulse Fitness Center</span>
      <button class="notif-btn" onclick="toggleNotifications()" id="notif-toggle">
        🔔 Notifications
        <?php if ($unread > 0): ?>
          <span class="notif-count" id="notif-badge"><?= $unread ?></span>
        <?php endif; ?>
      </button>
    </div>
  </div>

  <!-- NOTIFICATIONS PANEL -->
  <div class="notif-panel" id="notif-panel">
    <div class="notif-header">
      <span>🔔 Notifications</span>
      <button class="notif-mark" onclick="markAllRead()">Mark all read</button>
    </div>
    <div id="notif-list">
      <?php
      $notifs = getNotifications($user['id']);
      if ($notifs):
        foreach ($notifs as $n):
          $cls = $n['is_read'] ? '' : 'unread';
          $time = date('M j, g:ia', strtotime($n['created_at']));
      ?>
        <div class="notif-item <?= $cls ?>">
          <div class="n-title"><?= htmlspecialchars($n['title']) ?></div>
          <div class="n-msg"><?= htmlspecialchars($n['message']) ?></div>
          <div class="n-time"><?= $time ?></div>
        </div>
      <?php endforeach; else: ?>
        <div class="notif-empty">No notifications yet.</div>
      <?php endif; ?>
    </div>
  </div>
