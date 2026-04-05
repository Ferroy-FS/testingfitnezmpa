<?php
/**
 * Attendance Controller — Check-in kehadiran
 * Member bisa MEMILIH kelas mana yang ingin dihadiri
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

function handleGetAttendance(array $currentUser): void {
    $db = getDB();

    $sql = "
        SELECT a.id, u.full_name as member, 
               COALESCE(s.name, a.attendance_type) as cls,
               to_char(a.check_in_time, 'DD Mon YYYY HH24:MI') as date,
               a.attendance_type as status
        FROM attendance a
        JOIN users u ON u.id = a.user_id
        LEFT JOIN schedules s ON s.id = a.schedule_id
    ";

    if ($currentUser['role'] === 'member') {
        $sql .= " WHERE a.user_id = :uid";
        $sql .= " ORDER BY a.check_in_time DESC";
        $stmt = $db->prepare($sql);
        $stmt->execute(['uid' => $currentUser['id']]);
    } else {
        $sql .= " ORDER BY a.check_in_time DESC";
        $stmt = $db->query($sql);
    }

    jsonResponse(['attendance' => $stmt->fetchAll()]);
}

/**
 * POST /api/attendance — check-in ke kelas tertentu
 * Body: { schedule_id: int }
 */
function handleCheckIn(array $currentUser): void {
    $body = getJsonBody();
    $scheduleId = (int) ($body['schedule_id'] ?? 0);
    $db = getDB();

    if (!$scheduleId) {
        jsonResponse(['error' => 'Silakan pilih kelas terlebih dahulu.'], 400);
    }

    // Cek jadwal valid
    $stmt = $db->prepare("SELECT id, name, day FROM schedules WHERE id = :id");
    $stmt->execute(['id' => $scheduleId]);
    $schedule = $stmt->fetch();

    if (!$schedule) {
        jsonResponse(['error' => 'Kelas tidak ditemukan.'], 404);
    }

    // Cek duplikat hari ini untuk kelas yang sama
    $stmt = $db->prepare("
        SELECT id FROM attendance
        WHERE user_id = :uid AND schedule_id = :sid AND check_in_time::date = CURRENT_DATE
    ");
    $stmt->execute(['uid' => $currentUser['id'], 'sid' => $scheduleId]);
    if ($stmt->fetch()) {
        jsonResponse(['error' => 'Anda sudah check-in untuk kelas ini hari ini.'], 409);
    }

    $stmt = $db->prepare("
        INSERT INTO attendance (user_id, check_in_time, attendance_type, schedule_id)
        VALUES (:uid, NOW(), 'gym_class', :sid) RETURNING id
    ");
    $stmt->execute(['uid' => $currentUser['id'], 'sid' => $scheduleId]);

    jsonResponse([
        'message' => "Check-in berhasil untuk kelas {$schedule['name']}.",
    ], 201);
}
