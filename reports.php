<?php
$pageId    = 'reports';
$pageTitle = 'Report Generator';
require_once __DIR__ . '/includes/header.php';

$pdo = getDB();

// Fetch all data for JS export (JSON-encoded)
$enrollments = $pdo->query("
    SELECT e.enrollment_code AS id, CONCAT(m.first_name,' ',m.last_name) AS member,
           p.plan_name AS plan, e.start_date AS start, e.expiry_date AS expiry,
           e.amount, IFNULL(u.full_name,'System') AS by_user, e.status
    FROM enrollments e
    LEFT JOIN members m ON e.member_id=m.id
    LEFT JOIN membership_plans p ON e.plan_id=p.id
    LEFT JOIN users u ON e.processed_by=u.id
    ORDER BY e.id DESC
")->fetchAll();

$payments = $pdo->query("
    SELECT pay.receipt_no AS receipt, CONCAT(m.first_name,' ',m.last_name) AS member,
           p.plan_name AS plan, pay.amount, pay.method, pay.payment_date AS date,
           IFNULL(u.full_name,'System') AS cashier, pay.status
    FROM payments pay
    LEFT JOIN members m ON pay.member_id=m.id
    LEFT JOIN membership_plans p ON pay.plan_id=p.id
    LEFT JOIN users u ON pay.cashier_id=u.id
    ORDER BY pay.id DESC
")->fetchAll();

$attendances = $pdo->query("
    SELECT a.log_code AS id, CONCAT(m.first_name,' ',m.last_name) AS name,
           m.member_code AS mid, a.attendance_date AS date,
           a.time_in, a.time_out, a.duration_minutes, a.status
    FROM attendance a
    LEFT JOIN members m ON a.member_id=m.id
    ORDER BY a.id DESC
")->fetchAll();

function fmtTime($t) { return $t ? date('g:i A', strtotime($t)) : '-'; }
function fmtDur($m)  { return $m ? floor($m/60).'h '.($m%60).'m' : '-'; }

// Format for JS
$jsEnrollments = json_encode(array_map(fn($e) => [
    'id' => $e['id'], 'member' => $e['member'],
    'plan' => $e['plan'].' — ₱'.number_format($e['amount'],0),
    'start' => date('M j, Y', strtotime($e['start'])),
    'expiry' => date('M j, Y', strtotime($e['expiry'])),
    'amount' => '₱'.number_format($e['amount'],0),
    'by' => $e['by_user'], 'status' => $e['status'],
], $enrollments));

$jsPayments = json_encode(array_map(fn($p) => [
    'receipt' => $p['receipt'], 'member' => $p['member'], 'plan' => $p['plan'],
    'amount' => '₱'.number_format($p['amount'],0), 'method' => $p['method'],
    'date' => date('M j, Y', strtotime($p['date'])),
    'cashier' => $p['cashier'], 'status' => $p['status'],
], $payments));

$jsAttendances = json_encode(array_map(fn($a) => [
    'id' => $a['id'], 'name' => $a['name'], 'mid' => $a['mid'],
    'date' => date('M j, Y', strtotime($a['date'])),
    'tin' => fmtTime($a['time_in']), 'tout' => fmtTime($a['time_out']),
    'dur' => fmtDur($a['duration_minutes']), 'status' => $a['status'],
], $attendances));
?>

<div class="page active" id="page-reports">
  <div class="report-layout">
    <div>
      <div class="section-head"><h3>Report Generator</h3></div>
      <div class="card" style="margin-bottom:16px;">
        <h4>Select Report Type</h4>
        <div class="report-type-grid">
          <div class="report-type-btn selected" onclick="selectReport('membership',this)">
            <div class="rt-icon">🪪</div><div class="rt-title">Membership</div><div class="rt-sub">Plans &amp; renewals</div>
          </div>
          <div class="report-type-btn" onclick="selectReport('attendance',this)">
            <div class="rt-icon">📅</div><div class="rt-title">Attendance</div><div class="rt-sub">Daily/monthly log</div>
          </div>
          <div class="report-type-btn" onclick="selectReport('payment',this)">
            <div class="rt-icon">💳</div><div class="rt-title">Revenue</div><div class="rt-sub">Payments &amp; income</div>
          </div>
          <div class="report-type-btn" onclick="selectReport('expiring',this)">
            <div class="rt-icon">⏰</div><div class="rt-title">Expiring</div><div class="rt-sub">Due for renewal</div>
          </div>
        </div>
      </div>
      <div class="section-head">
        <h3>📋 Report Preview <span style="font-size:13px;color:var(--muted);font-weight:400;" id="rpt-label"></span></h3>
        <span style="font-size:11px;background:rgba(34,197,94,.15);color:#22c55e;padding:3px 10px;border-radius:20px;font-weight:600;">⚡ Live Preview</span>
      </div>
      <div style="font-size:12px;color:var(--muted);margin-bottom:10px;" id="rpt-meta"></div>
      <div class="table-wrap">
        <table><thead id="rpt-head"></thead><tbody id="rpt-body"></tbody></table>
      </div>
    </div>

    <!-- FILTERS -->
    <div class="filters-box">
      <h4>Report Filters</h4>
      <div class="sub">Narrow down the scope</div>
      <div class="filter-label">Membership Plan</div>
      <select class="filter-input" id="f-plan" onchange="refreshReport()">
        <option>All Plans</option><option>Monthly</option><option>Quarterly</option><option>Annual</option><option>Day Pass</option>
      </select>
      <div class="filter-label">Status</div>
      <select class="filter-input" id="f-status" onchange="refreshReport()">
        <option>All Status</option><option>Active</option><option>Pending</option><option>Expired</option>
      </select>
      <div class="filter-label">Date Range</div>
      <input type="date" class="filter-input" id="f-start" value="<?= date('Y-m-01') ?>" onchange="refreshReport()" style="margin-bottom:6px;">
      <input type="date" class="filter-input" id="f-end" value="<?= date('Y-m-t') ?>" onchange="refreshReport()">
      <div class="filter-label" style="margin-top:16px;">Export</div>
      <div class="export-btns">
        <button class="export-btn primary" onclick="exportExcel('membership')">📄 Membership</button>
        <button class="export-btn" onclick="exportExcel('attendance')">📅 Attendance</button>
        <button class="export-btn" onclick="exportExcel('payment')">💳 Revenue</button>
      </div>
      <div style="font-size:11px;color:var(--muted);margin-top:10px;">Each Excel file includes company header, signature placeholder, and chart data on Sheet 2.</div>
    </div>
  </div>
</div>

<script>
// Inject PHP data
const enrollments = <?= $jsEnrollments ?>;
const payments    = <?= $jsPayments ?>;
const attendances = <?= $jsAttendances ?>;

let selectedReport = 'membership';

function selectReport(type, el) {
  selectedReport = type;
  document.querySelectorAll('.report-type-btn').forEach(b => b.classList.remove('selected'));
  el.classList.add('selected');
  refreshReport();
}

function refreshReport() {
  const head   = document.getElementById('rpt-head');
  const body   = document.getElementById('rpt-body');
  const lbl    = document.getElementById('rpt-label');
  const meta   = document.getElementById('rpt-meta');
  const plan   = document.getElementById('f-plan').value;
  const status = document.getElementById('f-status').value;

  if (selectedReport === 'membership') {
    lbl.textContent = '— Membership Enrollment';
    head.innerHTML = '<tr><th>TX ID</th><th>Member</th><th>Plan</th><th>Start</th><th>Expiry</th><th>Amount</th><th>By</th><th>Status</th></tr>';
    let data = enrollments.filter(e =>
      (plan === 'All Plans' || e.plan.includes(plan)) &&
      (status === 'All Status' || e.status === status)
    );
    body.innerHTML = data.map(e => `<tr><td>${e.id}</td><td>${e.member}</td><td>${e.plan.split(' —')[0]}</td><td>${e.start}</td><td>${e.expiry}</td><td>${e.amount}</td><td>${e.by}</td><td>${statusBadge(e.status)}</td></tr>`).join('');
    meta.innerHTML = `<strong>Total Records:</strong> ${data.length}`;

  } else if (selectedReport === 'attendance') {
    lbl.textContent = '— Attendance Log';
    head.innerHTML = '<tr><th>Log ID</th><th>Member</th><th>Member ID</th><th>Date</th><th>Time In</th><th>Time Out</th><th>Duration</th><th>Status</th></tr>';
    body.innerHTML = attendances.map(a => `<tr><td>${a.id}</td><td>${a.name}</td><td>${a.mid}</td><td>${a.date}</td><td>${a.tin}</td><td>${a.tout}</td><td>${a.dur}</td><td>${statusBadge(a.status)}</td></tr>`).join('');
    const present = attendances.filter(a => a.status === 'Present').length;
    meta.innerHTML = `<strong>Total:</strong> ${attendances.length} &nbsp; <strong>Present:</strong> ${present} &nbsp; <strong>Absent:</strong> ${attendances.length - present}`;

  } else if (selectedReport === 'payment') {
    lbl.textContent = '— Revenue Report';
    head.innerHTML = '<tr><th>Receipt #</th><th>Member</th><th>Plan</th><th>Amount</th><th>Method</th><th>Date</th><th>Cashier</th><th>Status</th></tr>';
    let data = payments.filter(p => (plan === 'All Plans' || p.plan === plan) && (status === 'All Status' || p.status === status));
    body.innerHTML = data.map(p => `<tr><td>${p.receipt}</td><td>${p.member}</td><td>${p.plan}</td><td>${p.amount}</td><td>${p.method}</td><td>${p.date}</td><td>${p.cashier}</td><td>${statusBadge(p.status)}</td></tr>`).join('');
    meta.innerHTML = `<strong>Total Records:</strong> ${data.length}`;

  } else if (selectedReport === 'expiring') {
    lbl.textContent = '— Expiring Memberships';
    head.innerHTML = '<tr><th>Enrollment ID</th><th>Member</th><th>Plan</th><th>Expiry Date</th><th>Amount</th><th>Status</th></tr>';
    let data = enrollments.filter(e => e.status === 'Expired' || e.status === 'Pending');
    body.innerHTML = data.map(e => `<tr><td>${e.id}</td><td>${e.member}</td><td>${e.plan.split(' —')[0]}</td><td>${e.expiry}</td><td>${e.amount}</td><td>${statusBadge(e.status)}</td></tr>`).join('');
    meta.innerHTML = `<strong>Total Expiring:</strong> ${data.length}`;
  }
}

// ── EXCEL EXPORTS ─────────────────────────────────────────────
function exportExcel(type) {
  const wb = XLSX.utils.book_new();
  const logo = getLogoBase64();
  if (type === 'membership') buildMembershipSheet(wb, logo);
  else if (type === 'attendance') buildAttendanceSheet(wb, logo);
  else if (type === 'payment') buildPaymentSheet(wb, logo);
  const names = { membership:'GymPulse_Membership_Report', attendance:'GymPulse_Attendance_Report', payment:'GymPulse_Revenue_Report' };
  XLSX.writeFile(wb, names[type] + '.xlsx');
  showToast('Export Successful!', names[type] + '.xlsx downloaded.');
}

function buildMembershipSheet(wb, logo) {
  const data = addHeader('MEMBERSHIP ENROLLMENT REPORT', 'Period: Current Data | All Plans');
  data.push(['','TX ID','Member Name','Plan','Start Date','Expiry Date','Amount Paid','Processed By','Status']);
  enrollments.forEach(e => data.push(['',e.id,e.member,e.plan.split(' —')[0],e.start,e.expiry,e.amount,e.by,e.status]));
  addSignature(data);
  const ws = XLSX.utils.aoa_to_sheet(data);
  ws['!cols'] = [{wch:3},{wch:14},{wch:24},{wch:14},{wch:14},{wch:14},{wch:14},{wch:20},{wch:12}];
  ws['!merges'] = [{s:{r:5,c:1},e:{r:5,c:8}},{s:{r:6,c:1},e:{r:6,c:8}},{s:{r:7,c:1},e:{r:7,c:8}},{s:{r:8,c:1},e:{r:8,c:8}}];
  addImageToSheet(ws, logo);
  XLSX.utils.book_append_sheet(wb, ws, 'Membership Report');
  addChartSheet(wb, logo, 'Membership Chart', getChartData(enrollments));
}

function buildAttendanceSheet(wb, logo) {
  const data = addHeader('ATTENDANCE LOG REPORT', 'Period: Current Data');
  data.push(['','Log ID','Member Name','Member ID','Date','Time In','Time Out','Duration','Status']);
  attendances.forEach(a => data.push(['',a.id,a.name,a.mid,a.date,a.tin,a.tout,a.dur,a.status]));
  addSignature(data);
  const ws = XLSX.utils.aoa_to_sheet(data);
  ws['!cols'] = [{wch:3},{wch:12},{wch:22},{wch:12},{wch:14},{wch:10},{wch:10},{wch:10},{wch:10}];
  ws['!merges'] = [{s:{r:5,c:1},e:{r:5,c:8}},{s:{r:6,c:1},e:{r:6,c:8}},{s:{r:7,c:1},e:{r:7,c:8}},{s:{r:8,c:1},e:{r:8,c:8}}];
  addImageToSheet(ws, logo);
  XLSX.utils.book_append_sheet(wb, ws, 'Attendance Report');
  const present = attendances.filter(a=>a.status==='Present').length;
  const chartData = [['Attendance Summary','',''],['','',''],['Status','Count',''],['Present',present,''],['Absent',attendances.length-present,'']];
  const ws2 = XLSX.utils.aoa_to_sheet(chartData);
  addImageToSheet(ws2, logo);
  XLSX.utils.book_append_sheet(wb, ws2, 'Chart Data');
}

function buildPaymentSheet(wb, logo) {
  const data = addHeader('REVENUE REPORT', 'Period: Current Data | All Plans');
  data.push(['','Receipt #','Member Name','Plan','Amount','Method','Date','Cashier','Status']);
  payments.forEach(p => data.push(['',p.receipt,p.member,p.plan,p.amount,p.method,p.date,p.cashier,p.status]));
  addSignature(data);
  const ws = XLSX.utils.aoa_to_sheet(data);
  ws['!cols'] = [{wch:3},{wch:16},{wch:22},{wch:12},{wch:12},{wch:14},{wch:14},{wch:16},{wch:10}];
  ws['!merges'] = [{s:{r:5,c:1},e:{r:5,c:8}},{s:{r:6,c:1},e:{r:6,c:8}},{s:{r:7,c:1},e:{r:7,c:8}},{s:{r:8,c:1},e:{r:8,c:8}}];
  addImageToSheet(ws, logo);
  XLSX.utils.book_append_sheet(wb, ws, 'Revenue Report');
  const m = {}; payments.forEach(p => m[p.method] = (m[p.method]||0)+1);
  const chartData = [['Revenue Chart','',''],['','',''],['Method','Count',''],...Object.entries(m).map(([k,v])=>[k,v,''])];
  const ws2 = XLSX.utils.aoa_to_sheet(chartData);
  addImageToSheet(ws2, logo);
  XLSX.utils.book_append_sheet(wb, ws2, 'Chart Data');
}

function addImageToSheet(ws, logo) {
  if (!ws['!images']) ws['!images'] = [];
  ws['!images'].push({name:'logo.png',data:logo,opts:{base64:true},position:{type:'twoCellAnchor',attrs:{editAs:'oneCell'},from:{col:1,colOff:0,row:0,rowOff:0},to:{col:4,colOff:0,row:5,rowOff:0}}});
}

function getChartData(enrs) {
  const plans = {}; enrs.forEach(e => { const p=e.plan.split(' —')[0]; plans[p]=(plans[p]||0)+1; });
  return [['Plan Chart','',''],['','',''],['Plan','Count',''],...Object.entries(plans).map(([k,v])=>[k,v,''])];
}

function addChartSheet(wb, logo, name, data) {
  const ws2 = XLSX.utils.aoa_to_sheet(data);
  addImageToSheet(ws2, logo);
  XLSX.utils.book_append_sheet(wb, ws2, name);
}

// Init
document.addEventListener('DOMContentLoaded', refreshReport);
</script>

<?php require_once __DIR__ . '/includes/footer.php'; ?>
