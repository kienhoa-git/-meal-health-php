<?php
require_once __DIR__ . '/includes/auth.php';

$statuses = ['cho_xac_nhan', 'dang_chuan_bi', 'dang_giao', 'hoan_tat', 'huy'];

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'status') {
    check_csrf();
    $id     = (int)($_POST['id'] ?? 0);
    $status = $_POST['trang_thai'] ?? '';
    if (in_array($status, $statuses, true)) {
        $pdo->prepare('UPDATE don_hang SET trang_thai = ? WHERE id = ?')->execute([$status, $id]);
        flash('Cập nhật trạng thái đơn hàng thành công.', 'success');
    }
    redirect('donhang.php' . (isset($_GET['xem']) ? '?xem=' . (int)$_GET['xem'] : ''));
}

$filter = $_GET['trang_thai'] ?? '';
if ($filter !== '' && !in_array($filter, $statuses, true)) $filter = '';

if ($filter !== '') {
    $stmt = $pdo->prepare("
        SELECT dh.*, nd.ho_ten AS ten_khach
        FROM don_hang dh JOIN nguoi_dung nd ON dh.id_nguoi_dung = nd.id
        WHERE dh.trang_thai = ? ORDER BY dh.created_at DESC
    ");
    $stmt->execute([$filter]);
} else {
    $stmt = $pdo->query("
        SELECT dh.*, nd.ho_ten AS ten_khach
        FROM don_hang dh JOIN nguoi_dung nd ON dh.id_nguoi_dung = nd.id
        ORDER BY dh.created_at DESC
    ");
}
$orders = $stmt->fetchAll();

$detail = null;
$detail_items = [];
if (($_GET['xem'] ?? '') !== '') {
    $id = (int)$_GET['xem'];
    $stmt = $pdo->prepare("
        SELECT dh.*, nd.ho_ten AS ten_khach, nd.email
        FROM don_hang dh JOIN nguoi_dung nd ON dh.id_nguoi_dung = nd.id
        WHERE dh.id = ?
    ");
    $stmt->execute([$id]);
    $detail = $stmt->fetch();
    if ($detail) {
        $stmt = $pdo->prepare('SELECT * FROM chi_tiet_don_hang WHERE id_don_hang = ?');
        $stmt->execute([$id]);
        $detail_items = $stmt->fetchAll();
    }
}

$page_title = 'Quản lý đơn hàng';
include __DIR__ . '/includes/header.php';
?>

<h1 class="admin-page-title">Quản lý đơn hàng</h1>

<?php if ($detail): ?>
<section>
    <div class="section-head">
        <h2>Chi tiết đơn #<?= (int)$detail['id'] ?> <?= order_status_badge($detail['trang_thai']) ?></h2>
        <a href="donhang.php" class="btn btn-sm btn-ghost">Đóng</a>
    </div>
    <div class="grid grid-2">
        <div class="panel">
            <h3 style="margin-top:0">Thông tin nhận bữa</h3>
            <p><strong>Khách:</strong> <?= e($detail['ten_khach']) ?> (<?= e($detail['email']) ?>)</p>
            <p><strong>Điện thoại:</strong> <?= e($detail['dien_thoai']) ?></p>
            <p><strong>Địa chỉ:</strong> <?= nl2br(e($detail['dia_chi'])) ?></p>
            <p><strong>Ghi chú:</strong> <?= e($detail['ghi_chu'] ?: '—') ?></p>
            <p><strong>Ngày đặt:</strong> <?= e($detail['created_at']) ?></p>

            <form method="post" action="donhang.php?xem=<?= (int)$detail['id'] ?>" class="form-row" style="align-items:end">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="status">
                <input type="hidden" name="id" value="<?= (int)$detail['id'] ?>">
                <div class="form-group" style="margin:0">
                    <label for="trang_thai">Cập nhật trạng thái</label>
                    <select name="trang_thai" id="trang_thai">
                        <?php foreach ($statuses as $s): ?>
                            <option value="<?= $s ?>" <?= $detail['trang_thai'] === $s ? 'selected' : '' ?>><?= e(order_status_label($s)) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary">Lưu trạng thái</button>
            </form>
        </div>

        <div class="table-wrap">
            <table>
                <thead><tr><th>Món</th><th>SL</th><th>Giá</th><th>Thành tiền</th></tr></thead>
                <tbody>
                <?php foreach ($detail_items as $it): ?>
                    <tr>
                        <td><?= e($it['ten_mon_an']) ?></td>
                        <td>x<?= (int)$it['so_luong'] ?></td>
                        <td><?= money($it['gia']) ?></td>
                        <td><strong><?= money($it['gia'] * $it['so_luong']) ?></strong></td>
                    </tr>
                <?php endforeach; ?>
                <tr>
                    <td colspan="3"><strong>Tổng calo: <?= (int)$detail['tong_calo'] ?></strong></td>
                    <td><strong style="color:var(--orange)"><?= money($detail['tong_tien']) ?></strong></td>
                </tr>
                </tbody>
            </table>
        </div>
    </div>
</section>
<?php endif; ?>

<section>
    <div class="toolbar">
        <a href="donhang.php" class="btn btn-sm <?= !$filter ? 'btn-primary' : 'btn-outline' ?>">Tất cả</a>
        <?php foreach ($statuses as $s): ?>
            <a href="donhang.php?trang_thai=<?= $s ?>" class="btn btn-sm <?= $filter === $s ? 'btn-primary' : 'btn-outline' ?>">
                <?= e(order_status_label($s)) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <div class="table-wrap">
        <table>
            <thead><tr><th>Mã</th><th>Khách hàng</th><th>Ngày đặt</th><th>Tổng tiền</th><th>Calo</th><th>Trạng thái</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($orders as $o): ?>
                <tr>
                    <td>#<?= (int)$o['id'] ?></td>
                    <td><strong><?= e($o['ten_khach']) ?></strong><br><small style="color:var(--muted)"><?= e($o['dien_thoai']) ?></small></td>
                    <td><small><?= e($o['created_at']) ?></small></td>
                    <td><?= money($o['tong_tien']) ?></td>
                    <td><?= (int)$o['tong_calo'] ?></td>
                    <td><?= order_status_badge($o['trang_thai']) ?></td>
                    <td><a href="donhang.php?xem=<?= (int)$o['id'] ?>" class="btn btn-sm btn-outline">Xem</a></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$orders): ?>
                <tr><td colspan="7" style="text-align:center;color:var(--muted)">Chưa có đơn hàng nào.</td></tr>
            <?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
