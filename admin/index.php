<?php
require_once __DIR__ . '/includes/auth.php';

$so_mon     = (int)$pdo->query('SELECT COUNT(*) FROM mon_an')->fetchColumn();
$so_don     = (int)$pdo->query('SELECT COUNT(*) FROM don_hang')->fetchColumn();
$doanh_thu  = (float)$pdo->query("SELECT COALESCE(SUM(tong_tien),0) FROM don_hang WHERE trang_thai <> 'huy'")->fetchColumn();
$so_khach   = (int)$pdo->query("SELECT COUNT(*) FROM nguoi_dung WHERE vai_tro = 'user'")->fetchColumn();
$bl_cho     = (int)$pdo->query('SELECT COUNT(*) FROM binh_luan WHERE trang_thai = 0')->fetchColumn();
$dh_cho     = (int)$pdo->query("SELECT COUNT(*) FROM don_hang WHERE trang_thai = 'cho_xac_nhan'")->fetchColumn();

$recent_orders = $pdo->query("
    SELECT dh.*, nd.ho_ten
    FROM don_hang dh JOIN nguoi_dung nd ON dh.id_nguoi_dung = nd.id
    ORDER BY dh.created_at DESC LIMIT 5
")->fetchAll();

$top_foods = $pdo->query("
    SELECT m.id, m.ten, m.hinh_anh, SUM(ct.so_luong) AS da_ban
    FROM chi_tiet_don_hang ct JOIN mon_an m ON ct.id_mon_an = m.id
    GROUP BY m.id ORDER BY da_ban DESC LIMIT 5
")->fetchAll();

$page_title = 'Tổng quan';
include __DIR__ . '/includes/header.php';
?>

<h1 class="admin-page-title">Tổng quan hệ thống</h1>

<div class="stat-grid">
    <div class="stat-card"><span>Món ăn</span><strong><?= $so_mon ?></strong></div>
    <div class="stat-card"><span>Đơn hàng</span><strong><?= $so_don ?></strong></div>
    <div class="stat-card"><span>Doanh thu</span><strong style="font-size:1.35rem"><?= money($doanh_thu) ?></strong></div>
    <div class="stat-card"><span>Khách hàng</span><strong><?= $so_khach ?></strong></div>
    <div class="stat-card"><span>Bình luận chờ</span><strong><?= $bl_cho ?></strong></div>
</div>

<?php if ($dh_cho > 0): ?>
    <div class="alert alert-warning" style="margin-top:18px">
        Có <strong><?= $dh_cho ?></strong> đơn hàng mới cần xác nhận.
        <a href="donhang.php" class="btn btn-sm btn-primary" style="margin-left:auto">Xử lý ngay</a>
    </div>
<?php endif; ?>

<div class="grid grid-2" style="margin-top:20px">
    <section>
        <div class="section-head"><h2>Đơn hàng gần đây</h2><a href="donhang.php" class="btn btn-sm btn-outline">Xem tất cả</a></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Mã</th><th>Khách</th><th>Tổng tiền</th><th>Trạng thái</th><th>Ngày</th></tr></thead>
                <tbody>
                <?php foreach ($recent_orders as $o): ?>
                    <tr>
                        <td><a href="donhang.php?xem=<?= (int)$o['id'] ?>">#<?= (int)$o['id'] ?></a></td>
                        <td><?= e($o['ho_ten']) ?></td>
                        <td><?= money($o['tong_tien']) ?></td>
                        <td><?= order_status_badge($o['trang_thai']) ?></td>
                        <td><small><?= e($o['created_at']) ?></small></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>

    <section>
        <div class="section-head"><h2>Món ăn bán chạy</h2><a href="thongke.php" class="btn btn-sm btn-outline">Thống kê</a></div>
        <div class="table-wrap">
            <table>
                <thead><tr><th>Món</th><th>Đã bán</th></tr></thead>
                <tbody>
                <?php foreach ($top_foods as $f): ?>
                    <tr>
                        <td>
                            <div style="display:flex;align-items:center;gap:10px">
                                <img class="thumb" src="<?= e(asset_url($f['hinh_anh'])) ?>" alt="">
                                <a href="monan.php?edit=<?= (int)$f['id'] ?>"><?= e($f['ten']) ?></a>
                            </div>
                        </td>
                        <td><strong><?= (int)$f['da_ban'] ?></strong></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </section>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
