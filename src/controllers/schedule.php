<?php
/**
 * Schedule Controller — CRUD jadwal kelas
 * + Member bisa menambah jadwal latihan pribadi
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

function handleGetSchedules(): void {
    $db = getDB();
    $stmt = $db->query("
        SELECT s.id, s.name, s.day, s.time, s.level, s.slots, 
               u.full_name as trainer, s.is_personal, s.owner_id
        FROM schedules s 
        JOIN users u ON u.id = s.trainer_id 
        WHERE s.is_personal = false
        ORDER BY s.id
    ");
    jsonResponse(['schedule' => $stmt->fetchAll()]);
}

/**
 * GET /api/schedule/my — jadwal latihan pribadi member
 */
function handleGetMySchedules(array $currentUser): void {
    $db = getDB();
    $stmt = $db->prepare("
        SELECT s.id, s.name, s.day, s.time, s.level, s.notes,
               to_char(s.created_at, 'DD Mon YYYY') as created
        FROM schedules s
        WHERE s.is_personal = true AND s.owner_id = :uid
        ORDER BY s.id DESC
    ");
    $stmt->execute(['uid' => $currentUser['id']]);
    jsonResponse(['schedule' => $stmt->fetchAll()]);
}

/**
 * POST /api/schedule/my — member tambah jadwal pribadi
 */
function handleCreateMySchedule(array $currentUser): void {
    $body = getJsonBody();
    $db = getDB();

    $name  = trim($body['name'] ?? '');
    $day   = trim($body['day'] ?? '');
    $time  = trim($body['time'] ?? '');
    $level = trim($body['level'] ?? 'Personal');
    $notes = trim($body['notes'] ?? '');

    if (!$name || !$day || !$time) {
        jsonResponse(['error' => 'Nama, hari, dan waktu wajib diisi.'], 400);
    }

    $stmt = $db->prepare("
        INSERT INTO schedules (name, day, time, trainer_id, level, slots, is_personal, owner_id, notes, created_at, updated_at)
        VALUES (:name, :day, :time, :tid, :level, 1, true, :oid, :notes, NOW(), NOW()) RETURNING id
    ");
    $stmt->execute([
        'name'  => $name,
        'day'   => $day,
        'time'  => $time,
        'tid'   => $currentUser['id'],
        'level' => $level,
        'oid'   => $currentUser['id'],
        'notes' => $notes,
    ]);

    jsonResponse(['message' => 'Jadwal latihan berhasil ditambahkan.'], 201);
}

/**
 * DELETE /api/schedule/my/:id — member hapus jadwal pribadi sendiri
 */
function handleDeleteMySchedule(array $currentUser, int $id): void {
    $db = getDB();
    $stmt = $db->prepare("SELECT id FROM schedules WHERE id = :id AND owner_id = :uid AND is_personal = true");
    $stmt->execute(['id' => $id, 'uid' => $currentUser['id']]);
    if (!$stmt->fetch()) {
        jsonResponse(['error' => 'Jadwal tidak ditemukan.'], 404);
    }
    $db->prepare("DELETE FROM schedules WHERE id = :id")->execute(['id' => $id]);
    jsonResponse(['message' => 'Jadwal berhasil dihapus.']);
}

function handleCreateSchedule(): void {
    $body = getJsonBody();
    $db = getDB();

    $stmt = $db->prepare("
        INSERT INTO schedules (name, day, time, trainer_id, level, slots, is_personal, created_at, updated_at)
        VALUES (:name, :day, :time, :tid, :level, :slots, false, NOW(), NOW()) RETURNING id
    ");
    $stmt->execute([
        'name'  => $body['name'] ?? '',
        'day'   => $body['day'] ?? '',
        'time'  => $body['time'] ?? '',
        'tid'   => $body['trainer_id'] ?? 0,
        'level' => $body['level'] ?? 'All Level',
        'slots' => $body['slots'] ?? 20,
    ]);

    jsonResponse(['message' => 'Jadwal berhasil ditambahkan.'], 201);
}

function handleUpdateSchedule(int $id): void {
    $body = getJsonBody();
    $db = getDB();

    $fields = []; $params = ['id' => $id];
    foreach (['name','day','time','trainer_id','level','slots'] as $f) {
        if (isset($body[$f])) { $fields[] = "$f = :$f"; $params[$f] = $body[$f]; }
    }
    if (empty($fields)) jsonResponse(['error' => 'Tidak ada data yang diupdate.'], 400);

    $fields[] = "updated_at = NOW()";
    $db->prepare("UPDATE schedules SET " . implode(', ', $fields) . " WHERE id = :id")->execute($params);

    jsonResponse(['message' => 'Jadwal berhasil diperbarui.']);
}

function handleDeleteSchedule(int $id): void {
    $db = getDB();
    $db->prepare("DELETE FROM schedules WHERE id = :id")->execute(['id' => $id]);
    jsonResponse(['message' => 'Jadwal berhasil dihapus.']);
}
