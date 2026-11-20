<?php
require_once __DIR__ . '/auth.php';
require_once __DIR__ . '/functions.php';
?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? e($page_title) . ' | ' : '' ?>Ăn Lành Mạnh</title>
    <link rel="stylesheet" href="css/style.css">
</head>
<body>
<header class="site-header">
    <div class="header-inner">
        <a class="brand" href="index.php">
            <img src="images/logo.svg" alt="Logo">
            <span>Ăn Lành Mạnh</span>
        </a>

        <nav class="main-nav" aria-label="Menu chính">
            <a href="index.php">Trang chủ</a>
            <a href="danhmuc.php">Thực đơn</a>
            <a href="timkiem.php">Tìm kiếm</a>
            <a href="goiy.php">Gợi ý bữa ăn</a>
        </nav>

        <div class="header-actions">
            <a class="cart-link" href="giohang.php" aria-label="Giỏ hàng">
                🛒 <span class="cart-count"><?= cart_count() ?></span>
            </a>
            <?php if (is_logged_in()): ?>
                <a href="taikhoan.php" class="btn btn-sm btn-outline">Xin chào, <?= e($_SESSION['ho_ten'] ?? 'bạn') ?></a>
                <?php if (is_admin()): ?>
                    <a href="admin/index.php" class="btn btn-sm btn-primary">Admin</a>
                <?php endif; ?>
                <a href="logout.php" class="btn btn-sm btn-ghost">Đăng xuất</a>
            <?php else: ?>
                <a href="dangnhap.php" class="btn btn-sm btn-outline">Đăng nhập</a>
                <a href="dangky.php" class="btn btn-sm btn-primary">Đăng ký</a>
            <?php endif; ?>
        </div>
    </div>
</header>

<main class="container">
    <?= flash_html() ?>
