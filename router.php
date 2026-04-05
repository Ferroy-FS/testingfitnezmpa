<?php
/**
 * Router untuk PHP built-in server (php -S)
 * Jalankan: php -S localhost:8000 router.php
 *
 * Route /api/* → forward ke api.php
 * Route lainnya → file statis di public/
 */

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

// API routes → forward ke api.php
if (str_starts_with($uri, '/api')) {
    require __DIR__ . '/public/api.php';
    return true;
}

// Cari file statis di public/
$file = __DIR__ . '/public' . $uri;

if (is_file($file)) {
    // Set content type
    $ext = pathinfo($file, PATHINFO_EXTENSION);
    $types = [
        'html' => 'text/html',
        'css'  => 'text/css',
        'js'   => 'application/javascript',
        'json' => 'application/json',
        'png'  => 'image/png',
        'jpg'  => 'image/jpeg',
        'svg'  => 'image/svg+xml',
        'ico'  => 'image/x-icon',
    ];
    if (isset($types[$ext])) {
        header('Content-Type: ' . $types[$ext]);
    }
    readfile($file);
    return true;
}

// Default: index.html
if (is_file(__DIR__ . '/public/index.html')) {
    readfile(__DIR__ . '/public/index.html');
    return true;
}

http_response_code(404);
echo '404 Not Found';
return true;
