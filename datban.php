<?php
require __DIR__ . '/includes/db.php';
require_login();

$items = $_SESSION['cart'] ?? [];
$totals = cart_totals();

if (!$items) {
    flash('Giỏ hàng của bạn đang trống.', 'warning');
    redirect('giohang.php');
}

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    $ho_ten    = trim($_POST['ho_ten'] ?? '');
    $dien_thoai = trim($_POST['dien_thoai'] ?? '');
    $dia_chi   = trim($_POST['dia_chi'] ?? '');
    $ghi_chu   = trim($_POST['ghi_chu'] ?? '');

    if ($ho_ten === '' || $dien_thoai === '' || $dia_chi === '') {
        flash('Vui lòng điền đầy đủ họ tên, điện thoại và địa chỉ nhận bữa.', 'danger');
    } else {
        try {
            $pdo->beginTransaction();

            $stmt = $pdo->prepare("
                INSERT INTO don_hang
                    (id_nguoi_dung, ho_ten, dien_thoai, dia_chi, tong_tien, tong_calo, trang_thai, ghi_chu)
                VALUES (?, ?, ?, ?, ?, ?, 'cho_xac_nhan', ?)
            ");
            $stmt->execute([
                $_SESSION['user_id'], $ho_ten, $dien_thoai, $dia_chi,
                $totals['money'], $totals['calo'], $ghi_chu,
            ]);
            $don_hang_id = (int)$pdo->lastInsertId();

            $stmt = $pdo->prepare("
                INSERT INTO chi_tiet_don_hang
                    (id_don_hang, id_mon_an, ten_mon_an, so_luong, gia)
                VALUES (?, ?, ?, ?, ?)
            ");
            foreach ($items as $item) {
                $stmt->execute([
                    $don_hang_id, $item['id'], $item['ten'], $item['qty'], $item['gia'],
                ]);
            }

            $pdo->commit();
            $_SESSION['cart'] = [];
            flash('Đặt bữa thành công! Mã đơn hàng #' . $don_hang_id . '. Vui lòng theo dõi trong tài khoản.', 'success');
            redirect('taikhoan.php');
        } catch (PDOException $e) {
            $pdo->rollBack();
            flash('Có lỗi khi lưu đơn hàng. Vui lòng thử lại.', 'danger');
        }
    }
}

$page_title = 'Đặt bữa';
include __DIR__ . '/includes/header.php';
?>

<section>
    <div class="section-head"><h1>Xác nhận đặt bữa</h1></div>
    <?= flash_html() ?>
    <div class="grid grid-2">
        <div class="panel">
            <h2 style="margin-top:0;color:var(--green-dark)">Thông tin nhận bữa</h2>
            <form method="post" action="datban.php">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label for="ho_ten">Họ tên người nhận *</label>
                    <input type="text" id="ho_ten" name="ho_ten" required value="<?= e($_POST['ho_ten'] ?? $user['ho_ten'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="dien_thoai">Điện thoại *</label>
                    <input type="text" id="dien_thoai" name="dien_thoai" required value="<?= e($_POST['dien_thoai'] ?? $user['dien_thoai'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="dia_chi">Địa chỉ nhận bữa *</label>
                    <textarea id="dia_chi" name="dia_chi" required><?= e($_POST['dia_chi'] ?? $user['dia_chi'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label for="ghi_chu">Ghi chú</label>
                    <textarea id="ghi_chu" name="ghi_chu" placeholder="Ví dụ: giao buổi sáng, ít dầu mỡ..."><?= e($_POST['ghi_chu'] ?? '') ?></textarea>
                </div>
                <button type="submit" class="btn btn-primary btn-block">✓ Xác nhận đặt bữa</button>
            </form>
        </div>

        <div class="panel">
            <h2 style="margin-top:0;color:var(--green-dark)">Đơn hàng của bạn</h2>
            <?php foreach ($items as $item): ?>
                <div style="display:flex;justify-content:space-between;border-bottom:1px solid var(--border);padding:8px 0">
                    <span><?= e($item['ten']) ?> <small style="color:var(--muted)">x<?= (int)$item['qty'] ?></small></span>
                    <strong><?= money($item['gia'] * $item['qty']) ?></strong>
                </div>
            <?php endforeach; ?>
            <div style="display:flex;justify-content:space-between;margin-top:14px;font-weight:700">
                <span>Tổng calo</span><span><?= (int)$totals['calo'] ?> calo</span>
            </div>
            <div style="display:flex;justify-content:space-between;margin-top:6px;font-size:1.25rem;color:var(--orange);font-weight:700">
                <span>Tổng tiền</span><span><?= money($totals['money']) ?></span>
            </div>
            <a href="giohang.php" class="btn btn-sm btn-ghost" style="margin-top:16px">← Sửa giỏ hàng</a>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
