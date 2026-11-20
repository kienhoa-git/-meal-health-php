<?php
require_once __DIR__ . '/includes/auth.php';

$monthly = $pdo->query("
    SELECT DATE_FORMAT(created_at, '%Y-%m') AS thang,
           COUNT(*) AS so_don,
           SUM(tong_tien) AS doanh_thu
    FROM don_hang
    WHERE trang_thai <> 'huy'
    GROUP BY thang
    ORDER BY thang DESC
    LIMIT 6
")->fetchAll();
$monthly = array_reverse($monthly);

$status_rows = $pdo->query('SELECT trang_thai, COUNT(*) AS so_luong FROM don_hang GROUP BY trang_thai')->fetchAll();
$status_counts = [];
foreach ($status_rows as $r) $status_counts[$r['trang_thai']] = (int)$r['so_luong'];

$top_foods = $pdo->query("
    SELECT m.ten, m.hinh_anh, SUM(ct.so_luong) AS da_ban, SUM(ct.so_luong * ct.gia) AS doanh_thu
    FROM chi_tiet_don_hang ct JOIN mon_an m ON ct.id_mon_an = m.id
    GROUP BY m.id ORDER BY da_ban DESC LIMIT 8
")->fetchAll();

$tong_doanh_thu = (float)$pdo->query("SELECT COALESCE(SUM(tong_tien),0) FROM don_hang WHERE trang_thai <> 'huy'")->fetchColumn();
$tong_calo = (int)$pdo->query('SELECT COALESCE(SUM(tong_calo),0) FROM don_hang')->fetchColumn();
$max_doanh_thu = 0;
foreach ($monthly as $m) $max_doanh_thu = max($max_doanh_thu, (float)$m['doanh_thu']);

$page_title = 'Thống kê';
include __DIR__ . '/includes/header.php';
?>

<h1 class="admin-page-title">Thống kê & báo cáo</h1>

<div class="stat-grid">
    <div class="stat-card"><span>Tổng doanh thu</span><strong style="font-size:1.3rem"><?= money($tong_doanh_thu) ?></strong></div>
    <div class="stat-card"><span>Tổng calo đã bán</span><strong style="font-size:1.3rem"><?= number_format($tong_calo) ?></strong></div>
    <div class="stat-card"><span>Đơn hoàn tất</span><strong><?= $status_counts['hoan_tat'] ?? 0 ?></strong></div>
    <div class="stat-card"><span>Đơn đang xử lý</span><strong><?= ($status_counts['cho_xac_nhan'] ?? 0) + ($status_counts['dang_chuan_bi'] ?? 0) + ($status_counts['dang_giao'] ?? 0) ?></strong></div>
    <div class="stat-card"><span>Đơn đã hủy</span><strong><?= $status_counts['huy'] ?? 0 ?></strong></div>
</div>

<div class="grid grid-2" style="margin-top:22px">
    <section>
        <div class="section-head"><h2>Doanh thu 6 tháng gần nhất</h2></div>
        <div class="panel">
            <?php if (!$monthly): ?>
                <p style="color:var(--muted)">Chưa có dữ liệu doanh thu.</p>
            <?php else: ?>
                <?php foreach ($monthly as $m): ?>
                    <?php $pct = $max_doanh_thu > 0 ? (float)$m['doanh_thu'] / $max_doanh_thu * 100 : 0; ?>
                    <div style="margin-bottom:14px">
                        <div style="display:flex;justify-content:space-between;font-size:.9rem;margin-bottom:4px">
                            <strong>Tháng <?= e($m['thang']) ?></strong>
                            <span><?= money($m['doanh_thu']) ?> · <?= (int)$m['so_don'] ?> đơn</span>
                        </div>
                        <div style="background:#edf3ef;border-radius:999px;height:12px;overflow:hidden">
                            <div style="height:100%;width:<?= round($pct,1) ?>%;background:linear-gradient(90deg,var(--green),var(--orange));border-radius:999px"></div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
    </section>

    <section>
        <div class="section-head"><h2>Đơn hàng theo trạng thái</h2></div>
        <div class="panel">
            <?php foreach (['cho_xac_nhan','dang_chuan_bi','dang_giao','hoan_tat','huy'] as $s): ?>
                <div style="display:flex;justify-content:space-between;padding:9px 0;border-bottom:1px solid var(--border)">
                    <span><?= order_status_badge($s) ?></span>
                    <strong><?= $status_counts[$s] ?? 0 ?> đơn</strong>
                </div>
            <?php endforeach; ?>
        </div>
    </section>
</div>

<section>
    <div class="section-head"><h2>Top món ăn bán chạy</h2></div>
    <div class="table-wrap">
        <table>
            <thead><tr><th>#</th><th>Món ăn</th><th>Số lượng bán</th><th>Doanh thu</th></tr></thead>
            <tbody>
            <?php foreach ($top_foods as $i => $f): ?>
                <tr>
                    <td><?= $i + 1 ?></td>
                    <td><div style="display:flex;align-items:center;gap:10px"><img class="thumb" src="<?= e($f['hinh_anh']) ?>" alt=""><strong><?= e($f['ten']) ?></strong></div></td>
                    <td><?= (int)$f['da_ban'] ?></td>
                    <td><?= money($f['doanh_thu']) ?></td>
                </tr>
            <?php endforeach; ?>
            <?php if (!$top_foods): ?><tr><td colspan="4" style="text-align:center;color:var(--muted)">Chưa có dữ liệu.</td></tr><?php endif; ?>
            </tbody>
        </table>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
