<?php
/**
 * Auth Controller — Login, Register, Logout, Me
 * Authentication: Session + Cookie + Salt/SHA-256 + Token
 * Register SELALU sebagai member.
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

/**
 * POST /api/auth/register — selalu sebagai member
 */
function handleRegister(): void {
    $body = getJsonBody();
    $name     = trim($body['name'] ?? '');
    $email    = trim($body['email'] ?? '');
    $password = $body['password'] ?? '';

    // Force role = member
    $roleName = 'member';

    if (!$name || !$email || !$password) {
        jsonResponse(['error' => 'Semua field wajib diisi.'], 400);
    }
    if (strlen($password) < 6) {
        jsonResponse(['error' => 'Password minimal 6 karakter.'], 400);
    }

    $db = getDB();

    // Cek email duplikat
    $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) {
        jsonResponse(['error' => 'Email sudah terdaftar.'], 409);
    }

    // Cari role_id member
    $stmt = $db->prepare("SELECT id FROM roles WHERE name = :name");
    $stmt->execute(['name' => $roleName]);
    $role = $stmt->fetch();
    if (!$role) {
        jsonResponse(['error' => 'Role tidak valid.'], 400);
    }

    // Generate salt + hash password
    $salt = bin2hex(random_bytes(16));
    $hash = hash('sha256', $salt . $password);

    // Insert user (dengan kolom bio)
    $stmt = $db->prepare("
        INSERT INTO users (email, password_hash, full_name, role_id, bio, created_at, is_active)
        VALUES (:email, :hash, :name, :role_id, '', NOW(), true)
        RETURNING id
    ");
    $stmt->execute([
        'email'   => $email,
        'hash'    => $hash,
        'name'    => $name,
        'role_id' => $role['id'],
    ]);
    $userId = $stmt->fetchColumn();

    // Insert authentications
    $stmt = $db->prepare("
        INSERT INTO authentications (user_id, email, password_hash, salt, provider, is_active)
        VALUES (:uid, :email, :hash, :salt, 'local', true)
    ");
    $stmt->execute([
        'uid'   => $userId,
        'email' => $email,
        'hash'  => $hash,
        'salt'  => $salt,
    ]);

    jsonResponse(['message' => 'Registrasi berhasil.'], 201);
}

/**
 * POST /api/auth/login
 */
function handleLogin(): void {
    $body = getJsonBody();
    $email    = trim($body['email'] ?? '');
    $password = $body['password'] ?? '';

    if (!$email || !$password) {
        jsonResponse(['error' => 'Email dan password wajib diisi.'], 400);
    }

    $db = getDB();

    $stmt = $db->prepare("
        SELECT u.id, u.email, u.full_name, u.phone, u.bio, u.has_trainer_cert,
               u.created_at, u.is_active,
               r.name as role, a.salt, a.password_hash
        FROM users u
        JOIN roles r ON r.id = u.role_id
        JOIN authentications a ON a.user_id = u.id
        WHERE u.email = :email
    ");
    $stmt->execute(['email' => $email]);
    $user = $stmt->fetch();

    if (!$user) {
        jsonResponse(['error' => 'Email atau password salah.'], 401);
    }

    if (!$user['is_active']) {
        jsonResponse(['error' => 'Akun tidak aktif.'], 403);
    }

    $hash = hash('sha256', $user['salt'] . $password);
    if (!hash_equals($user['password_hash'], $hash)) {
        jsonResponse(['error' => 'Email atau password salah.'], 401);
    }

    // SESSION
    $_SESSION['user_id']   = $user['id'];
    $_SESSION['user_role'] = $user['role'];

    // TOKEN
    $plainToken = generateToken(64);
    $hashedToken = hash('sha256', $plainToken);

    $stmt = $db->prepare("
        INSERT INTO personal_access_tokens (tokenable_type, tokenable_id, name, token, abilities, created_at, updated_at)
        VALUES ('user', :uid, 'fitnez-session', :token, :abilities, NOW(), NOW())
    ");
    $stmt->execute([
        'uid'       => $user['id'],
        'token'     => $hashedToken,
        'abilities' => json_encode([$user['role']]),
    ]);

    // COOKIE
    setcookie('fitnez_token', $plainToken, [
        'expires'  => time() + 7200,
        'path'     => '/',
        'httponly'  => true,
        'samesite' => 'Lax',
        'secure'   => false,
    ]);

    $db->prepare("UPDATE users SET last_login = NOW() WHERE id = :id")->execute(['id' => $user['id']]);
    $db->prepare("UPDATE authentications SET last_login = NOW(), failed_login_attempts = 0 WHERE user_id = :id")
       ->execute(['id' => $user['id']]);

    $db->prepare("
        INSERT INTO system_logs (user_id, action_type, table_affected, record_id, description, created_at)
        VALUES (:uid, 'LOGIN', 'users', :uid, :desc, NOW())
    ")->execute(['uid' => $user['id'], 'desc' => $user['full_name'] . ' logged in']);

    jsonResponse([
        'message' => 'Login berhasil.',
        'token'   => $plainToken,
        'user'    => formatUser($user),
    ]);
}

/**
 * POST /api/auth/logout
 */
function handleLogout(array $currentUser): void {
    $db = getDB();

    $db->prepare("
        INSERT INTO system_logs (user_id, action_type, table_affected, record_id, description, created_at)
        VALUES (:uid, 'LOGOUT', 'users', :uid, :desc, NOW())
    ")->execute(['uid' => $currentUser['id'], 'desc' => $currentUser['full_name'] . ' logged out']);

    $header = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    if (preg_match('/^Bearer\s+(.+)$/i', $header, $m)) {
        $hash = hash('sha256', $m[1]);
        $db->prepare("DELETE FROM personal_access_tokens WHERE token = :token")->execute(['token' => $hash]);
    }

    $_SESSION = [];
    session_destroy();
    setcookie('fitnez_token', '', ['expires' => time() - 3600, 'path' => '/', 'httponly' => true]);

    jsonResponse(['message' => 'Logout berhasil.']);
}

/**
 * GET /api/auth/me
 */
function handleMe(array $currentUser): void {
    jsonResponse(['user' => formatUser($currentUser)]);
}

/**
 * Format user data untuk response
 */
function formatUser(array $user): array {
    return [
        'id'               => (int) $user['id'],
        'name'             => $user['full_name'],
        'email'            => $user['email'],
        'role'             => $user['role'],
        'phone'            => $user['phone'] ?? null,
        'bio'              => $user['bio'] ?? '',
        'has_trainer_cert' => (bool) ($user['has_trainer_cert'] ?? false),
        'joined'           => isset($user['created_at']) ? date('d M Y', strtotime($user['created_at'])) : '—',
    ];
}
