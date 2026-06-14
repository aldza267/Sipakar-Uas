<?php
header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    echo json_encode(['success' => false]);
    exit;
}

$pdo  = getDB();
$user = currentUser();
$uid  = $user['id'];

$stmt1 = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id=?");
$stmt1->execute([$uid]);
$total = (int)$stmt1->fetchColumn();

$stmt2 = $pdo->prepare("SELECT COUNT(*) FROM tasks WHERE user_id=? AND status='selesai'");
$stmt2->execute([$uid]);
$selesai = (int)$stmt2->fetchColumn();

$progress = $total > 0 ? round(($selesai / $total) * 100) : 0;

echo json_encode([
    'success'  => true,
    'total'    => $total,
    'selesai'  => $selesai,
    'progress' => $progress
]);
