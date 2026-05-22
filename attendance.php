<?php
$pageId    = 'attendance-tx';
$pageTitle = 'Transaction 3: Attendance Log';
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();
$formError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_attendance') {
    $member_id = (int)($_POST['member_id'] ?? 0);
    $time_in   = $_POST['time_in'] ?: null;
    $time_out  = $_POST['time_out'] ?: null;
    $status    = $time_in ? 'Present' : 'Absent';

    if (!$member_id) {
        $formError = 'Please select a member.';
    } else {
        $duration = 0;
        if ($time_in && $time_out) {
            $in  = strtotime($time_in);
            $out = strtotime($time_out);
            $duration = max(0, (int)(($out - $in) / 60));
        }

        $last = $pdo->query("SELECT log_code FROM attendance ORDER BY id DESC LIMIT 1")->fetchColumn();
        $num  = $last ? ((int)substr($last, 4)) + 1 : 1;
        $code = 'ATT-' . str_pad($num, 4, '0', STR_PAD_LEFT);

        $pdo->prepare("INSERT INTO attendance (log_code,member_id,attendance_date,time_in,time_out,duration_minutes,status) VALUES (?,?,CURDATE(),?,?,?,?)")
            ->execute([$code, $member_id, $time_in, $time_out, $duration, $status]);

        header('Location: attendance.php?added=1'); exit;
    }
}

$records = $pdo->query("
    SELECT a.log_code, CONCAT(m.first_name,' ',m.last_name) AS member_name,
           m.member_code, a.attendance_date, a.time_in, a.time_out, a.duration_minutes, a.status
    FROM attendance a
    LEFT JOIN members m ON a.member_id=m.id
    ORDER BY a.id DESC
")->fetchAll();

$members = $pdo->query("SELECT id, CONCAT(first_name,' ',last_name) AS full_name, member_code FROM members ORDER BY first_name")->fetchAll();

function statusBadge($s) {
    $map=['Present'=>'status-present','Absent'=>'status-absent'];
    return '<span class="status-badge '.($map[$s]??'').'">'.htmlspecialchars($s).'</span>';
}

function fmtTime($t) {
    if (!$t) return '-';
    return date('g:i A', strtotime($t));
}

function fmtDur($mins) {
    if (!$mins) return '-';
    return floor($mins/60).'h '.($mins%60).'m';
}
?>

<?php if (isset($_GET['added'])): ?>
<script>document.addEventListener('DOMContentLoaded',()=>showToast('Attendance Logged!','Check-in recorded successfully.'));</script>
<?php endif; ?>

<div class="page active">
  <div class="section-head">
    <h3>Transaction 3: Attendance Log</h3>
    <button class="btn btn-red btn-sm" onclick="openModal('modal-attendance')">+ Log Attendance</button>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Log ID</th><th>Member</th><th>Member ID</th><th>Date</th><th>Time In</th><th>Time Out</th><th>Duration</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($records as $a): ?>
        <tr>
          <td><?= htmlspecialchars($a['log_code']) ?></td>
          <td><?= htmlspecialchars($a['member_name']) ?></td>
          <td><?= htmlspecialchars($a['member_code']) ?></td>
          <td><?= date('M j, Y', strtotime($a['attendance_date'])) ?></td>
          <td><?= fmtTime($a['time_in']) ?></td>
          <td><?= fmtTime($a['time_out']) ?></td>
          <td><?= fmtDur($a['duration_minutes']) ?></td>
          <td><?= statusBadge($a['status']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Attendance Modal -->
<div class="modal-bg" id="modal-attendance">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('modal-attendance')">✕</button>
    <h2>Log Attendance</h2>
    <div class="modal-sub">Transaction 3 — Record a member check-in</div>
    <?php if ($formError): ?><div style="color:#ef4444;margin-bottom:12px;font-size:13px;">⚠️ <?= htmlspecialchars($formError) ?></div><?php endif; ?>
    <form method="POST">
      <input type="hidden" name="action" value="add_attendance">
      <div class="form-row full">
        <div class="form-group">
          <label>Member</label>
          <select name="member_id" required>
            <option value="">— Select Member —</option>
            <?php foreach ($members as $m): ?>
              <option value="<?= $m['id'] ?>"><?= htmlspecialchars($m['full_name']) ?> (<?= htmlspecialchars($m['member_code']) ?>)</option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Time In</label>
          <input type="time" name="time_in" value="<?= date('H:i') ?>">
        </div>
        <div class="form-group">
          <label>Time Out</label>
          <input type="time" name="time_out">
        </div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-outline" onclick="closeModal('modal-attendance')">Cancel</button>
        <button type="submit" class="btn btn-red">Log Check-in</button>
      </div>
    </form>
  </div>
</div>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
