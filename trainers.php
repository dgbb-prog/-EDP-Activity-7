<?php
$pageId    = 'trainers';
$pageTitle = 'Trainers';
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();
$trainers = $pdo->query("
    SELECT t.*, COUNT(m.id) AS member_count
    FROM trainers t
    LEFT JOIN members m ON m.trainer_id=t.id AND m.status='Active'
    GROUP BY t.id
    ORDER BY t.id
")->fetchAll();

function statusBadge($s) {
    $map = ['Active'=>'status-active','On Leave'=>'status-pending','Inactive'=>'status-expired'];
    return '<span class="status-badge '.($map[$s]??'').'">'.htmlspecialchars($s).'</span>';
}
?>

<div class="page active">
  <div class="section-head"><h3>Trainers</h3></div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Trainer ID</th><th>Name</th><th>Specialization</th><th>Active Members</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($trainers as $t): ?>
        <tr>
          <td><?= htmlspecialchars($t['trainer_code']) ?></td>
          <td><?= htmlspecialchars($t['full_name']) ?></td>
          <td><?= htmlspecialchars($t['specialization']) ?></td>
          <td><?= $t['member_count'] ?></td>
          <td><?= statusBadge($t['status']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
