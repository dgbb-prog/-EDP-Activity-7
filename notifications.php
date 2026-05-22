<?php
require_once __DIR__ . '/../includes/auth.php';
requireLogin();

header('Content-Type: application/json');

$user   = currentUser();
$action = $_POST['action'] ?? $_GET['action'] ?? '';

if ($action === 'mark_read') {
    markNotificationsRead($user['id']);
    echo json_encode(['success' => true]);

} elseif ($action === 'count') {
    echo json_encode(['count' => getUnreadNotificationCount($user['id'])]);

} elseif ($action === 'list') {
    $notifs = getNotifications($user['id']);
    echo json_encode(['notifications' => $notifs]);

} else {
    http_response_code(400);
    echo json_encode(['error' => 'Unknown action']);
}
