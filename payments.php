<?php
$pageId    = 'payment-tx';
$pageTitle = 'Transaction 2: Payment Recording';
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();
$formError = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'add_payment') {
    $member_id = (int)($_POST['member_id'] ?? 0);
    $plan_id   = (int)($_POST['plan_id'] ?? 0);
    $amount    = (float)($_POST['amount'] ?? 0);
    $method    = $_POST['method'] ?? 'Cash';
    $cashier   = (int)($_POST['cashier_id'] ?? $user['id']);

    if (!$member_id || !$amount) {
        $formError = 'Member and amount are required.';
    } else {
        $year = date('Y');
        $last = $pdo->query("SELECT receipt_no FROM payments ORDER BY id DESC LIMIT 1")->fetchColumn();
        $num  = $last ? ((int)substr($last, -3)) + 1 : 1;
        $receipt = 'RCP-' . $year . '-' . str_pad($num, 3, '0', STR_PAD_LEFT);

        $pdo->prepare("INSERT INTO payments (receipt_no,member_id,plan_id,amount,method,payment_date,cashier_id,status) VALUES (?,?,?,?,?,CURDATE(),?,'Paid')")
            ->execute([$receipt, $member_id, $plan_id, $amount, $method, $cashier]);

        addNotification($user['id'], 'Payment Recorded', "Payment of ₱".number_format($amount,0)." received.");
        header('Location: payments.php?added=1'); exit;
    }
}

$payments = $pdo->query("
    SELECT pay.receipt_no, CONCAT(m.first_name,' ',m.last_name) AS member_name,
           p.plan_name, pay.amount, pay.method, pay.payment_date,
           IFNULL(u.full_name,'System') AS cashier_name, pay.status
    FROM payments pay
    LEFT JOIN members m ON pay.member_id=m.id
    LEFT JOIN membership_plans p ON pay.plan_id=p.id
    LEFT JOIN users u ON pay.cashier_id=u.id
    ORDER BY pay.id DESC
")->fetchAll();

$members  = $pdo->query("SELECT id, CONCAT(first_name,' ',last_name) AS full_name FROM members ORDER BY first_name")->fetchAll();
$plans    = $pdo->query("SELECT * FROM membership_plans")->fetchAll();
$staffers = $pdo->query("SELECT id, full_name FROM users WHERE is_active=1")->fetchAll();

function statusBadge($s) {
    $map=['Paid'=>'status-paid','Unpaid'=>'status-unpaid','Refunded'=>'status-pending'];
    return '<span class="status-badge '.($map[$s]??'').'">'.htmlspecialchars($s).'</span>';
}
?>

<?php if (isset($_GET['added'])): ?>
<script>document.addEventListener('DOMContentLoaded',()=>showToast('Payment Recorded!','Payment successfully logged.'));</script>
<?php endif; ?>

<div class="page active">
  <div class="section-head">
    <h3>Transaction 2: Payment Recording</h3>
    <button class="btn btn-red btn-sm" onclick="openModal('modal-payment')">+ Record Payment</button>
  </div>
  <div class="table-wrap">
    <table>
      <thead><tr><th>Receipt #</th><th>Member</th><th>Plan</th><th>Amount</th><th>Method</th><th>Date</th><th>Cashier</th><th>Status</th></tr></thead>
      <tbody>
        <?php foreach ($payments as $p): ?>
        <tr>
          <td><?= htmlspecialchars($p['receipt_no']) ?></td>
          <td><?= htmlspecialchars($p['member_name']) ?></td>
          <td><?= htmlspecialchars($p['plan_name'] ?? 'N/A') ?></td>
          <td>₱<?= number_format($p['amount'], 0) ?></td>
          <td><?= htmlspecialchars($p['method']) ?></td>
          <td><?= date('M j, Y', strtotime($p['payment_date'])) ?></td>
          <td><?= htmlspecialchars($p['cashier_name']) ?></td>
          <td><?= statusBadge($p['status']) ?></td>
        </tr>
        <?php endforeach; ?>
      </tbody>
    </table>
  </div>
</div>

<!-- Payment Modal -->
<div class="modal-bg" id="modal-payment">
  <div class="modal">
    <button class="modal-close" onclick="closeModal('modal-payment')">✕</button>
    <h2>Record Payment</h2>
    <div class="modal-sub">Transaction 2 — Record a membership payment</div>
    <?php if ($formError): ?><div style="color:#ef4444;margin-bottom:12px;font-size:13px;">⚠️ <?= htmlspecialchars($formError) ?></div><?php endif; ?>
    <form method="POST">
      <input type="hidden" name="action" value="add_payment">
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
          <label>Plan</label>
          <select name="plan_id" id="payment-plan" onchange="autoFillAmount(this)">
            <?php foreach ($plans as $p): ?>
              <option value="<?= $p['id'] ?>" data-price="<?= $p['price'] ?>"><?= htmlspecialchars($p['plan_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="form-row">
        <div class="form-group">
          <label>Amount (₱)</label>
          <input type="number" name="amount" id="payment-amount" placeholder="1200" step="0.01" required>
        </div>
        <div class="form-group">
          <label>Payment Method</label>
          <select name="method">
            <option>Cash</option><option>GCash</option><option>Card</option><option>Bank Transfer</option>
          </select>
        </div>
      </div>
      <div class="form-row full">
        <div class="form-group">
          <label>Cashier</label>
          <select name="cashier_id">
            <?php foreach ($staffers as $s): ?>
              <option value="<?= $s['id'] ?>" <?= $s['id'] == $user['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['full_name']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      </div>
      <div class="modal-actions">
        <button type="button" class="btn btn-outline" onclick="closeModal('modal-payment')">Cancel</button>
        <button type="submit" class="btn btn-green">Record Payment</button>
      </div>
    </form>
  </div>
</div>

<script>
function autoFillAmount(sel) {
  const price = sel.options[sel.selectedIndex].dataset.price;
  document.getElementById('payment-amount').value = price || '';
}
// Init on load
document.addEventListener('DOMContentLoaded', () => autoFillAmount(document.getElementById('payment-plan')));
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
