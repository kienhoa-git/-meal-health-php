<?php require_once __DIR__ . '/auth.php'; ?>
<!DOCTYPE html>
<html lang="vi">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= isset($page_title) ? e($page_title) . ' | ' : '' ?>Quản trị - Ăn Lành Mạnh</title>
    <link rel="stylesheet" href="css/admin.css">
</head>
<body>
<div class="admin-layout">
    <aside class="sidebar">
        <a class="sidebar-brand" href="index.php">
            <img src="../images/logo.svg" alt="">
            <span>Ăn Lành Mạnh<br><small>Admin Panel</small></span>
        </a>
        <nav class="sidebar-nav">
            <a href="index.php">📊 Tổng quan</a>
            <a href="monan.php">🍽️ Quản lý món ăn</a>
            <a href="danhmuc.php">🗂️ Danh mục</a>
            <a href="nguoncongthuc.php">📚 Nguồn công thức</a>
            <a href="donhang.php">📦 Đơn hàng</a>
            <a href="khachhang.php">👥 Khách hàng</a>
            <a href="binhluan.php">💬 Bình luận</a>
            <a href="thongke.php">📈 Thống kê</a>
        </nav>
        <div class="sidebar-bottom">
            <a href="../index.php" target="_blank">🌐 Xem website</a>
            <a href="logout.php">🚪 Đăng xuất</a>
        </div>
    </aside>

    <div class="admin-main">
        <header class="admin-topbar">
            <strong><?= isset($page_title) ? e($page_title) : 'Quản trị' ?></strong>
            <span>Xin chào, <b><?= e($_SESSION['ho_ten'] ?? 'Admin') ?></b></span>
        </header>
        <div class="admin-content">
            <?= flash_html() ?>
