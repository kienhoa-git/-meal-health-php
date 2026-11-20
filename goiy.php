<?php
require __DIR__ . '/includes/db.php';

$user = current_user();
$result = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    check_csrf();

    $ho_ten   = trim($_POST['ho_ten'] ?? '');
    $gioi_tinh = $_POST['gioi_tinh'] ?? 'khac';
    $tuoi     = max(10, min(100, (int)($_POST['tuoi'] ?? 25)));
    $chieu_cao = max(100, min(230, (float)($_POST['chieu_cao'] ?? 165)));
    $can_nang = max(30, min(250, (float)($_POST['can_nang'] ?? 60)));
    $activity = $_POST['muc_do_hoat_dong'] ?? 'trung_binh';
    $goal     = $_POST['muc_tieu'] ?? 'duy_tri';

    $bmi = bmi($chieu_cao, $can_nang);
    $calo = daily_calories($gioi_tinh, $tuoi, $chieu_cao, $can_nang, $activity, $goal);

    // Chọn món gần nhất với calo mục tiêu cho từng bữa
    $meals = [
        ['bua' => 'Sáng', 'ty_le' => 0.25, 'danh_muc' => 1],
        ['bua' => 'Trưa', 'ty_le' => 0.35, 'danh_muc' => 2],
        ['bua' => 'Tối',  'ty_le' => 0.30, 'danh_muc' => 3],
        ['bua' => 'Phụ',  'ty_le' => 0.10, 'danh_muc' => 4],
    ];

    $chosen = [];
    foreach ($meals as $meal) {
        $target = $calo * $meal['ty_le'];
        $stmt = $pdo->prepare('SELECT * FROM mon_an WHERE trang_thai = 1 AND id_danh_muc = ? ORDER BY ABS(calo - ?) LIMIT 1');
        $stmt->execute([$meal['danh_muc'], $target]);
        $food = $stmt->fetch();
        if ($food) {
            $chosen[] = ['bua' => $meal['bua'], 'mon' => $food];
        }
    }

    if ($user) {
        $pdo->beginTransaction();
        try {
            $stmt = $pdo->prepare('INSERT INTO goi_y_bua_an (id_nguoi_dung, ngay, muc_tieu, tong_calo, ghi_chu) VALUES (?, NOW(), ?, ?, ?)');
            $ghi_chu = 'BMI ' . $bmi . ' - ' . bmi_label($bmi);
            $stmt->execute([$user['id'], $goal, $calo, $ghi_chu]);
            $goi_y_id = (int)$pdo->lastInsertId();

            $stmt = $pdo->prepare('INSERT INTO chi_tiet_goi_y (id_goi_y, id_mon_an, bua) VALUES (?, ?, ?)');
            foreach ($chosen as $item) {
                $stmt->execute([$goi_y_id, $item['mon']['id'], $item['bua']]);
            }
            $pdo->commit();
        } catch (PDOException $e) {
            $pdo->rollBack();
        }
    }

    $result = [
        'ho_ten' => $ho_ten, 'bmi' => $bmi, 'calo' => $calo,
        'goal' => $goal, 'chosen' => $chosen,
    ];
}

$page_title = 'Gợi ý bữa ăn';
include __DIR__ . '/includes/header.php';
?>

<section>
    <div class="section-head">
        <div>
            <h1>Gợi ý thực đơn cá nhân</h1>
            <p>Tính BMI và nhu cầu calo, sau đó chọn món ăn phù hợp cho từng bữa</p>
        </div>
    </div>

    <div class="grid grid-2">
        <div class="panel">
            <h2 style="margin-top:0;color:var(--green-dark)">Thông tin của bạn</h2>
            <form method="post" action="goiy.php">
                <?= csrf_field() ?>
                <div class="form-group">
                    <label for="ho_ten">Họ tên</label>
                    <input type="text" id="ho_ten" name="ho_ten" value="<?= e($user['ho_ten'] ?? '') ?>" placeholder="Tên của bạn">
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="gioi_tinh">Giới tính</label>
                        <select name="gioi_tinh" id="gioi_tinh">
                            <option value="nam" <?= ($user['gioi_tinh'] ?? '') === 'nam' ? 'selected' : '' ?>>Nam</option>
                            <option value="nu" <?= ($user['gioi_tinh'] ?? '') === 'nu' ? 'selected' : '' ?>>Nữ</option>
                            <option value="khac" <?= ($user['gioi_tinh'] ?? 'khac') === 'khac' ? 'selected' : '' ?>>Khác</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="tuoi">Tuổi</label>
                        <input type="number" id="tuoi" name="tuoi" value="<?= e($user['tuoi'] ?? 25) ?>" min="10" max="100">
                    </div>
                </div>
                <div class="form-row">
                    <div class="form-group">
                        <label for="chieu_cao">Chiều cao (cm)</label>
                        <input type="number" step="0.1" id="chieu_cao" name="chieu_cao" value="<?= e($user['chieu_cao'] ?? 165) ?>">
                    </div>
                    <div class="form-group">
                        <label for="can_nang">Cân nặng (kg)</label>
                        <input type="number" step="0.1" id="can_nang" name="can_nang" value="<?= e($user['can_nang'] ?? 60) ?>">
                    </div>
                </div>
                <div class="form-group">
                    <label for="muc_do_hoat_dong">Mức độ hoạt động</label>
                    <select name="muc_do_hoat_dong" id="muc_do_hoat_dong">
                        <option value="thap" <?= ($user['muc_do_hoat_dong'] ?? '') === 'thap' ? 'selected' : '' ?>>Ít vận động (ngồi nhiều)</option>
                        <option value="trung_binh" <?= ($user['muc_do_hoat_dong'] ?? 'trung_binh') === 'trung_binh' ? 'selected' : '' ?>>Vận động trung bình</option>
                        <option value="cao" <?= ($user['muc_do_hoat_dong'] ?? '') === 'cao' ? 'selected' : '' ?>>Vận động nhiều</option>
                    </select>
                </div>
                <div class="form-group">
                    <label for="muc_tieu">Mục tiêu</label>
                    <select name="muc_tieu" id="muc_tieu">
                        <option value="giam_can" <?= ($user['muc_tieu'] ?? '') === 'giam_can' ? 'selected' : '' ?>>Giảm cân</option>
                        <option value="duy_tri" <?= ($user['muc_tieu'] ?? 'duy_tri') === 'duy_tri' ? 'selected' : '' ?>>Duy trì cân nặng</option>
                        <option value="tang_can" <?= ($user['muc_tieu'] ?? '') === 'tang_can' ? 'selected' : '' ?>>Tăng cân / tăng cơ</option>
                    </select>
                </div>
                <button type="submit" class="btn btn-primary btn-block">✨ Gợi ý thực đơn</button>
                <?php if (!$user): ?>
                    <p class="hint" style="text-align:center">Bạn chưa đăng nhập. <a href="dangnhap.php">Đăng nhập</a> để lưu thực đơn vào tài khoản.</p>
                <?php endif; ?>
            </form>
        </div>

        <div>
            <?php if (!$result): ?>
                <div class="panel" style="text-align:center;padding:60px 20px;color:var(--muted)">
                    <div style="font-size:3rem">🍽️</div>
                    <p>Điền thông tin bên trái và nhấn “Gợi ý thực đơn” để xem kết quả.</p>
                </div>
            <?php else: ?>
                <div class="panel" style="background:var(--green-light);margin-bottom:16px">
                    <h2 style="margin-top:0;color:var(--green-dark)">Kết quả cho <?= e($result['ho_ten'] ?: 'bạn') ?></h2>
                    <div class="nutrition">
                        <div><strong><?= e($result['bmi']) ?></strong><span>BMI</span></div>
                        <div><strong><?= e(bmi_label($result['bmi'])) ?></strong><span>Phân loại</span></div>
                        <div><strong><?= (int)$result['calo'] ?></strong><span>Calo/ngày</span></div>
                        <div><strong><?= e(['giam_can'=>'Giảm cân','tang_can'=>'Tăng cân','duy_tri'=>'Duy trì'][$result['goal']]) ?></strong><span>Mục tiêu</span></div>
                    </div>
                </div>

                <?php foreach ($result['chosen'] as $item): $m = $item['mon']; ?>
                    <div class="card" style="margin-bottom:12px;padding:12px;display:flex;gap:14px;align-items:center">
                        <img src="<?= e($m['hinh_anh']) ?>" style="width:88px;height:70px;object-fit:cover;border-radius:8px" alt="">
                        <div style="flex:1">
                            <span class="pill"><?= e($item['bua']) ?></span>
                            <h3 style="margin:6px 0 2px"><a href="monan.php?id=<?= (int)$m['id'] ?>"><?= e($m['ten']) ?></a></h3>
                            <small style="color:var(--muted)"><?= (int)$m['calo'] ?> calo · <?= money($m['gia']) ?></small>
                        </div>
                        <a href="giohang.php?action=add&id=<?= (int)$m['id'] ?>&qty=1" class="btn btn-sm btn-primary">Thêm</a>
                    </div>
                <?php endforeach; ?>
                <a href="giohang.php" class="btn btn-orange btn-block">🛒 Đặt toàn bộ thực đơn</a>
            <?php endif; ?>
        </div>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
