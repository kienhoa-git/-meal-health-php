<?php
/** Các hàm tiện ích dùng chung. Cần include sau auth.php (để có session). */

function e($value): string
{
    return htmlspecialchars((string)$value, ENT_QUOTES, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . $path);
    exit;
}

function flash(string $message, string $type = 'success'): void
{
    $_SESSION['flash'] = ['message' => $message, 'type' => $type];
}

function flash_html(): string
{
    if (empty($_SESSION['flash'])) {
        return '';
    }
    $f = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return '<div class="alert alert-' . e($f['type']) . '" role="alert">'
        . e($f['message'])
        . '<button type="button" class="alert-close" aria-label="Đóng">&times;</button>'
        . '</div>';
}

function csrf_token(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function csrf_field(): string
{
    return '<input type="hidden" name="csrf_token" value="' . e(csrf_token()) . '">';
}

function check_csrf(): void
{
    $token = $_POST['csrf_token'] ?? '';
    if (!hash_equals($_SESSION['csrf_token'] ?? '', $token)) {
        http_response_code(419);
        die('Phiên làm việc hết hạn. Vui lòng tải lại trang.');
    }
}

function money($amount): string
{
    return number_format((float)$amount, 0, ',', '.') . ' đ';
}

function cart_count(): int
{
    if (empty($_SESSION['cart'])) {
        return 0;
    }
    $total = 0;
    foreach ($_SESSION['cart'] as $item) {
        $total += (int)($item['qty'] ?? 0);
    }
    return $total;
}

function cart_totals(): array
{
    $money = 0;
    $calo = 0;
    foreach ($_SESSION['cart'] ?? [] as $item) {
        $money += (float)$item['gia'] * (int)$item['qty'];
        $calo  += (int)$item['calo'] * (int)$item['qty'];
    }
    return ['money' => $money, 'calo' => $calo];
}

/** Upload ảnh món ăn vào images/product/, trả về đường dẫn tương đối. */
function upload_image(string $field, string $prefix = 'monan'): ?string
{
    if (empty($_FILES[$field]) || $_FILES[$field]['error'] === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    $file = $_FILES[$field];
    if ($file['error'] !== UPLOAD_ERR_OK) {
        flash('Upload ảnh bị lỗi (mã ' . $file['error'] . ').', 'danger');
        return null;
    }
    if ($file['size'] > 2 * 1024 * 1024) {
        flash('Ảnh phải nhỏ hơn 2 MB.', 'danger');
        return null;
    }
    $info = @getimagesize($file['tmp_name']);
    if ($info === false) {
        flash('File upload không phải hình ảnh hợp lệ.', 'danger');
        return null;
    }
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    $allow = ['jpg', 'jpeg', 'png', 'gif', 'webp', 'svg'];
    if (!in_array($ext, $allow, true)) {
        flash('Chỉ hỗ trợ ảnh JPG, PNG, GIF, WEBP, SVG.', 'danger');
        return null;
    }
    $dir = __DIR__ . '/../images/product';
    if (!is_dir($dir)) {
        mkdir($dir, 0755, true);
    }
    $name = $prefix . '_' . date('YmdHis') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    if (!move_uploaded_file($file['tmp_name'], $dir . '/' . $name)) {
        flash('Không thể lưu ảnh lên máy chủ.', 'danger');
        return null;
    }
    return 'images/product/' . $name;
}

function bmi(float $heightCm, float $weightKg): float
{
    $m = $heightCm / 100;
    return $m > 0 ? round($weightKg / ($m * $m), 1) : 0;
}

function bmi_label(float $bmi): string
{
    if ($bmi < 18.5) return 'Thiếu cân';
    if ($bmi < 24)   return 'Bình thường';
    if ($bmi < 28)   return 'Thừa cân';
    return 'Béo phì';
}

/** Tính nhu cầu calo/ngày theo Mifflin-St Jeor + hệ số hoạt động + mục tiêu. */
function daily_calories(string $gender, int $age, float $height, float $weight, string $activity, string $goal): int
{
    $base = 10 * $weight + 6.25 * $height - 5 * $age;
    $base += ($gender === 'nam') ? 5 : -161;
    $factor = ['thap' => 1.2, 'trung_binh' => 1.55, 'cao' => 1.9][$activity] ?? 1.55;
    $tdee = $base * $factor;
    if ($goal === 'giam_can') $tdee -= 400;
    if ($goal === 'tang_can') $tdee += 350;
    return max(1200, (int)round($tdee));
}

function order_status_label(string $status): string
{
    return [
        'cho_xac_nhan'  => 'Chờ xác nhận',
        'dang_chuan_bi' => 'Đang chuẩn bị',
        'dang_giao'     => 'Đang giao',
        'hoan_tat'      => 'Hoàn tất',
        'huy'           => 'Đã hủy',
    ][$status] ?? $status;
}

function order_status_badge(string $status): string
{
    return '<span class="badge badge-' . e($status) . '">' . e(order_status_label($status)) . '</span>';
}

function slugify(string $text): string
{
    $map = [
        'à'=>'a','á'=>'a','ả'=>'a','ã'=>'a','ạ'=>'a','ă'=>'a','ắ'=>'a','ằ'=>'a','ẳ'=>'a','ẵ'=>'a','ặ'=>'a',
        'â'=>'a','ấ'=>'a','ầ'=>'a','ẩ'=>'a','ẫ'=>'a','ậ'=>'a','đ'=>'d',
        'è'=>'e','é'=>'e','ẻ'=>'e','ẽ'=>'e','ẹ'=>'e','ê'=>'e','ế'=>'e','ề'=>'e','ể'=>'e','ễ'=>'e','ệ'=>'e',
        'ì'=>'i','í'=>'i','ỉ'=>'i','ĩ'=>'i','ị'=>'i',
        'ò'=>'o','ó'=>'o','ỏ'=>'o','õ'=>'o','ọ'=>'o','ô'=>'o','ố'=>'o','ồ'=>'o','ổ'=>'o','ỗ'=>'o','ộ'=>'o',
        'ơ'=>'o','ớ'=>'o','ờ'=>'o','ở'=>'o','ỡ'=>'o','ợ'=>'o',
        'ù'=>'u','ú'=>'u','ủ'=>'u','ũ'=>'u','ụ'=>'u','ư'=>'u','ứ'=>'u','ừ'=>'u','ử'=>'u','ữ'=>'u','ự'=>'u',
        'ỳ'=>'y','ý'=>'y','ỷ'=>'y','ỹ'=>'y','ỵ'=>'y',
    ];

    $text = mb_strtolower($text, 'UTF-8');
    $text = strtr($text, $map);
    $text = preg_replace('/[^a-z0-9\s-]/', '', $text);
    $text = preg_replace('/[\s-]+/', '-', trim($text));

    return trim($text, '-');
}
