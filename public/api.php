<?php
/**
 * API Router — Vanilla PHP
 * Entry point: public/api.php
 */

// Load config
require_once __DIR__ . '/../src/config/app.php';
require_once __DIR__ . '/../src/config/database.php';

// Load .env
loadEnv(__DIR__ . '/../.env');

// Init session & CORS
initSession();
setCorsHeaders();

// Load middleware
require_once __DIR__ . '/../src/middleware/auth.php';

// Load controllers
require_once __DIR__ . '/../src/controllers/auth.php';
require_once __DIR__ . '/../src/controllers/users.php';
require_once __DIR__ . '/../src/controllers/schedule.php';
require_once __DIR__ . '/../src/controllers/attendance.php';
require_once __DIR__ . '/../src/controllers/logs.php';
require_once __DIR__ . '/../src/controllers/faq.php';
require_once __DIR__ . '/../src/controllers/notifications.php';

// Parse route
$method = $_SERVER['REQUEST_METHOD'];
$uri    = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// Hapus prefix /api
$path = preg_replace('#^/api#', '', $uri);
$path = rtrim($path, '/');

//  PUBLIC ROUTES (tanpa auth)

if ($method === 'POST' && $path === '/auth/login') {
    handleLogin();
}

if ($method === 'POST' && $path === '/auth/register') {
    handleRegister();
}

if ($method === 'GET' && $path === '/faq') {
    handleGetFaq();
}

//  PROTECTED ROUTES (perlu login)

$user = authMiddleware();

// --- Auth ---
if ($method === 'GET' && $path === '/auth/me') {
    handleMe($user);
}
if ($method === 'POST' && $path === '/auth/logout') {
    handleLogout($user);
}

// --- Profile ---
if ($method === 'PUT' && $path === '/users/profile') {
    handleUpdateProfile($user);
}

// --- Trainer Upgrade (upload sertifikat) ---
if ($method === 'POST' && $path === '/users/upgrade-trainer') {
    handleUpgradeTrainer($user);
}

// --- Switch Role (trainer <-> member) ---
if ($method === 'POST' && $path === '/users/switch-role') {
    handleSwitchRole($user);
}

// --- Users CRUD ---
if ($method === 'GET' && $path === '/users') {
    roleMiddleware($user, ['admin', 'trainer']);
    handleGetUsers();
}
if ($method === 'POST' && $path === '/users') {
    roleMiddleware($user, ['admin']);
    handleCreateUser();
}
if ($method === 'GET' && preg_match('#^/users/(\d+)$#', $path, $m)) {
    roleMiddleware($user, ['admin', 'trainer']);
    handleGetUser((int)$m[1]);
}
if ($method === 'DELETE' && preg_match('#^/users/(\d+)$#', $path, $m)) {
    roleMiddleware($user, ['admin']);
    handleDeleteUser($user, (int)$m[1]);
}

// --- Schedule CRUD (gym classes) ---
if ($method === 'GET' && $path === '/schedule') {
    handleGetSchedules();
}
if ($method === 'POST' && $path === '/schedule') {
    roleMiddleware($user, ['admin', 'trainer']);
    handleCreateSchedule();
}
if ($method === 'PUT' && preg_match('#^/schedule/(\d+)$#', $path, $m)) {
    roleMiddleware($user, ['admin', 'trainer']);
    handleUpdateSchedule((int)$m[1]);
}
if ($method === 'DELETE' && preg_match('#^/schedule/(\d+)$#', $path, $m)) {
    roleMiddleware($user, ['admin']);
    handleDeleteSchedule((int)$m[1]);
}

// --- Personal Schedule (member) ---
if ($method === 'GET' && $path === '/schedule/my') {
    handleGetMySchedules($user);
}
if ($method === 'POST' && $path === '/schedule/my') {
    handleCreateMySchedule($user);
}
if ($method === 'DELETE' && preg_match('#^/schedule/my/(\d+)$#', $path, $m)) {
    handleDeleteMySchedule($user, (int)$m[1]);
}

// --- Attendance ---
if ($method === 'GET' && $path === '/attendance') {
    handleGetAttendance($user);
}
if ($method === 'POST' && $path === '/attendance') {
    handleCheckIn($user);
}

// --- Auth Logs ---
if ($method === 'GET' && $path === '/logs') {
    roleMiddleware($user, ['admin']);
    handleGetLogs();
}
if ($method === 'DELETE' && $path === '/logs') {
    roleMiddleware($user, ['admin']);
    handleClearLogs();
}

// --- Real-time: Long Polling ---
if ($method === 'GET' && $path === '/notifications/poll') {
    handleLongPoll();
}

// --- 404 ---
jsonResponse(['error' => 'Route tidak ditemukan.'], 404);
