<?php
/**
 * System Logs Controller — Auth logs (login/logout)
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

function handleGetLogs(): void {
    $db = getDB();
    $stmt = $db->query("
        SELECT sl.id, u.full_name as who, LOWER(sl.action_type) as action,
               r.name as role, to_char(sl.created_at, 'DD Mon YYYY HH24:MI') as ts
        FROM system_logs sl
        JOIN users u ON u.id = sl.user_id
        JOIN roles r ON r.id = u.role_id
        WHERE sl.action_type IN ('LOGIN', 'LOGOUT')
        ORDER BY sl.created_at DESC LIMIT 100
    ");
    jsonResponse(['logs' => $stmt->fetchAll()]);
}

function handleClearLogs(): void {
    $db = getDB();
    $db->exec("DELETE FROM system_logs WHERE action_type IN ('LOGIN', 'LOGOUT')");
    jsonResponse(['message' => 'Auth logs berhasil dihapus.']);
}
