<?php
// ============================================================
// GymPulse — Login / Register / Forgot Password
// ============================================================
require_once __DIR__ . '/includes/auth.php';

startSecureSession();

// Already logged in → redirect
if (isLoggedIn()) {
    header('Location: dashboard.php');
    exit;
}

// Handle POST actions
$error = '';
$success = '';
$view = $_GET['view'] ?? 'login'; // login | register | forgot | reset

// ── LOGIN ────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    if ($action === 'login') {
        $username = trim($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        if (!$username || !$password) {
            $error = 'Please enter your username and password.';
        } else {
            $pdo = getDB();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE (username = ? OR email = ?) AND is_active = 1 LIMIT 1");
            $stmt->execute([$username, $username]);
            $user = $stmt->fetch();
            if ($user && password_verify($password, $user['password_hash'])) {
                loginUser($user);
                header('Location: dashboard.php');
                exit;
            } else {
                $error = 'Invalid username or password.';
            }
        }
    }

    elseif ($action === 'register') {
        $full_name = trim($_POST['full_name'] ?? '');
        $email     = trim($_POST['email'] ?? '');
        $username  = trim($_POST['username'] ?? '');
        $password  = $_POST['password'] ?? '';
        $confirm   = $_POST['confirm_password'] ?? '';
        $role      = in_array($_POST['role'] ?? '', ['admin','trainer','staff']) ? $_POST['role'] : 'staff';

        if (!$full_name || !$email || !$username || !$password) {
            $error = 'All fields are required.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $pdo = getDB();
            $check = $pdo->prepare("SELECT id FROM users WHERE username = ? OR email = ? LIMIT 1");
            $check->execute([$username, $email]);
            if ($check->fetch()) {
                $error = 'Username or email already exists.';
            } else {
                $hash = password_hash($password, PASSWORD_DEFAULT);
                $pdo->prepare("INSERT INTO users (username, email, password_hash, role, full_name) VALUES (?,?,?,?,?)")
                    ->execute([$username, $email, $hash, $role, $full_name]);
                $success = 'Account created! You can now sign in.';
                $view = 'login';
            }
        }
    }

    elseif ($action === 'forgot') {
        $email = trim($_POST['email'] ?? '');
        if (!$email) {
            $error = 'Please enter your email address.';
        } else {
            $pdo = getDB();
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND is_active = 1 LIMIT 1");
            $stmt->execute([$email]);
            $user = $stmt->fetch();
            if ($user) {
                $token = generateResetToken($user['id']);
                // In production, send email. For demo, show link.
                $resetLink = SITE_URL . '/index.php?view=reset&token=' . $token;
                $success = 'Password reset link generated. (Demo: <a href="' . htmlspecialchars($resetLink) . '">Click here to reset</a>)';
            } else {
                $success = 'If this email exists in our system, a reset link has been sent.';
            }
        }
    }

    elseif ($action === 'reset') {
        $token    = trim($_POST['token'] ?? '');
        $password = $_POST['new_password'] ?? '';
        $confirm  = $_POST['confirm_password'] ?? '';
        $user     = validateResetToken($token);
        if (!$user) {
            $error = 'Invalid or expired reset link.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            $pdo = getDB();
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("UPDATE users SET password_hash = ? WHERE id = ?")->execute([$hash, $user['id']]);
            clearResetToken($user['id']);
            $success = 'Password updated successfully! You can now sign in.';
            $view = 'login';
        }
    }
}

// For reset view, validate token
$resetUser = null;
if ($view === 'reset') {
    $token = $_GET['token'] ?? '';
    $resetUser = validateResetToken($token);
    if (!$resetUser) {
        $error = 'This reset link is invalid or has expired.';
        $view = 'forgot';
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>GymPulse — <?= $view === 'register' ? 'Create Account' : ($view === 'forgot' || $view === 'reset' ? 'Reset Password' : 'Sign In') ?></title>
<style>
  @import url('https://fonts.googleapis.com/css2?family=Bebas+Neue&family=Rajdhani:wght@400;500;600;700&family=Inter:wght@300;400;500;600&display=swap');
  :root {
    --red:#e8192c; --red-dark:#b01020; --bg:#0a0a0a; --surface:#141414;
    --surface2:#1c1c1c; --border:#2a2a2a; --text:#f0f0f0; --muted:#888;
  }
  *{margin:0;padding:0;box-sizing:border-box;}
  body{font-family:'Inter',sans-serif;background:var(--bg);color:var(--text);min-height:100vh;display:flex;}
  .login-left{width:420px;background:var(--red);padding:40px;display:flex;flex-direction:column;justify-content:center;position:relative;overflow:hidden;}
  .login-left::before{content:'';position:absolute;width:300px;height:300px;border-radius:50%;background:rgba(255,255,255,.07);top:-80px;left:-80px;}
  .login-left::after{content:'';position:absolute;width:200px;height:200px;border-radius:50%;background:rgba(255,255,255,.05);bottom:-60px;right:-60px;}
  .login-left-content{position:relative;z-index:1;}
  .login-logo{font-family:'Bebas Neue',sans-serif;font-size:48px;letter-spacing:4px;color:#fff;line-height:1;}
  .login-logo span{color:rgba(255,255,255,.5);}
  .login-tagline{font-size:12px;letter-spacing:3px;color:rgba(255,255,255,.7);margin-bottom:32px;}
  .login-features{list-style:none;}
  .login-features li{font-size:13px;color:rgba(255,255,255,.8);padding:6px 0;display:flex;align-items:center;gap:8px;}
  .login-features li::before{content:'•';color:rgba(255,255,255,.4);}
  .login-right{flex:1;display:flex;align-items:center;justify-content:center;padding:40px;overflow-y:auto;}
  .login-form{width:100%;max-width:400px;}
  .login-welcome{font-family:'Bebas Neue',sans-serif;font-size:42px;letter-spacing:2px;margin-bottom:4px;}
  .login-sub{font-size:13px;color:var(--muted);margin-bottom:24px;}
  .role-tabs{display:flex;gap:8px;margin-bottom:20px;}
  .role-tab{flex:1;padding:8px;border-radius:8px;border:1px solid var(--border);background:var(--surface2);color:var(--muted);font-size:13px;font-weight:600;cursor:pointer;text-align:center;transition:all .2s;}
  .role-tab.active{border-color:var(--red);color:var(--text);background:rgba(232,25,44,.12);}
  .login-field{margin-bottom:14px;}
  .login-field label{display:block;font-size:10px;font-weight:600;color:var(--muted);letter-spacing:1.5px;text-transform:uppercase;margin-bottom:5px;}
  .login-field input,.login-field select{width:100%;background:var(--surface2);border:1px solid var(--border);color:var(--text);padding:11px 14px;border-radius:8px;font-size:14px;font-family:'Inter',sans-serif;transition:border-color .2s;}
  .login-field input:focus,.login-field select:focus{outline:none;border-color:var(--red);}
  .login-field select option{background:var(--surface2);}
  .login-opts{display:flex;justify-content:space-between;align-items:center;margin-bottom:20px;font-size:12px;}
  .login-opts label{color:var(--muted);display:flex;align-items:center;gap:6px;cursor:pointer;}
  .forgot{color:var(--red);cursor:pointer;text-decoration:none;font-size:12px;}
  .forgot:hover{text-decoration:underline;}
  .login-btn{width:100%;padding:13px;background:var(--red);color:#fff;border:none;border-radius:8px;font-size:15px;font-weight:700;cursor:pointer;font-family:'Rajdhani',sans-serif;letter-spacing:1px;transition:background .2s;}
  .login-btn:hover{background:var(--red-dark);}
  .ver-badge{margin-top:20px;font-size:11px;color:var(--muted);text-align:center;}
  .error-msg{background:rgba(239,68,68,.15);border:1px solid rgba(239,68,68,.4);color:#ef4444;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:16px;}
  .success-msg{background:rgba(34,197,94,.15);border:1px solid rgba(34,197,94,.4);color:#22c55e;padding:10px 14px;border-radius:8px;font-size:13px;margin-bottom:16px;}
  .link-row{text-align:center;margin-top:16px;font-size:13px;color:var(--muted);}
  .link-row a{color:var(--red);text-decoration:none;font-weight:600;}
  .link-row a:hover{text-decoration:underline;}
  .form-row{display:grid;grid-template-columns:1fr 1fr;gap:12px;}
  ::-webkit-scrollbar{width:6px;}
  ::-webkit-scrollbar-track{background:var(--surface);}
  ::-webkit-scrollbar-thumb{background:var(--border);border-radius:3px;}
  @media(max-width:768px){.login-left{display:none;}.login-right{padding:24px;}}
</style>
</head>
<body>

<div class="login-left">
  <div class="login-left-content">
    <div style="font-size:36px;margin-bottom:8px;">🏋️</div>
    <div class="login-logo">GYM<span>PULSE</span></div>
    <div class="login-tagline">GYM MANAGEMENT INFORMATION SYSTEM</div>
    <ul class="login-features">
      <li>Member Registration &amp; Profiles</li>
      <li>Membership Plan Management</li>
      <li>Attendance &amp; Check-in Tracking</li>
      <li>Payment &amp; Billing System</li>
      <li>Trainer Scheduling</li>
    </ul>
    <div style="margin-top:32px;font-size:11px;color:rgba(255,255,255,.4);">© 2025 GymPulse Management System</div>
  </div>
</div>

<div class="login-right">
  <div class="login-form">

    <?php if ($view === 'login'): ?>
    <!-- ── LOGIN FORM ── -->
    <div class="login-welcome">WELCOME BACK</div>
    <div class="login-sub">Sign in to your GymPulse account</div>

    <?php if ($error): ?><div class="error-msg">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="success-msg">✅ <?= $success ?></div><?php endif; ?>

    <div class="role-tabs">
      <div class="role-tab active" onclick="setRole('admin',this)">⚡ Admin</div>
      <div class="role-tab" onclick="setRole('trainer',this)">🥋 Trainer</div>
      <div class="role-tab" onclick="setRole('staff',this)">🪪 Staff</div>
    </div>

    <form method="POST">
      <input type="hidden" name="action" value="login">
      <div class="login-field">
        <label>Username / Email</label>
        <input type="text" name="username" value="admin@gympulse.com" placeholder="Enter username or email" required>
      </div>
      <div class="login-field">
        <label>Password</label>
        <input type="password" name="password" value="password" placeholder="Enter your password" required>
      </div>
      <div class="login-opts">
        <label><input type="checkbox" name="remember" checked> Remember me</label>
        <a class="forgot" href="?view=forgot">Forgot password?</a>
      </div>
      <button type="submit" class="login-btn">SIGN IN →</button>
    </form>
    <div class="link-row">Don't have an account? <a href="?view=register">Create one</a></div>
    <div class="ver-badge">v<?= APP_VERSION ?> | AY 2024-2025</div>

    <?php elseif ($view === 'register'): ?>
    <!-- ── REGISTER FORM ── -->
    <div class="login-welcome">CREATE ACCOUNT</div>
    <div class="login-sub">Register a new GymPulse user</div>

    <?php if ($error): ?><div class="error-msg">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="POST">
      <input type="hidden" name="action" value="register">
      <div class="login-field">
        <label>Full Name</label>
        <input type="text" name="full_name" placeholder="e.g. Juan Dela Cruz" required value="<?= htmlspecialchars($_POST['full_name'] ?? '') ?>">
      </div>
      <div class="form-row" style="gap:12px;margin-bottom:14px;">
        <div class="login-field" style="margin-bottom:0">
          <label>Username</label>
          <input type="text" name="username" placeholder="username" required value="<?= htmlspecialchars($_POST['username'] ?? '') ?>">
        </div>
        <div class="login-field" style="margin-bottom:0">
          <label>Role</label>
          <select name="role">
            <option value="staff">🪪 Staff</option>
            <option value="trainer">🥋 Trainer</option>
            <option value="admin">⚡ Admin</option>
          </select>
        </div>
      </div>
      <div class="login-field">
        <label>Email</label>
        <input type="email" name="email" placeholder="email@domain.com" required value="<?= htmlspecialchars($_POST['email'] ?? '') ?>">
      </div>
      <div class="login-field">
        <label>Password</label>
        <input type="password" name="password" placeholder="Min. 6 characters" required>
      </div>
      <div class="login-field">
        <label>Confirm Password</label>
        <input type="password" name="confirm_password" placeholder="Repeat password" required>
      </div>
      <button type="submit" class="login-btn">CREATE ACCOUNT →</button>
    </form>
    <div class="link-row">Already have an account? <a href="?view=login">Sign in</a></div>

    <?php elseif ($view === 'forgot'): ?>
    <!-- ── FORGOT PASSWORD ── -->
    <div class="login-welcome">RESET PASSWORD</div>
    <div class="login-sub">Enter your email to receive a reset link</div>

    <?php if ($error): ?><div class="error-msg">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>
    <?php if ($success): ?><div class="success-msg">✅ <?= $success ?></div><?php endif; ?>

    <form method="POST">
      <input type="hidden" name="action" value="forgot">
      <div class="login-field">
        <label>Email Address</label>
        <input type="email" name="email" placeholder="your-email@domain.com" required>
      </div>
      <button type="submit" class="login-btn">SEND RESET LINK →</button>
    </form>
    <div class="link-row"><a href="?view=login">← Back to Sign In</a></div>

    <?php elseif ($view === 'reset'): ?>
    <!-- ── RESET PASSWORD ── -->
    <div class="login-welcome">NEW PASSWORD</div>
    <div class="login-sub">Set a new password for your account</div>

    <?php if ($error): ?><div class="error-msg">⚠️ <?= htmlspecialchars($error) ?></div><?php endif; ?>

    <form method="POST">
      <input type="hidden" name="action" value="reset">
      <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">
      <div class="login-field">
        <label>New Password</label>
        <input type="password" name="new_password" placeholder="Min. 6 characters" required>
      </div>
      <div class="login-field">
        <label>Confirm New Password</label>
        <input type="password" name="confirm_password" placeholder="Repeat new password" required>
      </div>
      <button type="submit" class="login-btn">UPDATE PASSWORD →</button>
    </form>
    <div class="link-row"><a href="?view=login">← Back to Sign In</a></div>
    <?php endif; ?>

  </div>
</div>

<script>
function setRole(role, el) {
  document.querySelectorAll('.role-tab').forEach(t => t.classList.remove('active'));
  el.classList.add('active');
  // Populate demo credentials based on role
  const creds = {
    admin:   { user: 'admin@gympulse.com', pass: 'password' },
    trainer: { user: 'trainer01',           pass: 'password' },
    staff:   { user: 'staff001',            pass: 'password' },
  };
  const u = document.querySelector('input[name="username"]');
  const p = document.querySelector('input[name="password"]');
  if (u && p && creds[role]) { u.value = creds[role].user; p.value = creds[role].pass; }
}
</script>
</body>
</html>
