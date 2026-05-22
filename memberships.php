<?php
$pageId    = 'memberships';
$pageTitle = 'Membership Plans';
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();
$plans = $pdo->query("
    SELECT p.*, COUNT(m.id) AS member_count
    FROM membership_plans p
    LEFT JOIN members m ON m.plan_id=p.id AND m.status='Active'
    GROUP BY p.id
")->fetchAll();
?>

<div class="page active">
  <div class="section-head"><h3>Membership Plans</h3></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Plan</th><th>Duration</th><th>Price</th><th>Active Members</th></tr></thead>
      <tbody>
        <?php foreach ($plans as $p): ?>
        <tr>
          <td><?= htmlspecialchars($p['plan_name']) ?></td>
          <td><?= $p['duration_days'] ?> day<?= $p['duration_days'] > 1 ? 's' : '' ?></td>
          <td>₱<?= number_format($p['price'], 0) ?></td>
          <td><?= $p['member_count'] ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
