<?php
$pageId    = 'dashboard';
$pageTitle = 'Dashboard';
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();

// KPIs
$activeMembers = $pdo->query("SELECT COUNT(*) FROM members WHERE status='Active'")->fetchColumn();
$totalMembers  = $pdo->query("SELECT COUNT(*) FROM members")->fetchColumn();
$presentToday  = $pdo->query("SELECT COUNT(*) FROM attendance WHERE attendance_date=CURDATE() AND status='Present'")->fetchColumn();
$totalToday    = $pdo->query("SELECT COUNT(*) FROM attendance WHERE attendance_date=CURDATE()")->fetchColumn();
$attRate       = $totalToday > 0 ? round(($presentToday / $totalToday) * 100, 1) : 0;
$monthRevenue  = $pdo->query("SELECT IFNULL(SUM(amount),0) FROM payments WHERE MONTH(payment_date)=MONTH(CURDATE()) AND YEAR(payment_date)=YEAR(CURDATE()) AND status='Paid'")->fetchColumn();
$expiringSoon  = $pdo->query("SELECT COUNT(*) FROM enrollments WHERE expiry_date BETWEEN CURDATE() AND DATE_ADD(CURDATE(), INTERVAL 30 DAY) AND status='Active'")->fetchColumn();

// Recent members
$recentMembers = $pdo->query("
    SELECT m.member_code, CONCAT(m.first_name,' ',m.last_name) AS full_name,
           p.plan_name, IFNULL(t.full_name,'Unassigned') AS trainer_name,
           m.joined_date, m.status
    FROM members m
    LEFT JOIN membership_plans p ON m.plan_id=p.id
    LEFT JOIN trainers t ON m.trainer_id=t.id
    ORDER BY m.id DESC LIMIT 5
")->fetchAll();
?>

<div class="page active" id="page-dashboard">
  <div class="kpi-grid">
    <div class="kpi-card">
      <div class="kpi-badge">Active</div>
      <div class="kpi-icon">👥</div>
      <div class="kpi-val"><?= number_format($activeMembers) ?></div>
      <div class="kpi-label">Active Members</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-badge"><?= $attRate ?>%</div>
      <div class="kpi-icon">✅</div>
      <div class="kpi-val"><?= $presentToday ?></div>
      <div class="kpi-label">Present Today</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-badge">This Month</div>
      <div class="kpi-icon">💰</div>
      <div class="kpi-val">₱<?= number_format($monthRevenue / 1000, 0) ?>K</div>
      <div class="kpi-label">Monthly Revenue</div>
    </div>
    <div class="kpi-card">
      <div class="kpi-badge warn">⏰ Alert</div>
      <div class="kpi-icon">⏰</div>
      <div class="kpi-val"><?= $expiringSoon ?></div>
      <div class="kpi-label">Expiring in 30 Days</div>
    </div>
  </div>

  <div class="section-head">
    <h3>Recent Member Registrations</h3>
    <a class="btn btn-outline btn-sm" href="members.php">View all →</a>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Member ID</th><th>Name</th><th>Plan</th><th>Trainer</th><th>Date Joined</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($recentMembers as $m): ?>
        <tr>
          <td><?= htmlspecialchars($m['member_code']) ?></td>
          <td><?= htmlspecialchars($m['full_name']) ?></td>
          <td><?= htmlspecialchars($m['plan_name'] ?? 'N/A') ?></td>
          <td><?= htmlspecialchars($m['trainer_name']) ?></td>
          <td><?= date('M j, Y', strtotime($m['joined_date'])) ?></td>
          <td><?= statusBadge($m['status']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php
function statusBadge($s) {
    $map = ['Active'=>'status-active','Pending'=>'status-pending','Expired'=>'status-expired','Paid'=>'status-paid','Unpaid'=>'status-unpaid','Present'=>'status-present','Absent'=>'status-absent','On Leave'=>'status-pending'];
    return '<span class="status-badge '.($map[$s]??'').'">'.htmlspecialchars($s).'</span>';
}

require_once __DIR__ . '/includes/footer.php';
?>
