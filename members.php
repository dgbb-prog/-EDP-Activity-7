<?php
$pageId    = 'members';
$pageTitle = 'Member Directory';
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();

// Handle add member (AJAX-style POST)
$formError = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'add_member') {
    $fn      = trim($_POST['first_name'] ?? '');
    $ln      = trim($_POST['last_name'] ?? '');
    $email   = trim($_POST['email'] ?? '');
    $phone   = trim($_POST['phone'] ?? '');
    $plan_id = (int)($_POST['plan_id'] ?? 0);
    $trn_id  = $_POST['trainer_id'] === '0' ? null : (int)$_POST['trainer_id'];
    if (!$fn || !$ln) {
        $formError = 'First and last name are required.';
    } else {
        $last = $pdo->query("SELECT member_code FROM members ORDER BY id DESC LIMIT 1")->fetchColumn();
        $num  = $last ? ((int)substr($last, 4)) + 1 : 482;
        $code = 'GYM-' . str_pad($num, 4, '0', STR_PAD_LEFT);
        $pdo->prepare("INSERT INTO members (member_code,first_name,last_name,email,phone,plan_id,trainer_id,status,joined_date) VALUES (?,?,?,?,?,?,?,'Active',CURDATE())")
            ->execute([$code,$fn,$ln,$email,$phone,$plan_id,$trn_id]);
        addNotification($user['id'], 'New Member Registered', "$fn $ln has been registered.");
        header('Location: members.php?added=1');
        exit;
    }
}

$members = $pdo->query("
    SELECT m.*, CONCAT(m.first_name,' ',m.last_name) AS full_name,
           p.plan_name, IFNULL(t.full_name,'Unassigned') AS trainer_name
    FROM members m
    LEFT JOIN membership_plans p ON m.plan_id=p.id
    LEFT JOIN trainers t ON m.trainer_id=t.id
    ORDER BY m.id DESC
")->fetchAll();

$plans   = $pdo->query("SELECT * FROM membership_plans")->fetchAll();
$trainers = $pdo->query("SELECT * FROM trainers WHERE status='Active'")->fetchAll();

function statusBadge($s) {
    $map = ['Active'=>'status-active','Pending'=>'status-pending','Expired'=>'status-expired','On Leave'=>'status-pending'];
    return '<span class="status-badge '.($map[$s]??'').'">'.htmlspecialchars($s).'</span>';
}
?>

<?php if (isset($_GET['added'])): ?>
<script>document.addEventListener('DOMContentLoaded',()=>showToast('Member Added!','New member registered successfully.'));</script>
<?php endif; ?>

<div class="page active" id="page-members">
  <div class="section-head">
    <h3>Member Directory <span style="font-size:13px;color:var(--muted);font-weight:400;">(<?= count($members) ?> total)</span></h3>
    <button class="btn btn-red btn-sm" onclick="openModal('modal-member')">+ Add Member</button>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Member ID</th><th>Name</th><th>Email</th><th>Phone</th><th>Plan</th><th>Trainer</th><th>Joined</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($members as $m): ?>
        <tr>
          <td><?= htmlspecialchars($m['member_code']) ?></td>
          <td><?= htmlspecialchars($m['full_name']) ?></td>
          <td><?= htmlspecialchars($m['email'] ?? '-') ?></td>
          <td><?= htmlspecialchars($m['phone'] ?? '-') ?></td>
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

<!-- Add Member Modal -->
<div class="modal-bg" id="modal-member">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('modal-member')">✕</button>
    <h2>Add New Member</h2>
    <div class="modal-sub">Fill in member information below</div>
    <?php if ($formError): ?><div style="color:#ef4444;margin-bottom:12px;font-size:13px;">⚠️ <?= htmlspecialchars($formError) ?></div><?php endif; ?>
    <form method="POST">
      <input type="hidden" name="action" value="add_member">
      <div class="form-row">
        <div class="form-group"><label>First Name</label><input type="text" name="first_name" placeholder="e.g. Andrea" required></div>
        <div class="form-group"><label>Last Name</label><input type="text" name="last_name" placeholder="e.g. Lopez" required></div>
      </div>
      <div class="form-row">
        <div class="form-group"><label>Email</label><input type="email" name="email" placeholder="email@domain.com"></div>
        <div class="form-group"><label>Phone</label><input type="text" name="phone" placeholder="+63 9XX XXX XXXX"></div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Membership Plan</label>
          <select name="plan_id">
            <?php foreach ($plans as $p): ?>
              <option value="<?= $p['id'] ?>"><?= htmlspecialchars($p['plan_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
        <div class="form-group">
          <label>Assign Trainer</label>
          <select name="trainer_id">
            <option value="0">Unassigned</option>
            <?php foreach ($trainers as $t): ?>
              <option value="<?= $t['id'] ?>"><?= htmlspecialchars($t['full_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-outline" onclick="closeModal('modal-member')">Cancel</button>
        <button type="submit" class="btn btn-red">Save Member</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
