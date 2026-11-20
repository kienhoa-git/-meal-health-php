<?php
require __DIR__ . '/includes/db.php';

$action = $_GET['action'] ?? ($_POST['action'] ?? '');

if ($action === 'add') {
    if (isset($_GET['id'])) {
        $id  = (int)$_GET['id'];
        $qty = max(1, (int)($_GET['qty'] ?? 1));
    } else {
        check_csrf();
        $id  = (int)($_POST['id'] ?? 0);
        $qty = max(1, (int)($_POST['qty'] ?? 1));
    }

    if ($id > 0) {
        $stmt = $pdo->prepare('SELECT * FROM mon_an WHERE id = ? AND trang_thai = 1');
        $stmt->execute([$id]);
        $m = $stmt->fetch();
        if ($m) {
            if (isset($_SESSION['cart'][$id])) {
                $_SESSION['cart'][$id]['qty'] += $qty;
            } else {
                $_SESSION['cart'][$id] = [
                    'id'        => (int)$m['id'],
                    'ten'       => $m['ten'],
                    'gia'       => (float)$m['gia'],
                    'calo'      => (int)$m['calo'],
                    'hinh_anh'  => $m['hinh_anh'],
                    'qty'       => $qty,
                ];
            }
            flash('Đã thêm "' . $m['ten'] . '" vào giỏ hàng.', 'success');
        } else {
            flash('Món ăn không còn tồn tại.', 'danger');
        }
    }
    redirect('giohang.php');
}

if ($action === 'remove') {
    $id = (int)($_GET['id'] ?? 0);
    unset($_SESSION['cart'][$id]);
    flash('Đã xóa món khỏi giỏ hàng.', 'success');
    redirect('giohang.php');
}

if ($action === 'clear') {
    $_SESSION['cart'] = [];
    flash('Đã xóa toàn bộ giỏ hàng.', 'success');
    redirect('giohang.php');
}

if ($action === 'update' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();
    foreach ($_POST['qty'] ?? [] as $id => $qty) {
        $id = (int)$id;
        $qty = (int)$qty;
        if (isset($_SESSION['cart'][$id])) {
            if ($qty <= 0) {
                unset($_SESSION['cart'][$id]);
            } else {
                $_SESSION['cart'][$id]['qty'] = min(50, $qty);
            }
        }
    }
    flash('Cập nhật giỏ hàng thành công.', 'success');
    redirect('giohang.php');
}

$items = $_SESSION['cart'] ?? [];
$totals = cart_totals();
$page_title = 'Giỏ hàng';
include __DIR__ . '/includes/header.php';
?>

<section>
    <div class="section-head"><h1>Giỏ hàng của bạn</h1></div>

    <?php if (!$items): ?>
        <div class="empty-state">
            <div class="emoji">🛒</div>
            <p>Giỏ hàng đang trống.</p>
            <a href="danhmuc.php" class="btn btn-primary">Chọn món ăn ngay</a>
        </div>
    <?php else: ?>
        <form method="post" action="giohang.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="update">
            <div class="panel" style="padding:0">
                <?php foreach ($items as $item): ?>
                    <div class="cart-item">
                        <img src="<?= e($item['hinh_anh']) ?>" alt="<?= e($item['ten']) ?>">
                        <div>
                            <strong><?= e($item['ten']) ?></strong><br>
                            <small style="color:var(--muted)"><?= (int)$item['calo'] ?> calo / suất · <?= money($item['gia']) ?></small>
                        </div>
                        <div>
                            <label style="font-size:.8rem;color:var(--muted)">Số lượng</label>
                            <input type="number" name="qty[<?= (int)$item['id'] ?>]" value="<?= (int)$item['qty'] ?>"
                                   min="1" max="50" data-price="<?= (float)$item['gia'] ?>">
                        </div>
                        <div class="line-total" style="font-weight:700;color:var(--orange)">
                            <?= money($item['gia'] * $item['qty']) ?>
                        </div>
                        <a href="giohang.php?action=remove&id=<?= (int)$item['id'] ?>" title="Xóa"
                           data-confirm="Xóa món này khỏi giỏ?" style="color:var(--danger);font-size:1.2rem">✕</a>
                    </div>
                <?php endforeach; ?>
            </div>

            <div class="cart-summary">
                <div>Tổng calo: <strong><?= (int)$totals['calo'] ?> calo</strong></div>
                <div class="total">Tổng tiền: <?= money($totals['money']) ?></div>
            </div>

            <div style="display:flex;justify-content:space-between;gap:10px;flex-wrap:wrap">
                <button type="submit" class="btn btn-outline">Cập nhật số lượng</button>
                <div style="display:flex;gap:10px">
                    <a href="giohang.php?action=clear" class="btn btn-ghost" data-confirm="Xóa toàn bộ giỏ hàng?">Xóa giỏ</a>
                    <?php if (is_logged_in()): ?>
                        <a href="datban.php" class="btn btn-orange">Đặt bữa →</a>
                    <?php else: ?>
                        <a href="dangnhap.php" class="btn btn-orange">Đăng nhập để đặt bữa</a>
                    <?php endif; ?>
                </div>
            </div>
        </form>
    <?php endif; ?>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
