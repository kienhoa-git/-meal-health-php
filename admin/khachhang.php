<?php
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'toggle') {
    check_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $stmt = $pdo->prepare('SELECT trang_thai FROM nguoi_dung WHERE id = ? AND vai_tro = user');
    $stmt->execute([$id]);
    $u = $stmt->fetch();
    if ($u) {
        $new = (int)$u['trang_thai'] === 1 ? 0 : 1;
        $pdo->prepare('UPDATE nguoi_dung SET trang_thai = ? WHERE id = ?')->execute([$new, $id]);
        flash($new ? 'Đã mở khóa tài khoản.' : 'Đã khóa tài khoản.', 'success');
    }
    redirect('khachhang.php');
}

$keyword = trim($_GET['q'] ?? '');
if ($keyword !== '') {
    $stmt = $pdo->prepare("SELECT * FROM nguoi_dung WHERE vai_tro = 'user' AND (ho_ten LIKE ? OR email LIKE ?) ORDER BY id DESC");
    $stmt->execute(['%' . $keyword . '%', '%' . $keyword . '%']);
} else {
    $stmt = $pdo->query("SELECT * FROM nguoi_dung WHERE vai_tro = 'user' ORDER BY id DESC");
}
$users = $stmt->fetchAll();

$stmt = $pdo->query("SELECT id_nguoi_dung, COUNT(*) AS so_don, COALESCE(SUM(tong_tien),0) AS tong_tien FROM don_hang GROUP BY id_nguoi_dung");
$order_stats = [];
foreach ($stmt->fetchAll() as $row) {
    $order_stats[$row['id_nguoi_dung']] = $row;
}

$page_title = 'Quản lý khách hàng';
include __DIR__ . '/includes/header.php';
?>

<h1 class="admin-page-title">Quản lý khách hàng</h1>

<div class="toolbar">
    <form method="get" action="khachhang.php" class="form-inline">
        <div class="form-group">
            <input type="text" name="q" value="<?= e($keyword) ?>" placeholder="Tìm tên hoặc email...">
        </div>
        <button type="submit" class="btn btn-sm btn-primary">Tìm</button>
        <a href="khachhang.php" class="btn btn-sm btn-ghost">Xóa lọc</a>
    </form>
</div>

<div class="table-wrap">
    <table>
        <thead><tr><th>ID</th><th>Khách hàng</th><th>Liên hệ</th><th>Mục tiêu</th><th>Đơn hàng</th><th>Tổng chi</th><th>Trạng thái</th><th></th></tr></thead>
        <tbody>
        <?php foreach ($users as $u): ?>
            <tr>
                <td>#<?= (int)$u['id'] ?></td>
                <td><strong><?= e($u['ho_ten']) ?></strong><br><small style="color:var(--muted)">BMI: <?= e(bmi((float)$u['chieu_cao'], (float)$u['can_nang'])) ?></small></td>
                <td><?= e($u['email']) ?><br><small style="color:var(--muted)"><?= e($u['dien_thoai'] ?: '—') ?></small></td>
                <td><span class="pill"><?= e(['giam_can'=>'Giảm cân','tang_can'=>'Tăng cân','duy_tri'=>'Duy trì'][$u['muc_tieu']] ?? $u['muc_tieu']) ?></span></td>
                <td><?= (int)($order_stats[$u['id']]['so_don'] ?? 0) ?></td>
                <td><?= money($order_stats[$u['id']]['tong_tien'] ?? 0) ?></td>
                <td><span class="pill" style="<?= (int)$u['trang_thai'] ? '' : 'background:#fdeaea;color:#922' ?>"><?= (int)$u['trang_thai'] ? 'Hoạt động' : 'Đã khóa' ?></span></td>
                <td>
                    <form method="post" action="khachhang.php" class="inline-form">
                        <?= csrf_field() ?>
                        <input type="hidden" name="action" value="toggle">
                        <input type="hidden" name="id" value="<?= (int)$u['id'] ?>">
                        <button type="submit" data-confirm="<?= (int)$u['trang_thai'] ? 'Khóa tài khoản này?' : 'Mở khóa tài khoản này?' ?>">
                            <?= (int)$u['trang_thai'] ? 'Khóa' : 'Mở khóa' ?>
                        </button>
                    </form>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$users): ?>
            <tr><td colspan="8" style="text-align:center;color:var(--muted)">Không tìm thấy khách hàng.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
