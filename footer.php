<!-- TOAST -->
<div class="toast" id="toast">
  <div class="toast-title" id="toast-title"></div>
  <div class="toast-msg" id="toast-msg"></div>
</div>

</div><!-- /.main -->

<script>
// ── NOTIFICATIONS ────────────────────────────────────────────
function toggleNotifications() {
  const panel = document.getElementById('notif-panel');
  panel.classList.toggle('open');
}

function markAllRead() {
  fetch('ajax/notifications.php', { method: 'POST', headers: {'Content-Type':'application/x-www-form-urlencoded'}, body: 'action=mark_read' })
    .then(() => {
      document.getElementById('notif-badge') && (document.getElementById('notif-badge').remove());
      document.querySelectorAll('.notif-item.unread').forEach(el => el.classList.remove('unread'));
    });
}

// Close notifications when clicking outside
document.addEventListener('click', function(e) {
  const panel = document.getElementById('notif-panel');
  const btn   = document.getElementById('notif-toggle');
  if (panel && panel.classList.contains('open') && !panel.contains(e.target) && !btn.contains(e.target)) {
    panel.classList.remove('open');
  }
});

// ── TOAST ────────────────────────────────────────────────────
function showToast(title, msg) {
  const t = document.getElementById('toast');
  document.getElementById('toast-title').textContent = title;
  document.getElementById('toast-msg').textContent = msg;
  t.classList.add('show');
  setTimeout(() => t.classList.remove('show'), 3500);
}

// ── MODALS ────────────────────────────────────────────────────
function openModal(id)  { document.getElementById(id).classList.add('open'); }
function closeModal(id) { document.getElementById(id).classList.remove('open'); }

// Close modal on background click
document.querySelectorAll('.modal-bg').forEach(bg => {
  bg.addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('open');
  });
});

// ── STATUS BADGE ─────────────────────────────────────────────
function statusBadge(s) {
  const map = {
    Active:'status-active', Pending:'status-pending', Expired:'status-expired',
    Paid:'status-paid', Unpaid:'status-unpaid', Present:'status-present',
    Absent:'status-absent', 'On Leave':'status-pending', Refunded:'status-pending'
  };
  return `<span class="status-badge ${map[s]||''}">${s}</span>`;
}

// ── XLSX EXPORT HELPERS ───────────────────────────────────────
function getLogoBase64() {
  const canvas = document.createElement('canvas');
  canvas.width = 160; canvas.height = 60;
  const ctx = canvas.getContext('2d');
  ctx.fillStyle = '#e8192c';
  ctx.beginPath(); ctx.roundRect(0, 0, 160, 60, 10); ctx.fill();
  ctx.fillStyle = '#ffffff';
  ctx.fillRect(30, 27, 100, 6);
  ctx.fillRect(18, 18, 14, 24); ctx.fillRect(10, 22, 10, 16);
  ctx.fillRect(128, 18, 14, 24); ctx.fillRect(140, 22, 10, 16);
  ctx.fillStyle = '#ffffff';
  ctx.font = 'bold 18px Arial';
  ctx.textAlign = 'center';
  ctx.fillText('GYMPULSE', 80, 52);
  return canvas.toDataURL('image/png').split(',')[1];
}

function addHeader(title, sub) {
  return [
    ['','','','','','','','',''],['','','','','','','','',''],
    ['','','','','','','','',''],['','','','','','','','',''],['','','','','','','','',''],
    ['','GYMPULSE GYM MANAGEMENT INFORMATION SYSTEM','','','','','','',''],
    ['','GymPulse Fitness Center  |  admin@gympulse.com  |  +63 2 1234 5678','','','','','','',''],
    ['',title,'','','','','','',''],['',sub,'','','','','','',''],['','','','','','','','',''],
    ['','Generated: '+new Date().toLocaleDateString('en-US',{month:'long',day:'numeric',year:'numeric'}),'','','','','','',''],
    ['','','','','','','','',''],
  ];
}

function addSignature(data) {
  data.push(['','','','','','','']);data.push(['','','','','','','']);
  data.push(['','Prepared by:','','','Noted by:','','']);data.push(['','','','','','','']);data.push(['','','','','','','']);
  data.push(['','________________________________','','','________________________________','','']);
  data.push(['','System Administrator','','','Gym Manager / Director','','']);
  data.push(['','GymPulse Fitness Center','','','GymPulse Fitness Center','','']);
}
</script>
</body>
</html>
