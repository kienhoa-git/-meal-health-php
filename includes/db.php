<?php
/** Kết nối CSDL qua PDO. Biến $pdo và $config sẽ dùng được ở file include. */

$config = require __DIR__ . '/../config.php';

$dsn = 'mysql:host=' . $config['db_host'] . ';dbname=' . $config['db_name'] . ';charset=utf8mb4';

$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $pdo = new PDO($dsn, $config['db_user'], $config['db_pass'], $options);
} catch (PDOException $e) {
    http_response_code(500);
    die('Lỗi kết nối CSDL: ' . htmlspecialchars($e->getMessage(), ENT_QUOTES, 'UTF-8'));
}

// Helper và session luôn sẵn sàng cho mọi trang (kể cả trước khi include header).
require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/auth.php';

// Toàn bộ website bị khóa cho đến khi đăng nhập (trừ đăng nhập / đăng ký).
enforce_login_gate();
