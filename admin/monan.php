<?php
require_once __DIR__ . '/includes/auth.php';

$categories = $pdo->query('SELECT * FROM danh_muc ORDER BY id')->fetchAll();
$sources    = $pdo->query('SELECT * FROM nguon_cong_thuc ORDER BY id')->fetchAll();

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    check_csrf();

    $id         = (int)($_POST['id'] ?? 0);
    $ten        = trim($_POST['ten'] ?? '');
    $slug       = trim($_POST['slug'] ?? '');
    $id_dm      = (int)($_POST['id_danh_muc'] ?? 0);
    $id_nguon   = (int)($_POST['id_nguon'] ?? 0);
    $mo_ta      = trim($_POST['mo_ta'] ?? '');
    $nguyen_lieu = trim($_POST['nguyen_lieu'] ?? '');
    $calo       = max(0, (int)($_POST['calo'] ?? 0));
    $protein    = max(0, (float)($_POST['protein'] ?? 0));
    $carb       = max(0, (float)($_POST['carb'] ?? 0));
    $fat        = max(0, (float)($_POST['fat'] ?? 0));
    $gia        = max(0, (float)($_POST['gia'] ?? 0));
    $trang_thai = isset($_POST['trang_thai']) ? 1 : 0;
    $hinh_cu    = $_POST['hinh_cu'] ?? 'images/product/default.svg';

    if ($slug === '') $slug = slugify($ten);

    if ($ten === '' || $id_dm <= 0 || $id_nguon <= 0) {
        flash('Vui lòng điền tên, danh mục và nguồn công thức.', 'danger');
    } else {
        $stmt = $pdo->prepare('SELECT id FROM mon_an WHERE slug = ? AND id <> ?');
        $stmt->execute([$slug, $id]);
        if ($stmt->fetch()) {
            flash('Slug món ăn này đã tồn tại.', 'danger');
        } else {
            $hinh = upload_image('hinh_anh', 'monan') ?: $hinh_cu;

            if ($id > 0) {
                $stmt = $pdo->prepare("
                    UPDATE mon_an SET ten=?, slug=?, id_danh_muc=?, id_nguon=?, mo_ta=?, nguyen_lieu=?,
                        calo=?, protein=?, carb=?, fat=?, gia=?, hinh_anh=?, trang_thai=?
                    WHERE id=?
                ");
                $stmt->execute([$ten, $slug, $id_dm, $id_nguon, $mo_ta, $nguyen_lieu,
                    $calo, $protein, $carb, $fat, $gia, $hinh, $trang_thai, $id]);
                flash('Cập nhật món ăn thành công.', 'success');
            } else {
                $stmt = $pdo->prepare("
                    INSERT INTO mon_an
                        (ten, slug, id_danh_muc, id_nguon, mo_ta, nguyen_lieu, calo, protein, carb, fat, gia, hinh_anh, trang_thai)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$ten, $slug, $id_dm, $id_nguon, $mo_ta, $nguyen_lieu,
                    $calo, $protein, $carb, $fat, $gia, $hinh, $trang_thai]);
                flash('Thêm món ăn thành công.', 'success');
            }
            redirect('monan.php');
        }
    }
}

if (($_GET['delete'] ?? '') !== '') {
    $id = (int)$_GET['delete'];
    $stmt = $pdo->prepare('SELECT hinh_anh FROM mon_an WHERE id = ?');
    $stmt->execute([$id]);
    $m = $stmt->fetch();
    $pdo->prepare('DELETE FROM mon_an WHERE id = ?')->execute([$id]);
    if ($m && str_starts_with($m['hinh_anh'], 'images/product/monan_')) {
        @unlink(__DIR__ . '/../' . $m['hinh_anh']);
    }
    flash('Đã xóa món ăn.', 'warning');
    redirect('monan.php');
}

$edit = null;
if (($_GET['edit'] ?? '') !== '') {
    $stmt = $pdo->prepare('SELECT * FROM mon_an WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
}

$rows = $pdo->query("
    SELECT m.*, dm.ten AS danh_muc, nct.ten AS nguon
    FROM mon_an m
    JOIN danh_muc dm ON m.id_danh_muc = dm.id
    JOIN nguon_cong_thuc nct ON m.id_nguon = nct.id
    ORDER BY m.id DESC
")->fetchAll();

$page_title = 'Quản lý món ăn';
include __DIR__ . '/includes/header.php';
?>

<h1 class="admin-page-title">Quản lý món ăn / bữa ăn</h1>

<div class="panel" style="margin-bottom:22px">
    <h2 style="margin-top:0;color:var(--green-dark)"><?= $edit ? 'Sửa món ăn' : 'Thêm món ăn mới' ?></h2>
    <form method="post" action="monan.php" enctype="multipart/form-data">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="save">
        <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
        <input type="hidden" name="hinh_cu" value="<?= e($edit['hinh_anh'] ?? 'images/product/default.svg') ?>">

        <div class="form-row">
            <div class="form-group">
                <label for="ten">Tên món ăn *</label>
                <input type="text" id="ten" name="ten" required value="<?= e($edit['ten'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label for="slug">Slug (để trống tự tạo)</label>
                <input type="text" id="slug" name="slug" value="<?= e($edit['slug'] ?? '') ?>">
            </div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="id_danh_muc">Danh mục *</label>
                <select name="id_danh_muc" id="id_danh_muc" required>
                    <?php foreach ($categories as $c): ?>
                        <option value="<?= (int)$c['id'] ?>" <?= ($edit['id_danh_muc'] ?? 0) == $c['id'] ? 'selected' : '' ?>><?= e($c['ten']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div class="form-group">
                <label for="id_nguon">Nguồn công thức *</label>
                <select name="id_nguon" id="id_nguon" required>
                    <?php foreach ($sources as $s): ?>
                        <option value="<?= (int)$s['id'] ?>" <?= ($edit['id_nguon'] ?? 0) == $s['id'] ? 'selected' : '' ?>><?= e($s['ten']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
        </div>

        <div class="form-group">
            <label for="mo_ta">Mô tả</label>
            <textarea id="mo_ta" name="mo_ta"><?= e($edit['mo_ta'] ?? '') ?></textarea>
        </div>

        <div class="form-group">
            <label for="nguyen_lieu">Nguyên liệu</label>
            <textarea id="nguyen_lieu" name="nguyen_lieu"><?= e($edit['nguyen_lieu'] ?? '') ?></textarea>
        </div>

        <div class="form-row">
            <div class="form-group"><label for="calo">Calo</label><input type="number" id="calo" name="calo" value="<?= e($edit['calo'] ?? 0) ?>" min="0"></div>
            <div class="form-group"><label for="protein">Đạm (g)</label><input type="number" step="0.1" id="protein" name="protein" value="<?= e($edit['protein'] ?? 0) ?>" min="0"></div>
            <div class="form-group"><label for="carb">Tinh bột (g)</label><input type="number" step="0.1" id="carb" name="carb" value="<?= e($edit['carb'] ?? 0) ?>" min="0"></div>
            <div class="form-group"><label for="fat">Chất béo (g)</label><input type="number" step="0.1" id="fat" name="fat" value="<?= e($edit['fat'] ?? 0) ?>" min="0"></div>
        </div>

        <div class="form-row">
            <div class="form-group">
                <label for="gia">Giá (đ)</label>
                <input type="number" id="gia" name="gia" value="<?= e($edit['gia'] ?? 0) ?>" min="0">
            </div>
            <div class="form-group">
                <label for="hinh_anh">Ảnh món ăn (tối đa 2 MB)</label>
                <input type="file" id="hinh_anh" name="hinh_anh" accept="image/*">
                <?php if ($edit): ?>
                    <img class="preview-img" src="<?= e($edit['hinh_anh']) ?>" alt="" style="margin-top:8px">
                <?php endif; ?>
            </div>
        </div>

        <div class="checkbox" style="margin:8px 0 16px">
            <input type="checkbox" id="trang_thai" name="trang_thai" value="1" <?= ($edit['trang_thai'] ?? 1) ? 'checked' : '' ?>>
            <label for="trang_thai" style="margin:0">Hiển thị món ăn trên website</label>
        </div>

        <button type="submit" class="btn btn-primary"><?= $edit ? 'Cập nhật món ăn' : 'Thêm món ăn' ?></button>
        <?php if ($edit): ?><a href="monan.php" class="btn btn-ghost">Hủy</a><?php endif; ?>
    </form>
</div>

<div class="table-wrap">
    <table>
        <thead>
            <tr><th>Ảnh</th><th>Tên món</th><th>Danh mục</th><th>Calo</th><th>Giá</th><th>Trạng thái</th><th>Thao tác</th></tr>
        </thead>
        <tbody>
        <?php foreach ($rows as $r): ?>
            <tr>
                <td><img class="thumb" src="<?= e($r['hinh_anh']) ?>" alt=""></td>
                <td><strong><?= e($r['ten']) ?></strong><br><small style="color:var(--muted)">Nguồn: <?= e($r['nguon']) ?></small></td>
                <td><?= e($r['danh_muc']) ?></td>
                <td><?= (int)$r['calo'] ?></td>
                <td><?= money($r['gia']) ?></td>
                <td><span class="pill"><?= (int)$r['trang_thai'] ? 'Hiện' : 'Ẩn' ?></span></td>
                <td>
                    <div class="action-links">
                        <a href="monan.php?edit=<?= (int)$r['id'] ?>">Sửa</a>
                        <a class="delete" href="monan.php?delete=<?= (int)$r['id'] ?>" data-confirm="Xóa món <?= e($r['ten']) ?>?">Xóa</a>
                    </div>
                </td>
            </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
