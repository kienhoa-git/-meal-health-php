<?php
require __DIR__ . '/includes/db.php';

$id = (int)($_GET['id'] ?? 0);
$stmt = $pdo->prepare("
    SELECT m.*, dm.ten AS danh_muc, nct.ten AS nguon
    FROM mon_an m
    JOIN danh_muc dm ON m.id_danh_muc = dm.id
    JOIN nguon_cong_thuc nct ON m.id_nguon = nct.id
    WHERE m.id = ? AND m.trang_thai = 1
");
$stmt->execute([$id]);
$mon = $stmt->fetch();

if (!$mon) {
    flash('Món ăn không tồn tại hoặc đã bị ẩn.', 'danger');
    redirect('danhmuc.php');
}

$pdo->prepare('UPDATE mon_an SET luot_xem = luot_xem + 1 WHERE id = ?')->execute([$id]);

$stmt = $pdo->prepare("
    SELECT bl.*, nd.ho_ten
    FROM binh_luan bl JOIN nguoi_dung nd ON bl.id_nguoi_dung = nd.id
    WHERE bl.id_mon_an = ? AND bl.trang_thai = 1
    ORDER BY bl.created_at DESC
");
$stmt->execute([$id]);
$comments = $stmt->fetchAll();

$stmt = $pdo->prepare('SELECT * FROM mon_an WHERE id_danh_muc = ? AND id <> ? AND trang_thai = 1 LIMIT 3');
$stmt->execute([$mon['id_danh_muc'], $id]);
$related = $stmt->fetchAll();

$page_title = $mon['ten'];
include __DIR__ . '/includes/header.php';
?>

<section>
    <a href="javascript:history.back()" class="btn btn-sm btn-ghost">← Quay lại thực đơn</a>
    <div class="detail" style="margin-top:16px">
        <div>
            <img class="main-img" src="<?= e($mon['hinh_anh']) ?>" alt="<?= e($mon['ten']) ?>">
            <div class="nutrition">
                <div><strong><?= (int)$mon['calo'] ?></strong><span>Calo</span></div>
                <div><strong><?= e($mon['protein']) ?>g</strong><span>Đạm</span></div>
                <div><strong><?= e($mon['carb']) ?>g</strong><span>Tinh bột</span></div>
                <div><strong><?= e($mon['fat']) ?>g</strong><span>Chất béo</span></div>
            </div>
        </div>

        <div>
            <span class="pill"><?= e($mon['danh_muc']) ?></span>
            <h1 style="margin:10px 0"><?= e($mon['ten']) ?></h1>
            <p style="color:var(--muted)">Nguồn công thức: <strong><?= e($mon['nguon']) ?></strong> · 👁 <?= (int)$mon['luot_xem'] ?> lượt xem</p>
            <p><?= nl2br(e($mon['mo_ta'])) ?></p>

            <div class="panel" style="margin:16px 0">
                <strong>Thành phần:</strong>
                <p style="margin:6px 0 0;color:var(--muted)"><?= e($mon['nguyen_lieu']) ?></p>
            </div>

            <div class="card-price" style="font-size:1.6rem"><?= money($mon['gia']) ?></div>

            <form action="giohang.php" method="post" class="form-row" style="margin-top:16px;align-items:end">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="add">
                <input type="hidden" name="id" value="<?= (int)$mon['id'] ?>">
                <div class="form-group" style="margin:0">
                    <label for="qty">Số lượng</label>
                    <input type="number" id="qty" name="qty" value="1" min="1" max="20">
                </div>
                <button type="submit" class="btn btn-primary">🛒 Thêm vào giỏ</button>
            </form>
        </div>
    </div>
</section>

<section>
    <div class="section-head"><h2>Đánh giá từ khách hàng</h2></div>
    <div class="panel">
        <?php if (!$comments): ?>
            <p style="color:var(--muted)">Chưa có đánh giá nào. Hãy là người đầu tiên!</p>
        <?php else: ?>
            <?php foreach ($comments as $c): ?>
                <div class="comment">
                    <div class="comment-head">
                        <span><?= e($c['ho_ten']) ?></span>
                        <span class="stars"><?= str_repeat('★', (int)$c['danh_gia']) ?><?= str_repeat('☆', 5 - (int)$c['danh_gia']) ?></span>
                        <small style="color:var(--muted);font-weight:400"><?= e($c['created_at']) ?></small>
                    </div>
                    <p><?= nl2br(e($c['noi_dung'])) ?></p>
                </div>
            <?php endforeach; ?>
        <?php endif; ?>

        <?php if (is_logged_in()): ?>
            <form action="binhluan.php" method="post" style="margin-top:18px">
                <?= csrf_field() ?>
                <input type="hidden" name="id_mon_an" value="<?= (int)$mon['id'] ?>">
                <div class="form-row">
                    <div class="form-group">
                        <label for="danh_gia">Đánh giá</label>
                        <select name="danh_gia" id="danh_gia">
                            <?php for ($i = 5; $i >= 1; $i--): ?>
                                <option value="<?= $i ?>"><?= str_repeat('★', $i) ?> (<?= $i ?>/5)</option>
                            <?php endfor; ?>
                        </select>
                    </div>
                    <div class="form-group" style="display:flex;align-items:end">
                        <button type="submit" class="btn btn-primary btn-block">Gửi đánh giá</button>
                    </div>
                </div>
                <div class="form-group">
                    <label for="noi_dung">Bình luận</label>
                    <textarea name="noi_dung" id="noi_dung" required placeholder="Chia sẻ trải nghiệm của bạn..."></textarea>
                </div>
                <p class="hint">Bình luận sẽ được quản trị viên duyệt trước khi hiển thị.</p>
            </form>
        <?php else: ?>
            <p style="margin-top:16px"><a href="dangnhap.php" class="btn btn-sm btn-outline">Đăng nhập để đánh giá</a></p>
        <?php endif; ?>
    </div>
</section>

<?php if ($related): ?>
<section>
    <div class="section-head"><h2>Món ăn tương tự</h2></div>
    <div class="grid grid-3">
        <?php foreach ($related as $r): ?>
            <article class="card">
                <a href="monan.php?id=<?= (int)$r['id'] ?>"><img class="card-img" src="<?= e($r['hinh_anh']) ?>" alt=""></a>
                <div class="card-body">
                    <h3><a href="monan.php?id=<?= (int)$r['id'] ?>"><?= e($r['ten']) ?></a></h3>
                    <div class="card-price"><?= money($r['gia']) ?></div>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</section>
<?php endif; ?>

<?php include __DIR__ . '/includes/footer.php'; ?>
