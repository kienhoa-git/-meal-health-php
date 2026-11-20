<?php
require __DIR__ . '/includes/db.php';

$q         = trim($_GET['q'] ?? '');
$slug      = $_GET['danh_muc'] ?? '';
$min_calo  = (int)($_GET['min_calo'] ?? 0);
$max_calo  = (int)($_GET['max_calo'] ?? 0);

$where  = ['m.trang_thai = 1'];
$params = [];

if ($q !== '') {
    $where[] = '(m.ten LIKE :q OR m.mo_ta LIKE :q OR m.nguyen_lieu LIKE :q)';
    $params[':q'] = '%' . $q . '%';
}
if ($slug !== '') {
    $where[] = 'dm.slug = :slug';
    $params[':slug'] = $slug;
}
if ($min_calo > 0) {
    $where[] = 'm.calo >= :min_calo';
    $params[':min_calo'] = $min_calo;
}
if ($max_calo > 0) {
    $where[] = 'm.calo <= :max_calo';
    $params[':max_calo'] = $max_calo;
}

$sql = "
    SELECT m.*, dm.ten AS danh_muc
    FROM mon_an m JOIN danh_muc dm ON m.id_danh_muc = dm.id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY m.id
";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$results = $stmt->fetchAll();

$categories = $pdo->query('SELECT * FROM danh_muc ORDER BY id')->fetchAll();
$page_title = 'Tìm kiếm món ăn';
include __DIR__ . '/includes/header.php';
?>

<section>
    <div class="section-head">
        <div>
            <h1>Tìm kiếm món ăn</h1>
            <p>Lọc theo tên món, danh mục hoặc khoảng calo</p>
        </div>
    </div>

    <div class="panel">
        <form method="get" action="timkiem.php">
            <div class="form-row">
                <div class="form-group">
                    <label for="q">Từ khóa</label>
                    <input type="text" id="q" name="q" value="<?= e($q) ?>" placeholder="Ví dụ: cá hồi, yến mạch...">
                </div>
                <div class="form-group">
                    <label for="danh_muc">Danh mục</label>
                    <select name="danh_muc" id="danh_muc">
                        <option value="">Tất cả danh mục</option>
                        <?php foreach ($categories as $c): ?>
                            <option value="<?= e($c['slug']) ?>" <?= $slug === $c['slug'] ? 'selected' : '' ?>><?= e($c['ten']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="min_calo">Calo tối thiểu</label>
                    <input type="number" id="min_calo" name="min_calo" value="<?= $min_calo ?: '' ?>" placeholder="0">
                </div>
                <div class="form-group">
                    <label for="max_calo">Calo tối đa</label>
                    <input type="number" id="max_calo" name="max_calo" value="<?= $max_calo ?: '' ?>" placeholder="800">
                </div>
            </div>
            <button type="submit" class="btn btn-primary">🔍 Tìm kiếm</button>
            <a href="timkiem.php" class="btn btn-ghost">Xóa bộ lọc</a>
        </form>
    </div>

    <div class="section-head" style="margin-top:26px">
        <h2><?= count($results) ?> kết quả</h2>
    </div>

    <?php if (!$results): ?>
        <div class="empty-state"><div class="emoji">🔎</div><p>Không tìm thấy món ăn phù hợp. Thử đổi từ khóa hoặc bộ lọc.</p></div>
    <?php else: ?>
        <div class="grid grid-3">
            <?php foreach ($results as $m): ?>
                <article class="card">
                    <a href="monan.php?id=<?= (int)$m['id'] ?>"><img class="card-img" src="<?= e($m['hinh_anh']) ?>" alt=""></a>
                    <div class="card-body">
                        <span class="pill"><?= e($m['danh_muc']) ?></span>
                        <h3 style="margin-top:8px"><a href="monan.php?id=<?= (int)$m['id'] ?>"><?= e($m['ten']) ?></a></h3>
                        <p><?= e(mb_substr($m['mo_ta'], 0, 60)) ?>...</p>
                        <div class="meta"><span class="pill"><?= (int)$m['calo'] ?> calo</span></div>
                        <div class="card-price" style="margin-top:10px"><?= money($m['gia']) ?></div>
                    </div>
                </article>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
