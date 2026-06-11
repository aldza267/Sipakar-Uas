<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$user = currentUser();
if ($user['role'] === 'admin') {
    header('Location: /admin/dashboard.php');
} else {
    header('Location: /student/dashboard.php');
}
exit;
