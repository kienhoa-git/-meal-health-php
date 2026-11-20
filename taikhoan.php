<?php
require __DIR__ . '/includes/db.php';
require_login();

$user = current_user();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'update_profile') {
    check_csrf();

    $ho_ten    = trim($_POST['ho_ten'] ?? '');
    $dien_thoai = trim($_POST['dien_thoai'] ?? '');
    $dia_chi   = trim($_POST['dia_chi'] ?? '');
    $gioi_tinh = $_POST['gioi_tinh'] ?? 'khac';
    $tuoi      = max(10, min(100, (int)($_POST['tuoi'] ?? 25)));
    $chieu_cao = max(100, min(230, (float)($_POST['chieu_cao'] ?? 165)));
    $can_nang  = max(30, min(250, (float)($_POST['can_nang'] ?? 60)));
    $muc_tieu  = $_POST['muc_tieu'] ?? 'duy_tri';

    if ($ho_ten === '') {
        flash('Họ tên không được để trống.', 'danger');
    } else {
        $stmt = $pdo->prepare("
            UPDATE nguoi_dung SET ho_ten=?, dien_thoai=?, dia_chi=?, gioi_tinh=?, tuoi=?,
                chieu_cao=?, can_nang=?, muc_tieu=? WHERE id=?
        ");
        $stmt->execute([$ho_ten, $dien_thoai, $dia_chi, $gioi_tinh, $tuoi, $chieu_cao, $can_nang, $muc_tieu, $_SESSION['user_id']]);
        $_SESSION['ho_ten'] = $ho_ten;
        flash('Cập nhật hồ sơ thành công.', 'success');
        redirect('taikhoan.php');
    }
}

$stmt = $pdo->prepare('SELECT * FROM don_hang WHERE id_nguoi_dung = ? ORDER BY created_at DESC');
$stmt->execute([$_SESSION['user_id']]);
$orders = $stmt->fetchAll();

$stmt = $pdo->prepare("
    SELECT bl.*, m.ten AS ten_mon
    FROM binh_luan bl JOIN mon_an m ON bl.id_mon_an = m.id
    WHERE bl.id_nguoi_dung = ? ORDER BY bl.created_at DESC
");
$stmt->execute([$_SESSION['user_id']]);
$comments = $stmt->fetchAll();

$page_title = 'Tài khoản của tôi';
include __DIR__ . '/includes/header.php';
?>

<section>
    <div class="section-head"><h1>Xin chào, <?= e($user['ho_ten']) ?> 👋</h1></div>

    <div class="grid grid-2">
        <div class="panel">
            <h2 style="margin-top:0;color:var(--green-dark)">Hồ sơ cá nhân</h2>
            <?= flash_html() ?>
            <form method="post" action="taikhoan.php">
                <?= csrf_field() ?>
                <input type="hidden" name="action" value="update_profile">
                <div class="form-group">
                    <label for="ho_ten">Họ tên</label>
                    <input type="text" id="ho_ten" name="ho_ten" value="<?= e($user['ho_ten']) ?>" required>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="email">Email</label>
                        <input type="email" value="<?= e($user['email']) ?>" disabled>
                    </div>
                    <div class="form-group">
                        <label for="dien_thoai">Điện thoại</label>
                        <input type="text" id="dien_thoai" name="dien_thoai" value="<?= e($user['dien_thoai'] ?? '') ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="dia_chi">Địa chỉ</label>
                    <textarea id="dia_chi" name="dia_chi"><?= e($user['dia_chi'] ?? '') ?></textarea>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="gioi_tinh">Giới tính</label>
                        <select name="gioi_tinh" id="gioi_tinh">
                            <option value="khac" <?= $user['gioi_tinh']==='khac'?'selected':'' ?>>Khác</option>
                            <option value="nam" <?= $user['gioi_tinh']==='nam'?'selected':'' ?>>Nam</option>
                            <option value="nu" <?= $user['gioi_tinh']==='nu'?'selected':'' ?>>Nữ</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="tuoi">Tuổi</label>
                        <input type="number" id="tuoi" name="tuoi" value="<?= e($user['tuoi']) ?>">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="chieu_cao">Chiều cao (cm)</label>
                        <input type="number" step="0.1" id="chieu_cao" name="chieu_cao" value="<?= e($user['chieu_cao']) ?>">
                    </div>
                    <div class="form-group">
                        <label for="can_nang">Cân nặng (kg)</label>
                        <input type="number" step="0.1" id="can_nang" name="can_nang" value="<?= e($user['can_nang']) ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="muc_tieu">Mục tiêu</label>
                    <select name="muc_tieu" id="muc_tieu">
                        <option value="giam_can" <?= $user['muc_tieu']==='giam_can'?'selected':'' ?>>Giảm cân</option>
                        <option value="duy_tri" <?= $user['muc_tieu']==='duy_tri'?'selected':'' ?>>Duy trì</option>
                        <option value="tang_can" <?= $user['muc_tieu']==='tang_can'?'selected':'' ?>>Tăng cân</option>
                    </select>
                </div>
                <?php
                $bmi = bmi((float)$user['chieu_cao'], (float)$user['can_nang']);
                ?>
                <p class="hint">BMI hiện tại: <strong><?= e($bmi) ?> - <?= e(bmi_label($bmi)) ?></strong></p>
                <button type="submit" class="btn btn-primary btn-block">Lưu thay đổi</button>
            </form>
        </div>

        <div>
            <div class="panel">
                <h2 style="margin-top:0;color:var(--green-dark)">Lịch sử đặt bữa</h2>
                <?php if (!$orders): ?>
                    <p style="color:var(--muted)">Bạn chưa có đơn hàng nào. <a href="danhmuc.php">Đặt bữa ngay</a></p>
                <?php else: ?>
                    <?php foreach ($orders as $o): ?>
                        <div style="border:1px solid var(--border);border-radius:10px;padding:12px;margin-bottom:10px">
                            <div style="display:flex;justify-content:space-between;gap:8px">
                                <strong>Đơn #<?= (int)$o['id'] ?> · <?= e($o['created_at']) ?></strong>
                                <?= order_status_badge($o['trang_thai']) ?>
                            </div>
                            <div style="margin-top:6px;color:var(--muted);font-size:.9rem">
                                <?= (int)$o['tong_calo'] ?> calo · <strong style="color:var(--orange)"><?= money($o['tong_tien']) ?></strong>
                            </div>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

            <div class="panel" style="margin-top:16px">
                <h2 style="margin-top:0;color:var(--green-dark)">Đánh giá của bạn</h2>
                <?php if (!$comments): ?>
                    <p style="color:var(--muted)">Bạn chưa có đánh giá nào.</p>
                <?php else: ?>
                    <?php foreach ($comments as $c): ?>
                        <div class="comment">
                            <div class="comment-head">
                                <a href="monan.php?id=<?= (int)$c['id_mon_an'] ?>"><?= e($c['ten_mon']) ?></a>
                                <span class="stars"><?= str_repeat('★', (int)$c['danh_gia']) ?></span>
                                <span class="pill"><?= (int)$c['trang_thai'] === 1 ? 'Đã duyệt' : 'Chờ duyệt' ?></span>
                            </div>
                            <p><?= e($c['noi_dung']) ?></p>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
