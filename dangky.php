<?php
require __DIR__ . '/includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    $ho_ten    = trim($_POST['ho_ten'] ?? '');
    $email     = trim($_POST['email'] ?? '');
    $mat_khau  = $_POST['mat_khau'] ?? '';
    $xac_nhan  = $_POST['xac_nhan'] ?? '';
    $gioi_tinh = $_POST['gioi_tinh'] ?? 'khac';
    $tuoi      = max(10, min(100, (int)($_POST['tuoi'] ?? 25)));
    $chieu_cao = max(100, min(230, (float)($_POST['chieu_cao'] ?? 165)));
    $can_nang  = max(30, min(250, (float)($_POST['can_nang'] ?? 60)));
    $muc_tieu  = $_POST['muc_tieu'] ?? 'duy_tri';

    $errors = [];
    if ($ho_ten === '') $errors[] = 'Vui lòng nhập họ tên.';
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Email không hợp lệ.';
    if (strlen($mat_khau) < 6) $errors[] = 'Mật khẩu phải có ít nhất 6 ký tự.';
    if ($mat_khau !== $xac_nhan) $errors[] = 'Mật khẩu xác nhận không khớp.';

    if (!$errors) {
        $stmt = $pdo->prepare('SELECT id FROM nguoi_dung WHERE email = ?');
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = 'Email này đã được đăng ký.';
        }
    }

    if (!$errors) {
        $hash = password_hash($mat_khau, PASSWORD_DEFAULT);
        $stmt = $pdo->prepare("
            INSERT INTO nguoi_dung
                (ho_ten, email, mat_khau, gioi_tinh, tuoi, chieu_cao, can_nang, muc_tieu, vai_tro, trang_thai)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'user', 1)
        ");
        $stmt->execute([$ho_ten, $email, $hash, $gioi_tinh, $tuoi, $chieu_cao, $can_nang, $muc_tieu]);

        flash('Đăng ký thành công! Vui lòng đăng nhập bằng email vừa tạo để vào trang khách hàng.', 'success');
        redirect('dangnhap.php');
    }

    foreach ($errors as $err) flash($err, 'danger');
}

$page_title = 'Đăng ký tài khoản';
include __DIR__ . '/includes/header.php';
?>

<section style="max-width:680px;margin:0 auto">
    <div class="panel">
        <h1 style="margin-top:0;color:var(--green-dark)">Tạo tài khoản</h1>
        <p style="color:var(--muted)">Đăng ký để đặt bữa ăn, lưu thực đơn gợi ý và đánh giá món ăn.</p>

        <?= flash_html() ?>

        <form method="post" action="dangky.php">
            <?= csrf_field() ?>
            <div class="form-row">
                <div class="form-group">
                    <label for="ho_ten">Họ tên *</label>
                    <input type="text" id="ho_ten" name="ho_ten" required value="<?= e($_POST['ho_ten'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label for="email">Email *</label>
                    <input type="email" id="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="mat_khau">Mật khẩu *</label>
                    <input type="password" id="mat_khau" name="mat_khau" required minlength="6">
                </div>
                <div class="form-group">
                    <label for="xac_nhan">Xác nhận mật khẩu *</label>
                    <input type="password" id="xac_nhan" name="xac_nhan" required minlength="6">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="gioi_tinh">Giới tính</label>
                    <select name="gioi_tinh" id="gioi_tinh">
                        <option value="khac">Khác</option>
                        <option value="nam">Nam</option>
                        <option value="nu">Nữ</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="tuoi">Tuổi</label>
                    <input type="number" id="tuoi" name="tuoi" value="25" min="10" max="100">
                </div>
            </div>
            <div class="form-row">
                <div class="form-group">
                    <label for="chieu_cao">Chiều cao (cm)</label>
                    <input type="number" step="0.1" id="chieu_cao" name="chieu_cao" value="165">
                </div>
                <div class="form-group">
                    <label for="can_nang">Cân nặng (kg)</label>
                    <input type="number" step="0.1" id="can_nang" name="can_nang" value="60">
                </div>
            </div>
            <div class="form-group">
                <label for="muc_tieu">Mục tiêu sức khỏe</label>
                <select name="muc_tieu" id="muc_tieu">
                    <option value="duy_tri">Duy trì cân nặng</option>
                    <option value="giam_can">Giảm cân</option>
                    <option value="tang_can">Tăng cân / tăng cơ</option>
                </select>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Đăng ký</button>
        </form>
        <p style="margin-bottom:0;text-align:center;margin-top:14px">
            Đã có tài khoản? <a href="dangnhap.php">Đăng nhập ngay</a>
        </p>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
