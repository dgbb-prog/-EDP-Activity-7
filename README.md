# GymPulse — PHP + MySQL Setup Guide

## Requirements
- PHP 8.0+
- MySQL 5.7+ or MariaDB 10.3+
- Web server (Apache/Nginx) or XAMPP / WAMP

---

## Installation

### 1. Copy files to your web server
```
/htdocs/gympulse/       (XAMPP)
/var/www/html/gympulse/ (Linux Apache)
```

### 2. Create the database
Open phpMyAdmin (or MySQL CLI) and run:
```sql
SOURCE /path/to/gympulse/database.sql;
```
Or paste the contents of `database.sql` into phpMyAdmin's SQL tab.

### 3. Configure the database connection
Edit `includes/config.php`:
```php
define('DB_HOST', 'localhost');
define('DB_USER', 'root');        // Your MySQL username
define('DB_PASS', '');            // Your MySQL password
define('DB_NAME', 'gympulse_db');
define('SITE_URL', 'http://localhost/gympulse');
```

### 4. Open in browser
```
http://localhost/gympulse/
```

---

## Default Login Credentials

| Role    | Username             | Password  |
|---------|----------------------|-----------|
| Admin   | admin@gympulse.com   | password  |
| Trainer | trainer01            | password  |
| Staff   | staff001             | password  |

> ⚠️ Change these passwords immediately after first login!

---

## File Structure

```
gympulse/
├── index.php          ← Login / Register / Forgot Password
├── logout.php         ← Session logout
├── dashboard.php      ← Main dashboard with KPIs
├── members.php        ← Member directory + add member
├── trainers.php       ← Trainers list
├── memberships.php    ← Membership plans
├── enrollment.php     ← TX1: Membership enrollment
├── payments.php       ← TX2: Payment recording
├── attendance.php     ← TX3: Attendance log
├── reports.php        ← Report generator + Excel export
├── database.sql       ← Full DB schema + seed data
├── includes/
│   ├── config.php     ← DB credentials & app config
│   ├── auth.php       ← Login, session, reset token helpers
│   ├── header.php     ← Shared sidebar + topbar layout
│   └── footer.php     ← Shared JS (toast, modals, XLSX)
└── ajax/
    └── notifications.php  ← AJAX: mark read, list, count
```

---

## Features
- ✅ Secure PHP session authentication
- ✅ Role-based login (Admin / Trainer / Staff)
- ✅ User registration
- ✅ Forgot password + token-based reset
- ✅ Notification system with unread count
- ✅ Working logout
- ✅ All transactions backed by MySQL
- ✅ Excel export with logo, header, signature

## Production Notes
- Replace the "demo reset link" in `index.php` with proper email sending (PHPMailer/SMTP)
- Add CSRF tokens to all forms
- Use HTTPS
- Set strong `DB_PASS`

//Updated by Email2 collaborator.
