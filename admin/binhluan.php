<?php
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') !== '') {
    check_csrf();
    $id = (int)($_POST['id'] ?? 0);
    $action = $_POST['action'];

    if ($action === 'approve') {
        $pdo->prepare('UPDATE binh_luan SET trang_thai = 1 WHERE id = ?')->execute([$id]);
        flash('Đã duyệt bình luận.', 'success');
    } elseif ($action === 'hide') {
        $pdo->prepare('UPDATE binh_luan SET trang_thai = 0 WHERE id = ?')->execute([$id]);
        flash('Đã ẩn bình luận.', 'success');
    } elseif ($action === 'delete') {
        $pdo->prepare('DELETE FROM binh_luan WHERE id = ?')->execute([$id]);
        flash('Đã xóa bình luận.', 'warning');
    }
    redirect('binhluan.php');
}

$filter = $_GET['trang_thai'] ?? '';
$where = $filter === '0' ? 'WHERE bl.trang_thai = 0' : ($filter === '1' ? 'WHERE bl.trang_thai = 1' : '');

$rows = $pdo->query("
    SELECT bl.*, m.ten AS ten_mon, nd.ho_ten AS ten_nguoi
    FROM binh_luan bl
    JOIN mon_an m ON bl.id_mon_an = m.id
    JOIN nguoi_dung nd ON bl.id_nguoi_dung = nd.id
    $where
    ORDER BY bl.created_at DESC
")->fetchAll();

$page_title = 'Quản lý bình luận';
include __DIR__ . '/includes/header.php';
?>

<h1 class="admin-page-title">Quản lý bình luận & đánh giá</h1>

<div class="toolbar">
    <a href="binhluan.php" class="btn btn-sm <?= $filter === '' ? 'btn-primary' : 'btn-outline' ?>">Tất cả</a>
    <a href="binhluan.php?trang_thai=0" class="btn btn-sm <?= $filter === '0' ? 'btn-primary' : 'btn-outline' ?>">Chờ duyệt</a>
    <a href="binhluan.php?trang_thai=1" class="btn btn-sm <?= $filter === '1' ? 'btn-primary' : 'btn-outline' ?>">Đã duyệt</a>
</div>

<div class="table-wrap">
    <table>
        <thead><tr><th>ID</th><th>Món ăn</th><th>Người gửi</th><th>Đánh giá</th><th>Nội dung</th><th>Trạng thái</th><th>Thao tác</th></tr></thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td>#<?= (int)$r['id'] ?></td>
                <td><a href="../monan.php?id=<?= (int)$r['id_mon_an'] ?>" target="_blank"><?= e($r['ten_mon']) ?></a></td>
                <td><?= e($r['ten_nguoi']) ?><br><small style="color:var(--muted)"><?= e($r['created_at']) ?></small></td>
                <td><span class="stars"><?= str_repeat('★', (int)$r['danh_gia']) ?></span></td>
                <td style="max-width:340px"><?= nl2br(e($r['noi_dung'])) ?></td>
                <td><span class="pill" style="<?= (int)$r['trang_thai'] ? '' : 'background:#fff3cd;color:#856404' ?>"><?= (int)$r['trang_thai'] ? 'Đã duyệt' : 'Chờ duyệt' ?></span></td>
                <td>
                    <div class="action-links" style="flex-direction:column;gap:4px">
                        <?php if ((int)$r['trang_thai'] === 0): ?>
                            <form method="post" class="inline-form">
                                <?= csrf_field() ?><input type="hidden" name="action" value="approve"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <button type="submit" style="color:var(--green)">Duyệt</button>
                            </form>
                        <?php else: ?>
                            <form method="post" class="inline-form">
                                <?= csrf_field() ?><input type="hidden" name="action" value="hide"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                                <button type="submit" style="color:var(--warning)">Ẩn</button>
                            </form>
                        <?php endif; ?>
                        <form method="post" class="inline-form">
                            <?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int)$r['id'] ?>">
                            <button type="submit" data-confirm="Xóa bình luận này?">Xóa</button>
                        </form>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        <?php if (!$rows): ?>
            <tr><td colspan="7" style="text-align:center;color:var(--muted)">Chưa có bình luận nào.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
