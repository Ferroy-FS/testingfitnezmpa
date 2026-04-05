<?php
/**
 * Auth Middleware — verifikasi session/token
 */

require_once __DIR__ . '/../config/database.php';

function authMiddleware(): array {
    // Cek 1: Session
    if (!empty($_SESSION['user_id'])) {
        return getUserById($_SESSION['user_id']);
    }

    // Cek 2: Bearer token
    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
        return getUserByToken($m[1]);
    }

    // Cek 3: Cookie token
    $cookieToken = $_COOKIE['fitnez_token'] ?? '';
    if ($cookieToken) {
        return getUserByToken($cookieToken);
    }

    jsonResponse(['error' => 'Unauthorized'], 401);
    return [];
}

function getUserById(int $id): array {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT u.id, u.email, u.full_name, u.phone, u.bio, u.has_trainer_cert,
               u.created_at, u.is_active, r.name as role
        FROM users u
        JOIN roles r ON r.id = u.role_id
        WHERE u.id = :id AND u.is_active = true
    ");
    $stmt->execute(['id' => $id]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }
    return $user;
}

function getUserByToken(string $token): array {
    $db = getDB();
    $hash = hash('sha256', $token);
    $stmt = $db->prepare("
        SELECT u.id, u.email, u.full_name, u.phone, u.bio, u.has_trainer_cert,
               u.created_at, u.is_active, r.name as role
        FROM personal_access_tokens pat
        JOIN users u ON u.id = pat.tokenable_id AND pat.tokenable_type = 'user'
        JOIN roles r ON r.id = u.role_id
        WHERE pat.token = :token AND u.is_active = true
    ");
    $stmt->execute(['token' => $hash]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonResponse(['error' => 'Unauthorized'], 401);
    }

    $db->prepare("UPDATE personal_access_tokens SET last_used_at = NOW() WHERE token = :token")
       ->execute(['token' => $hash]);

    return $user;
}

function roleMiddleware(array $user, array $allowedRoles): void {
    if (!in_array($user['role'], $allowedRoles)) {
        jsonResponse(['error' => 'Akses ditolak.'], 403);
    }
}
