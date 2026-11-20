<?php
/** Quản lý session và phân quyền. */

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

function is_logged_in(): bool
{
    return isset($_SESSION['user_id']);
}

function is_admin(): bool
{
    return is_logged_in() && ($_SESSION['vai_tro'] ?? '') === 'admin';
}

function require_login(): void
{
    if (!is_logged_in()) {
        flash('Vui lòng đăng nhập để tiếp tục.', 'warning');
        redirect('dangnhap.php');
    }
}

function require_admin(): void
{
    if (!is_admin()) {
        flash('Bạn không có quyền truy cập trang quản trị.', 'danger');
        redirect('../dangnhap.php');
    }
}

function current_user(): ?array
{
    global $pdo;
    if (!is_logged_in()) {
        return null;
    }
    static $cached = null;
    if ($cached === null) {
        $stmt = $pdo->prepare('SELECT * FROM nguoi_dung WHERE id = ?');
        $stmt->execute([$_SESSION['user_id']]);
        $cached = $stmt->fetch() ?: null;
    }
    return $cached;
}

/**
 * Chặn toàn bộ site: chỉ cho phép trang đăng nhập / đăng ký hoạt động
 * khi chưa có session. Được gọi từ includes/db.php.
 */
function enforce_login_gate(): void
{
    if (is_logged_in()) {
        return;
    }

    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $public = ['dangnhap.php', 'dangky.php'];

    if (in_array(basename($script), $public, true)) {
        return;
    }

    // Trang trong thư mục admin cần đường dẫn tương đối khác.
    $prefix = str_contains($script, '/admin/') ? '../' : '';

    flash('Vui lòng đăng nhập để truy cập website.', 'warning');
    redirect($prefix . 'dangnhap.php');
}
