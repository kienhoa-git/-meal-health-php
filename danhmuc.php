<?php
require __DIR__ . '/includes/db.php';

$slug = $_GET['slug'] ?? '';
$current = null;

if ($slug !== '') {
    $stmt = $pdo->prepare('SELECT * FROM danh_muc WHERE slug = ?');
    $stmt->execute([$slug]);
    $current = $stmt->fetch();
    if (!$current) {
        flash('Danh mục không tồn tại.', 'danger');
        redirect('danhmuc.php');
    }
    $stmt = $pdo->prepare("
        SELECT m.*, dm.ten AS danh_muc
        FROM mon_an m JOIN danh_muc dm ON m.id_danh_muc = dm.id
        WHERE m.trang_thai = 1 AND dm.slug = ?
        ORDER BY m.id
    ");
    $stmt->execute([$slug]);
} else {
    $stmt = $pdo->query("
        SELECT m.*, dm.ten AS danh_muc
        FROM mon_an m JOIN danh_muc dm ON m.id_danh_muc = dm.id
        WHERE m.trang_thai = 1
        ORDER BY dm.id, m.id
    ");
}
$mon_ans = $stmt->fetchAll();
$categories = $pdo->query('SELECT * FROM danh_muc ORDER BY id')->fetchAll();

$page_title = $current ? $current['ten'] : 'Thực đơn';
include __DIR__ . '/includes/header.php';
?>

<section>
    <div class="section-head">
        <div>
            <h1><?= $current ? e($current['ten']) : 'Toàn bộ thực đơn' ?></h1>
            <p><?= $current ? e($current['mo_ta']) : 'Chọn bữa ăn phù hợp với mục tiêu sức khỏe của bạn' ?></p>
        </div>
    </div>

    <div class="toolbar">
        <a href="danhmuc.php" class="btn btn-sm <?= !$current ? 'btn-primary' : 'btn-outline' ?>">Tất cả</a>
        <?php foreach ($categories as $c): ?>
            <a href="danhmuc.php?slug=<?= e($c['slug']) ?>"
               class="btn btn-sm <?= $current && $current['id'] == $c['id'] ? 'btn-primary' : 'btn-outline' ?>">
                <?= e($c['ten']) ?>
            </a>
        <?php endforeach; ?>
    </div>

    <?php if (!$mon_ans): ?>
        <div class="empty-state"><div class="emoji">🥗</div><p>Chưa có món ăn trong danh mục này.</p></div>
    <?php else: ?>
        <div class="grid grid-3">
            <?php foreach ($mon_ans as $m): ?>
                <article class="card">
                    <a href="monan.php?id=<?= (int)$m['id'] ?>">
                        <img class="card-img" src="<?= e($m['hinh_anh']) ?>" alt="<?= e($m['ten']) ?>">
                    </a>
                    <div class="card-body">
                        <span class="pill"><?= e($m['danh_muc']) ?></span>
                        <h3 style="margin-top:8px"><a href="monan.php?id=<?= (int)$m['id'] ?>"><?= e($m['ten']) ?></a></h3>
                        <p><?= e(mb_substr($m['mo_ta'], 0, 65)) ?>...</p>
                        <div class="meta">
                            <span class="pill"><?= (int)$m['calo'] ?> calo</span>
                            <span class="pill">Đạm <?= e($m['protein']) ?>g</span>
                        </div>
                        <div class="card-price" style="margin-top:10px"><?= money($m['gia']) ?></div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
