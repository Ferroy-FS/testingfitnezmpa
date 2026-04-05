<?php
/**
 * Teknik komunikasi real-time: Long Polling
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

/**
 * GET /api/notifications/poll — Long Polling
 * Client mengirim last_att_id dan last_log_id,
 * server menahan koneksi sampai ada data baru atau timeout.
 */
function handleLongPoll(): void {
    $lastAttId = (int) ($_GET['last_att_id'] ?? 0);
    $lastLogId = (int) ($_GET['last_log_id'] ?? 0);
    $timeout = 30;
    $start = time();

    $db = getDB();

    while (true) {
        $stmt = $db->prepare("
            SELECT a.id, u.full_name as member, COALESCE(s.name, a.attendance_type) as cls,
                   to_char(a.check_in_time, 'DD Mon YYYY HH24:MI') as date, a.attendance_type as status
            FROM attendance a JOIN users u ON u.id = a.user_id
            LEFT JOIN schedules s ON s.id = a.schedule_id
            WHERE a.id > :last ORDER BY a.id
        ");
        $stmt->execute(['last' => $lastAttId]);
        $newAtt = $stmt->fetchAll();

        $stmt = $db->prepare("
            SELECT sl.id, u.full_name as who, LOWER(sl.action_type) as action,
                   r.name as role, to_char(sl.created_at, 'DD Mon YYYY HH24:MI') as ts
            FROM system_logs sl JOIN users u ON u.id = sl.user_id
            JOIN roles r ON r.id = u.role_id
            WHERE sl.id > :last AND sl.action_type IN ('LOGIN','LOGOUT') ORDER BY sl.id
        ");
        $stmt->execute(['last' => $lastLogId]);
        $newLogs = $stmt->fetchAll();

        if (!empty($newAtt) || !empty($newLogs)) {
            jsonResponse(['attendance' => $newAtt, 'logs' => $newLogs]);
        }

        if ((time() - $start) >= $timeout) {
            jsonResponse(['attendance' => [], 'logs' => []]);
        }

        usleep(2000000);
    }
}
