<?php
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$user = currentUser();
if ($user['role'] === 'admin') {
    header('Location: ' . BASE_PATH . '/admin/dashboard.php');
} else {
    header('Location: ' . BASE_PATH . '/student/dashboard.php');
}
exit;
