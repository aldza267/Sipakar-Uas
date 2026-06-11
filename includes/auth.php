<?php
// ============================================
// Si-PAKAR - Auth Helper
// ============================================

require_once __DIR__ . '/config.php';

function startSession(): void {
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }
}

function isLoggedIn(): bool {
    startSession();
    return isset($_SESSION['user_id']);
}

function requireLogin(): void {
    if (!isLoggedIn()) {
        header('Location: /login.php');
        exit;
    }
}

function requireRole(string $role): void {
    requireLogin();
    startSession();
    if ($_SESSION['role'] !== $role) {
        header('Location: /index.php');
        exit;
    }
}

function currentUser(): array {
    startSession();
    return [
        'id'    => $_SESSION['user_id']  ?? null,
        'nama'  => $_SESSION['nama']     ?? '',
        'role'  => $_SESSION['role']     ?? '',
        'email' => $_SESSION['email']    ?? '',
    ];
}

function login(string $email, string $password): array {
    $pdo  = getDB();
    $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
    $stmt->execute([$email]);
    $user = $stmt->fetch();

    if (!$user || !password_verify($password, $user['password'])) {
        return ['success' => false, 'message' => 'Email atau password salah.'];
    }

    startSession();
    session_regenerate_id(true);
    $_SESSION['user_id'] = $user['id'];
    $_SESSION['nama']    = $user['nama'];
    $_SESSION['role']    = $user['role'];
    $_SESSION['email']   = $user['email'];

    return ['success' => true, 'role' => $user['role']];
}

function logout(): void {
    startSession();
    session_destroy();
    header('Location: /login.php');
    exit;
}
