<?php
/**
 * User Controller — CRUD + Trainer Upgrade + Role Switch + Bio
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

/**
 * GET /api/users
 */
function handleGetUsers(): void {
    $db = getDB();
    $stmt = $db->query("
        SELECT u.id, u.email, u.full_name, u.phone, u.bio, u.has_trainer_cert, u.created_at, r.name as role
        FROM users u JOIN roles r ON r.id = u.role_id
        ORDER BY u.created_at DESC
    ");
    $users = $stmt->fetchAll();

    jsonResponse([
        'users' => array_map(fn($u) => [
            'id'     => (int) $u['id'],
            'name'   => $u['full_name'],
            'email'  => $u['email'],
            'role'   => $u['role'],
            'phone'  => $u['phone'],
            'bio'    => $u['bio'] ?? '',
            'joined' => date('d M Y', strtotime($u['created_at'])),
        ], $users),
    ]);
}

/**
 * GET /api/users/:id
 */
function handleGetUser(int $id): void {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT u.id, u.email, u.full_name, u.phone, u.bio, u.has_trainer_cert, u.created_at, r.name as role
        FROM users u JOIN roles r ON r.id = u.role_id
        WHERE u.id = :id
    ");
    $stmt->execute(['id' => $id]);
    $user = $stmt->fetch();

    if (!$user) jsonResponse(['error' => 'User tidak ditemukan.'], 404);

    jsonResponse(['user' => [
        'id'     => (int) $user['id'],
        'name'   => $user['full_name'],
        'email'  => $user['email'],
        'role'   => $user['role'],
        'phone'  => $user['phone'],
        'bio'    => $user['bio'] ?? '',
        'joined' => date('d M Y', strtotime($user['created_at'])),
    ]]);
}

/**
 * POST /api/users — admin tambah user
 */
function handleCreateUser(): void {
    $body = getJsonBody();
    $name  = trim($body['name'] ?? '');
    $email = trim($body['email'] ?? '');
    $role  = $body['role'] ?? 'member';

    if (!$name || !$email) {
        jsonResponse(['error' => 'Nama dan email wajib diisi.'], 400);
    }

    $db = getDB();

    $stmt = $db->prepare("SELECT id FROM users WHERE email = :email");
    $stmt->execute(['email' => $email]);
    if ($stmt->fetch()) jsonResponse(['error' => 'Email sudah terdaftar.'], 409);

    $stmt = $db->prepare("SELECT id FROM roles WHERE name = :name");
    $stmt->execute(['name' => $role]);
    $roleRow = $stmt->fetch();
    if (!$roleRow) jsonResponse(['error' => 'Role tidak valid.'], 400);

    $salt = bin2hex(random_bytes(16));
    $hash = hash('sha256', $salt . '123456');

    $stmt = $db->prepare("
        INSERT INTO users (email, password_hash, full_name, role_id, bio, created_at, is_active)
        VALUES (:email, :hash, :name, :role_id, '', NOW(), true) RETURNING id
    ");
    $stmt->execute(['email' => $email, 'hash' => $hash, 'name' => $name, 'role_id' => $roleRow['id']]);
    $userId = $stmt->fetchColumn();

    $db->prepare("
        INSERT INTO authentications (user_id, email, password_hash, salt, provider, is_active)
        VALUES (:uid, :email, :hash, :salt, 'local', true)
    ")->execute(['uid' => $userId, 'email' => $email, 'hash' => $hash, 'salt' => $salt]);

    jsonResponse(['message' => 'User berhasil ditambahkan. Password default: 123456'], 201);
}

/**
 * PUT /api/users/profile — update name, phone, bio
 */
function handleUpdateProfile(array $currentUser): void {
    $body = getJsonBody();
    $db = getDB();

    $name  = trim($body['name'] ?? $currentUser['full_name']);
    $phone = trim($body['phone'] ?? $currentUser['phone'] ?? '');
    $bio   = trim($body['bio'] ?? $currentUser['bio'] ?? '');

    $db->prepare("UPDATE users SET full_name = :name, phone = :phone, bio = :bio WHERE id = :id")
       ->execute(['name' => $name, 'phone' => $phone, 'bio' => $bio, 'id' => $currentUser['id']]);

    // Fetch updated
    $stmt = $db->prepare("
        SELECT u.id, u.email, u.full_name, u.phone, u.bio, u.has_trainer_cert, u.created_at, r.name as role
        FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = :id
    ");
    $stmt->execute(['id' => $currentUser['id']]);
    $user = $stmt->fetch();

    jsonResponse([
        'message' => 'Profil berhasil diperbarui.',
        'user'    => [
            'id'               => (int) $user['id'],
            'name'             => $user['full_name'],
            'email'            => $user['email'],
            'role'             => $user['role'],
            'phone'            => $user['phone'],
            'bio'              => $user['bio'] ?? '',
            'has_trainer_cert' => (bool) ($user['has_trainer_cert'] ?? false),
            'joined'           => date('d M Y', strtotime($user['created_at'])),
        ],
    ]);
}

/**
 * POST /api/users/upgrade-trainer — member upload sertifikat → jadi trainer
 */
function handleUpgradeTrainer(array $currentUser): void {
    $db = getDB();

    // Cek apakah file diupload
    if (empty($_FILES['certificate']) || $_FILES['certificate']['error'] !== UPLOAD_ERR_OK) {
        jsonResponse(['error' => 'File sertifikat wajib diupload.'], 400);
    }

    $file = $_FILES['certificate'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if ($ext !== 'pdf') {
        jsonResponse(['error' => 'Hanya file PDF yang diterima.'], 400);
    }

    // Simpan file
    $uploadDir = __DIR__ . '/../../storage/certificates';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
    $filename = 'cert_' . $currentUser['id'] . '_' . time() . '.pdf';
    move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $filename);

    // Update user: set has_trainer_cert = true, switch role ke trainer
    $stmt = $db->prepare("SELECT id FROM roles WHERE name = 'trainer'");
    $stmt->execute();
    $trainerRole = $stmt->fetch();
    if (!$trainerRole) jsonResponse(['error' => 'Role trainer tidak ditemukan.'], 500);

    $db->prepare("
        UPDATE users SET role_id = :rid, has_trainer_cert = true, trainer_cert_file = :file WHERE id = :id
    ")->execute(['rid' => $trainerRole['id'], 'file' => $filename, 'id' => $currentUser['id']]);

    // Fetch updated user
    $stmt = $db->prepare("
        SELECT u.id, u.email, u.full_name, u.phone, u.bio, u.has_trainer_cert, u.created_at, r.name as role
        FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = :id
    ");
    $stmt->execute(['id' => $currentUser['id']]);
    $user = $stmt->fetch();

    jsonResponse([
        'message' => 'Selamat! Anda sekarang menjadi Trainer.',
        'user'    => [
            'id'               => (int) $user['id'],
            'name'             => $user['full_name'],
            'email'            => $user['email'],
            'role'             => $user['role'],
            'phone'            => $user['phone'],
            'bio'              => $user['bio'] ?? '',
            'has_trainer_cert' => (bool) $user['has_trainer_cert'],
            'joined'           => date('d M Y', strtotime($user['created_at'])),
        ],
    ], 200);
}

/**
 * POST /api/users/switch-role — switch trainer <-> member (hanya jika has_trainer_cert)
 */
function handleSwitchRole(array $currentUser): void {
    $db = getDB();

    $stmt = $db->prepare("SELECT has_trainer_cert FROM users WHERE id = :id");
    $stmt->execute(['id' => $currentUser['id']]);
    $row = $stmt->fetch();

    if ($currentUser['role'] === 'member' && (!$row || !$row['has_trainer_cert'])) {
        jsonResponse(['error' => 'Anda belum memiliki sertifikat trainer.'], 403);
    }

    if ($currentUser['role'] === 'trainer' && !$row['has_trainer_cert']) {
        $db->prepare("UPDATE users SET has_trainer_cert = true WHERE id = :id")
           ->execute(['id' => $currentUser['id']]);
    }

    // Toggle role
    $newRoleName = ($currentUser['role'] === 'trainer') ? 'member' : 'trainer';

    $stmt = $db->prepare("SELECT id FROM roles WHERE name = :name");
    $stmt->execute(['name' => $newRoleName]);
    $newRole = $stmt->fetch();
    if (!$newRole) jsonResponse(['error' => 'Role tidak ditemukan.'], 500);

    $db->prepare("UPDATE users SET role_id = :rid WHERE id = :id")
       ->execute(['rid' => $newRole['id'], 'id' => $currentUser['id']]);

    // Fetch updated
    $stmt = $db->prepare("
        SELECT u.id, u.email, u.full_name, u.phone, u.bio, u.has_trainer_cert, u.created_at, r.name as role
        FROM users u JOIN roles r ON r.id = u.role_id WHERE u.id = :id
    ");
    $stmt->execute(['id' => $currentUser['id']]);
    $user = $stmt->fetch();

    jsonResponse([
        'message' => "Berhasil beralih ke $newRoleName.",
        'user'    => [
            'id'               => (int) $user['id'],
            'name'             => $user['full_name'],
            'email'            => $user['email'],
            'role'             => $user['role'],
            'phone'            => $user['phone'],
            'bio'              => $user['bio'] ?? '',
            'has_trainer_cert' => (bool) $user['has_trainer_cert'],
            'joined'           => date('d M Y', strtotime($user['created_at'])),
        ],
    ]);
}

/**
 * DELETE /api/users/:id
 */
function handleDeleteUser(array $currentUser, int $id): void {
    if ($currentUser['id'] == $id) {
        jsonResponse(['error' => 'Tidak bisa menghapus akun sendiri.'], 403);
    }

    $db = getDB();

    $stmt = $db->prepare("SELECT id FROM users WHERE id = :id");
    $stmt->execute(['id' => $id]);
    if (!$stmt->fetch()) jsonResponse(['error' => 'User tidak ditemukan.'], 404);

    $db->prepare("DELETE FROM authentications WHERE user_id = :id")->execute(['id' => $id]);
    $db->prepare("DELETE FROM users WHERE id = :id")->execute(['id' => $id]);

    jsonResponse(['message' => 'User berhasil dihapus.']);
}
