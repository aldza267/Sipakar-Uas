<?php
// ============================================
// Si-PAKAR API — Update Status Tugas (AJAX)
// Dipanggil via Fetch API dari client-side JS
// ============================================

header('Content-Type: application/json');
require_once __DIR__ . '/../includes/auth.php';

if (!isLoggedIn()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    exit;
}

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Method not allowed']);
    exit;
}

$input  = json_decode(file_get_contents('php://input'), true);
$id     = (int)($input['id']     ?? 0);
$status = trim($input['status'] ?? '');

if (!$id || !in_array($status, ['pending', 'selesai'])) {
    echo json_encode(['success' => false, 'message' => 'Data tidak valid']);
    exit;
}

$pdo = getDB();
$user = currentUser();

// Pastikan tugas milik user yang sedang login (RBAC)
$stmt = $pdo->prepare("UPDATE tasks SET status=? WHERE id=? AND user_id=?");
$stmt->execute([$status, $id, $user['id']]);

if ($stmt->rowCount() === 0) {
    echo json_encode(['success' => false, 'message' => 'Tugas tidak ditemukan atau bukan milik Anda']);
    exit;
}

echo json_encode(['success' => true, 'id' => $id, 'status' => $status]);
