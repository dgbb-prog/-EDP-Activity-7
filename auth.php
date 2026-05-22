<?php
// ============================================================
// GymPulse — Auth & Session Helpers
// ============================================================

require_once __DIR__ . '/config.php';

function startSecureSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.use_only_cookies', 1);
        session_start();
    }
}

function isLoggedIn(): bool {
    startSecureSession();
    return isset($_SESSION['user_id']) && !empty($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: index.php');
        exit;
    }
}

function currentUser(): array {
    startSecureSession();
    return [
        'id'        => $_SESSION['user_id'] ?? 0,
        'full_name' => $_SESSION['full_name'] ?? '',
        'role'      => $_SESSION['role'] ?? '',
        'email'     => $_SESSION['email'] ?? '',
        'initials'  => $_SESSION['initials'] ?? '',
    ];
}

function loginUser(array $user): void {
    startSecureSession();
    session_regenerate_id(true);
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['full_name'] = $user['full_name'];
    $_SESSION['role']      = $user['role'];
    $_SESSION['email']     = $user['email'];
    $parts = explode(' ', $user['full_name']);
    $_SESSION['initials']  = strtoupper(substr($parts[0], 0, 1) . (isset($parts[1]) ? substr($parts[1], 0, 1) : ''));

    // Update last login
    $pdo = getDB();
    $pdo->prepare("UPDATE users SET last_login = NOW() WHERE id = ?")->execute([$user['id']]);
}

function logoutUser(): void {
    startSecureSession();
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
}

function generateResetToken(int $userId): string {
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', strtotime('+1 hour'));
    $pdo = getDB();
    $pdo->prepare("UPDATE users SET reset_token = ?, reset_token_expires = ? WHERE id = ?")
        ->execute([$token, $expires, $userId]);
    return $token;
}

function validateResetToken(string $token): ?array {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT id, email, full_name FROM users WHERE reset_token = ? AND reset_token_expires > NOW() AND is_active = 1");
    $stmt->execute([$token]);
    return $stmt->fetch() ?: null;
}

function clearResetToken(int $userId): void {
    $pdo = getDB();
    $pdo->prepare("UPDATE users SET reset_token = NULL, reset_token_expires = NULL WHERE id = ?")->execute([$userId]);
}

function getUnreadNotificationCount(int $userId): int {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE user_id = ? AND is_read = 0");
    $stmt->execute([$userId]);
    return (int) $stmt->fetchColumn();
}

function getNotifications(int $userId, int $limit = 20): array {
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT * FROM notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ?");
    $stmt->execute([$userId, $limit]);
    return $stmt->fetchAll();
}

function markNotificationsRead(int $userId): void {
    $pdo = getDB();
    $pdo->prepare("UPDATE notifications SET is_read = 1 WHERE user_id = ?")->execute([$userId]);
}

function addNotification(int $userId, string $title, string $message): void {
    $pdo = getDB();
    $pdo->prepare("INSERT INTO notifications (user_id, title, message) VALUES (?, ?, ?)")
        ->execute([$userId, $title, $message]);
}
