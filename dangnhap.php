<?php
require __DIR__ . '/includes/db.php';

if (is_logged_in()) {
    redirect(is_admin() ? 'admin/index.php' : 'index.php');
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['mat_khau'] ?? '';

    $stmt = $pdo->prepare("SELECT * FROM nguoi_dung WHERE email = :login_email OR ho_ten = :login_name ORDER BY (vai_tro = 'admin') DESC, id ASC LIMIT 1");
    $stmt->execute([':login_email' => $email, ':login_name' => $email]);
    $user = $stmt->fetch();

    if ($user && password_verify($pass, $user['mat_khau'])) {
        if ((int)$user['trang_thai'] !== 1) {
            flash('Tài khoản của bạn đã bị khóa. Vui lòng liên hệ quản trị viên.', 'danger');
        } else {
            session_regenerate_id(true);
            $_SESSION['user_id'] = (int)$user['id'];
            $_SESSION['ho_ten']  = $user['ho_ten'];
            $_SESSION['vai_tro'] = $user['vai_tro'];
            flash('Đăng nhập thành công. Xin chào ' . $user['ho_ten'] . '!', 'success');
            redirect($user['vai_tro'] === 'admin' ? 'admin/index.php' : 'index.php');
        }
    } else {
        flash('Email hoặc mật khẩu không đúng.', 'danger');
    }
}

$page_title = 'Đăng nhập';
include __DIR__ . '/includes/header.php';
?>

<section style="max-width:480px;margin:0 auto">
    <div class="panel">
        <h1 style="margin-top:0;color:var(--green-dark)">Đăng nhập</h1>
        <?= flash_html() ?>
        <form method="post" action="dangnhap.php">
            <?= csrf_field() ?>
            <div class="form-group">
                <label for="email">Email hoặc tên đăng nhập</label>
                <input type="text" id="email" name="email" required value="<?= e($_POST['email'] ?? '') ?>" placeholder="admin hoặc user1@example.com">
            </div>
            <div class="form-group">
                <label for="mat_khau">Mật khẩu</label>
                <input type="password" id="mat_khau" name="mat_khau" required>
            </div>
            <button type="submit" class="btn btn-primary btn-block">Đăng nhập</button>
        </form>

        <a href="dangky.php" class="btn btn-outline btn-block" style="margin-top:10px">+ Tạo tài khoản mới</a>

        <div style="margin-top:20px;padding:14px;background:var(--green-light);border-radius:10px;font-size:.9rem">
            <strong>Tài khoản demo (dùng chung 1 form):</strong><br>
            Admin: <code>admin</code> / <code>123</code><br>
            Khách: <code>user1@example.com</code> / <code>123456</code>
        </div>
        <p style="text-align:center;margin-bottom:0;margin-top:16px">Chưa có tài khoản? <a href="dangky.php">Đăng ký ngay</a></p>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
