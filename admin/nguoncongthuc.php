<?php
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    check_csrf();
    $id    = (int)($_POST['id'] ?? 0);
    $ten   = trim($_POST['ten'] ?? '');
    $mo_ta = trim($_POST['mo_ta'] ?? '');

    if ($ten === '') {
        flash('Tên nguồn công thức không được để trống.', 'danger');
    } else {
        if ($id > 0) {
            $pdo->prepare('UPDATE nguon_cong_thuc SET ten=?, mo_ta=? WHERE id=?')->execute([$ten, $mo_ta, $id]);
            flash('Cập nhật nguồn công thức thành công.', 'success');
        } else {
            $pdo->prepare('INSERT INTO nguon_cong_thuc (ten, mo_ta) VALUES (?, ?)')->execute([$ten, $mo_ta]);
            flash('Thêm nguồn công thức thành công.', 'success');
        }
        redirect('nguoncongthuc.php');
    }
}

if (($_GET['delete'] ?? '') !== '') {
    $pdo->prepare('DELETE FROM nguon_cong_thuc WHERE id = ?')->execute([(int)$_GET['delete']]);
    flash('Đã xóa nguồn công thức.', 'warning');
    redirect('nguoncongthuc.php');
}

$edit = null;
if (($_GET['edit'] ?? '') !== '') {
    $stmt = $pdo->prepare('SELECT * FROM nguon_cong_thuc WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
}

$rows = $pdo->query('SELECT nct.*, COUNT(m.id) AS so_mon FROM nguon_cong_thuc nct LEFT JOIN mon_an m ON m.id_nguon = nct.id GROUP BY nct.id ORDER BY nct.id')->fetchAll();

$page_title = 'Quản lý nguồn công thức';
include __DIR__ . '/includes/header.php';
?>

<h1 class="admin-page-title">Quản lý nguồn công thức</h1>

<div class="grid grid-2">
    <div class="panel">
        <h2 style="margin-top:0;color:var(--green-dark)"><?= $edit ? 'Sửa nguồn công thức' : 'Thêm nguồn công thức' ?></h2>
        <form method="post" action="nguoncongthuc.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
            <div class="form-group">
                <label for="ten">Tên nguồn *</label>
                <input type="text" id="ten" name="ten" required value="<?= e($edit['ten'] ?? '') ?>" placeholder="Ví dụ: Bếp ăn Healthy">
            </div>
            <div class="form-group">
                <label for="mo_ta">Mô tả</label>
                <textarea id="mo_ta" name="mo_ta"><?= e($edit['mo_ta'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary"><?= $edit ? 'Cập nhật' : 'Thêm mới' ?></button>
            <?php if ($edit): ?><a href="nguoncongthuc.php" class="btn btn-ghost">Hủy</a><?php endif; ?>
        </form>
    </div>

    <div class="table-wrap">
        <table>
            <thead><tr><th>ID</th><th>Tên</th><th>Số món</th><th>Thao tác</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td>#<?= (int)$r['id'] ?></td>
                    <td><strong><?= e($r['ten']) ?></strong><br><small style="color:var(--muted)"><?= e($r['mo_ta']) ?></small></td>
                    <td><?= (int)$r['so_mon'] ?></td>
                    <td>
                        <div class="action-links">
                            <a href="nguoncongthuc.php?edit=<?= (int)$r['id'] ?>">Sửa</a>
                            <a class="delete" href="nguoncongthuc.php?delete=<?= (int)$r['id'] ?>"
                               data-confirm="Xóa nguồn công thức này?">Xóa</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
