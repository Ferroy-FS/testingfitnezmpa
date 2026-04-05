<?php
/**
 * FAQ Controller
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/app.php';

function handleGetFaq(): void {
    $db = getDB();
    $stmt = $db->query("
        SELECT id, question, answer, category
        FROM faq WHERE is_active = true ORDER BY display_order
    ");
    jsonResponse(['faq' => $stmt->fetchAll()]);
}
