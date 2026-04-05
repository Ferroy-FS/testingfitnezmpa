<?php
/**
 * App Configuration
 * Load .env, start session, set CORS headers
 */

// Load .env file
function loadEnv(string $path): void {
    if (!file_exists($path)) return;
    $lines = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        [$key, $val] = explode('=', $line, 2);
        $key = trim($key);
        $val = trim($val);
        putenv("$key=$val");
        $_ENV[$key] = $val;
    }
}

// CORS headers — izinkan frontend akses API
function setCorsHeaders(): void {
    $origin = getenv('CORS_ORIGIN') ?: 'http://localhost:5173';
    header("Access-Control-Allow-Origin: $origin");
    header("Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS");
    header("Access-Control-Allow-Headers: Content-Type, Authorization");
    header("Access-Control-Allow-Credentials: true");

    // Preflight request
    if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
        http_response_code(204);
        exit;
    }
}

// Start session dengan cookie config
function initSession(): void {
    if (session_status() === PHP_SESSION_ACTIVE) return;

    session_set_cookie_params([
        'lifetime' => 7200,
        'path'     => '/',
        'domain'   => '',
        'secure'   => false,
        'httponly'  => true,
        'samesite'  => 'Lax',
    ]);
    session_name('fitnez_session');
    session_start();
}

// JSON response helper
function jsonResponse(array $data, int $code = 200): void {
    http_response_code($code);
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Get JSON body dari request
function getJsonBody(): array {
    $raw = file_get_contents('php://input');
    return json_decode($raw, true) ?: [];
}

// Generate random token
function generateToken(int $length = 64): string {
    return bin2hex(random_bytes($length / 2));
}
