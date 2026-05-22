<?php
$pageId    = 'membership-tx';
$pageTitle = 'Transaction 1: Membership Enrollment';
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();
$formError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_enrollment') {
    $member_id  = (int)($_POST['member_id'] ?? 0);
    $plan_id    = (int)($_POST['plan_id'] ?? 0);
    $start_date = $_POST['start_date'] ?? date('Y-m-d');
    $by         = (int)($_POST['processed_by'] ?? $user['id']);

    if (!$member_id || !$plan_id) {
        $formError = 'Member and plan are required.';
    } else {
        $plan = $pdo->prepare("SELECT * FROM membership_plans WHERE id=?")->execute([$plan_id]) ? $pdo->prepare("SELECT * FROM membership_plans WHERE id=?")->execute([$plan_id]) : null;
        $planRow = $pdo->prepare("SELECT * FROM membership_plans WHERE id=?");
        $planRow->execute([$plan_id]);
        $planRow = $planRow->fetch();

        $expiry = date('Y-m-d', strtotime($start_date . ' + ' . $planRow['duration_days'] . ' days'));
        $last = $pdo->query("SELECT enrollment_code FROM enrollments ORDER BY id DESC LIMIT 1")->fetchColumn();
        $num  = $last ? ((int)substr($last, 4)) + 1 : 1;
        $code = 'ENR-' . str_pad($num, 4, '0', STR_PAD_LEFT);

        $pdo->prepare("INSERT INTO enrollments (enrollment_code,member_id,plan_id,start_date,expiry_date,amount,processed_by,status) VALUES (?,?,?,?,?,?,?,'Active')")
            ->execute([$code, $member_id, $plan_id, $start_date, $expiry, $planRow['price'], $by]);

        addNotification($user['id'], 'New Enrollment', "Member enrolled under {$planRow['plan_name']} plan.");
        header('Location: enrollment.php?added=1'); exit;
    }
}

$enrollments = $pdo->query("
    SELECT e.enrollment_code, CONCAT(m.first_name,' ',m.last_name) AS member_name,
           p.plan_name, p.price, e.start_date, e.expiry_date, e.amount,
           IFNULL(u.full_name,'System') AS processed_by, e.status
    FROM enrollments e
    LEFT JOIN members m ON e.member_id=m.id
    LEFT JOIN membership_plans p ON e.plan_id=p.id
    LEFT JOIN users u ON e.processed_by=u.id
    ORDER BY e.id DESC
")->fetchAll();

$members  = $pdo->query("SELECT id, CONCAT(first_name,' ',last_name) AS full_name FROM members ORDER BY first_name")->fetchAll();
$plans    = $pdo->query("SELECT * FROM membership_plans")->fetchAll();
$staffers = $pdo->query("SELECT id, full_name FROM users WHERE is_active=1 ORDER BY full_name")->fetchAll();

function statusBadge($s) {
    $map=['Active'=>'status-active','Pending'=>'status-pending','Expired'=>'status-expired'];
    return '<span class="status-badge '.($map[$s]??'').'">'.htmlspecialchars($s).'</span>';
}
?>

<?php if (isset($_GET['added'])): ?>
<script>document.addEventListener('DOMContentLoaded',()=>showToast('Enrollment Processed!','New enrollment recorded.'));</script>
<?php endif; ?>

<div class="page active">
  <div class="section-head">
    <h3>Transaction 1: Membership Enrollment</h3>
    <button class="btn btn-red btn-sm" onclick="openModal('modal-enrollment')">+ New Enrollment</button>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>TX ID</th><th>Member</th><th>Plan</th><th>Start Date</th><th>Expiry</th><th>Amount</th><th>Processed By</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($enrollments as $e): ?>
        <tr>
          <td><?= htmlspecialchars($e['enrollment_code']) ?></td>
          <td><?= htmlspecialchars($e['member_name']) ?></td>
          <td><?= htmlspecialchars($e['plan_name']) ?></td>
          <td><?= date('M j, Y', strtotime($e['start_date'])) ?></td>
          <td><?= date('M j, Y', strtotime($e['expiry_date'])) ?></td>
          <td>₱<?= number_format($e['amount'], 0) ?></td>
          <td><?= htmlspecialchars($e['processed_by']) ?></td>
          <td><?= statusBadge($e['status']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Enrollment Modal -->
<div class="modal-bg" id="modal-enrollment">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('modal-enrollment')">✕</button>
    <h2>New Membership Enrollment</h2>
    <div class="modal-sub">Transaction 1 — Process a new membership enrollment</div>
    <?php if ($formError): ?><div style="color:#ef4444;margin-bottom:12px;font-size:13px;">⚠️ <?= htmlspecialchars($formError) ?></div><?php endif; ?>
    <form method="POST">
      <input type="hidden" name="action" value="add_enrollment">
      <div class="form-row">
        <div class="form-group">
          <label>Member</label>
          <select name="member_id" required>
            <option value="">— Select Member —</option>
            <?php foreach ($members as $m): ?>
              <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['full_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Membership Plan</label>
          <select name="plan_id" required>
            <?php foreach ($plans as $p): ?>
              <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['plan_name']) ?> — ₱<?= number_format($p['price'],0) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Start Date</label>
          <input type="date" name="start_date" value="<?= date('Y-m-d') ?>" required>
        </div>
        <div class="form-group">
          <label>Processed By</label>
          <select name="processed_by">
            <?php foreach ($staffers as $s): ?>
              <option value="<?= $s['id'] ?>" <?= $s['id'] == $user['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['full_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-outline" onclick="closeModal('modal-enrollment')">Cancel</button>
        <button type="submit" class="btn btn-red">Enroll</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
