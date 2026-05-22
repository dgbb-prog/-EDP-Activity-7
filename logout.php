<?php
require_once __DIR__ . '/includes/auth.php';
logoutUser();
header('Location: index.php?logout=1');
exit;
