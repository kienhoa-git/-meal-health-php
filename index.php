<?php
require __DIR__ . '/includes/db.php';

$featured = $pdo->query("
    SELECT m.*, dm.ten AS danh_muc
    FROM mon_an m
    JOIN danh_muc dm ON m.id_danh_muc = dm.id
    WHERE m.trang_thai = 1
    ORDER BY m.luot_xem DESC
    LIMIT 3
")->fetchAll();

$categories = $pdo->query("
    SELECT dm.*, COUNT(m.id) AS so_mon
    FROM danh_muc dm
    LEFT JOIN mon_an m ON m.id_danh_muc = dm.id AND m.trang_thai = 1
    GROUP BY dm.id
    ORDER BY dm.id
")->fetchAll();

$page_title = 'Trang chủ';
include __DIR__ . '/includes/header.php';
?>

<section class="hero">
    <div>
        <h1>Ăn đúng cách,<br>sống khỏe mỗi ngày</h1>
        <p>Khám phá thực đơn lành mạnh, tính toán nhu cầu calo cá nhân và đặt những bữa ăn được thiết kế riêng cho mục tiêu sức khỏe của bạn.</p>
        <div class="hero-actions">
            <a href="danhmuc.php" class="btn btn-orange">Xem thực đơn</a>
            <a href="goiy.php" class="btn btn-outline">Nhận gợi ý miễn phí</a>
        </div>
    </div>
    <img src="images/banner.svg" alt="Bữa ăn lành mạnh">
</section>

<section>
    <div class="section-head">
        <div>
            <h2>Món ăn được yêu thích</h2>
            <p>Những bữa ăn được chọn nhiều nhất từ cộng đồng</p>
        </div>
        <a href="danhmuc.php" class="btn btn-sm btn-outline">Xem tất cả →</a>
    </div>
    <div class="grid grid-3">
        <?php foreach ($featured as $m): ?>
            <article class="card">
                <a href="monan.php?id=<?= (int)$m['id'] ?>">
                    <img class="card-img" src="<?= e($m['hinh_anh']) ?>" alt="<?= e($m['ten']) ?>">
                </a>
                <div class="card-body">
                    <span class="pill"><?= e($m['danh_muc']) ?></span>
                    <h3 style="margin-top:8px"><a href="monan.php?id=<?= (int)$m['id'] ?>"><?= e($m['ten']) ?></a></h3>
                    <p><?= e(mb_substr($m['mo_ta'], 0, 70)) ?>...</p>
                    <div class="meta">
                        <span class="pill"><?= (int)$m['calo'] ?> calo</span>
                        <span class="pill">⭐ 4.8</span>
                    </div>
                    <div class="card-price" style="margin-top:10px"><?= money($m['gia']) ?></div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>

<section>
    <div class="section-head">
        <div>
            <h2>Chọn theo bữa ăn</h2>
            <p>Danh mục được thiết kế phù hợp từng thời điểm trong ngày</p>
        </div>
    </div>
    <div class="grid grid-3">
        <?php foreach ($categories as $c): ?>
            <a class="card" style="padding:20px" href="danhmuc.php?slug=<?= e($c['slug']) ?>">
                <h3 style="margin:0;color:var(--green-dark)"><?= e($c['ten']) ?></h3>
                <p style="color:var(--muted);margin:6px 0 0"><?= e($c['mo_ta']) ?></p>
                <span class="pill" style="margin-top:10px;display:inline-block"><?= (int)$c['so_mon'] ?> món</span>
            </a>
        <?php endforeach; ?>
    </div>
</section>

<section>
    <div class="panel" style="text-align:center;background:var(--green-light)">
        <h2 style="color:var(--green-dark);margin-top:0">Chưa biết bắt đầu từ đâu?</h2>
        <p>Chỉ cần nhập chiều cao, cân nặng và mục tiêu — hệ thống sẽ tính calo và gợi ý thực đơn phù hợp cho bạn.</p>
        <a href="goiy.php" class="btn btn-primary">Tạo thực đơn của tôi</a>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
