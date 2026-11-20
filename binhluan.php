<?php
require __DIR__ . '/includes/db.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    redirect('danhmuc.php');
}
check_csrf();

$id_mon_an = (int)($_POST['id_mon_an'] ?? 0);
$danh_gia  = max(1, min(5, (int)($_POST['danh_gia'] ?? 5)));
$noi_dung  = trim($_POST['noi_dung'] ?? '');

if ($id_mon_an <= 0 || $noi_dung === '') {
    flash('Nội dung bình luận không được để trống.', 'danger');
    redirect('monan.php?id=' . $id_mon_an);
}

$stmt = $pdo->prepare('INSERT INTO binh_luan (id_mon_an, id_nguoi_dung, danh_gia, noi_dung, trang_thai) VALUES (?, ?, ?, ?, 0)');
$stmt->execute([$id_mon_an, $_SESSION['user_id'], $danh_gia, $noi_dung]);

flash('Cảm ơn bạn! Bình luận đã gửi và đang chờ quản trị viên duyệt.', 'success');
redirect('monan.php?id=' . $id_mon_an);
