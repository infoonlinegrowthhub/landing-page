<?php
/**
 * Authentication, Session & CSRF Helpers for AdminLTE Panel
 */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/database.php';

function isAdminLoggedIn(): bool {
    return !empty($_SESSION['admin_id']);
}

function requireAdminAuth(): void {
    if (!isAdminLoggedIn()) {
        header('Location: login');
        exit;
    }
}

function getCurrentAdmin(): array {
    if (!isAdminLoggedIn()) {
        return [];
    }
    $pdo = getDB();
    $stmt = $pdo->prepare("SELECT id, username, email, full_name, role, avatar, last_login FROM admins WHERE id = ?");
    $stmt->execute([$_SESSION['admin_id']]);
    $admin = $stmt->fetch();
    if (!$admin) {
        session_destroy();
        header('Location: login');
        exit;
    }
    return $admin;
}

function generateCsrfToken(): string {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verifyCsrfToken(?string $token): bool {
    if (empty($_SESSION['csrf_token']) || empty($token)) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

function setFlashMessage(string $type, string $message): void {
    $_SESSION['flash'] = [
        'type'    => $type,
        'message' => $message
    ];
}

function getFlashMessage(): ?array {
    if (!empty($_SESSION['flash'])) {
        $flash = $_SESSION['flash'];
        unset($_SESSION['flash']);
        return $flash;
    }
    return null;
}

