<?php
require_once __DIR__ . '/includes/auth.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save') {
    check_csrf();
    $id    = (int)($_POST['id'] ?? 0);
    $ten   = trim($_POST['ten'] ?? '');
    $slug  = trim($_POST['slug'] ?? '');
    $mo_ta = trim($_POST['mo_ta'] ?? '');

    if ($slug === '') $slug = slugify($ten);

    if ($ten === '' || $slug === '') {
        flash('Tên và slug không được để trống.', 'danger');
    } else {
        $stmt = $pdo->prepare('SELECT id FROM danh_muc WHERE slug = ? AND id <> ?');
        $stmt->execute([$slug, $id]);
        if ($stmt->fetch()) {
            flash('Slug này đã tồn tại.', 'danger');
        } else {
            if ($id > 0) {
                $stmt = $pdo->prepare('UPDATE danh_muc SET ten=?, slug=?, mo_ta=? WHERE id=?');
                $stmt->execute([$ten, $slug, $mo_ta, $id]);
                flash('Cập nhật danh mục thành công.', 'success');
            } else {
                $stmt = $pdo->prepare('INSERT INTO danh_muc (ten, slug, mo_ta) VALUES (?, ?, ?)');
                $stmt->execute([$ten, $slug, $mo_ta]);
                flash('Thêm danh mục thành công.', 'success');
            }
            redirect('danhmuc.php');
        }
    }
}

if (($_GET['delete'] ?? '') !== '') {
    $id = (int)$_GET['delete'];
    $pdo->prepare('DELETE FROM danh_muc WHERE id = ?')->execute([$id]);
    flash('Đã xóa danh mục (và các món thuộc danh mục này).', 'warning');
    redirect('danhmuc.php');
}

$edit = null;
if (($_GET['edit'] ?? '') !== '') {
    $stmt = $pdo->prepare('SELECT * FROM danh_muc WHERE id = ?');
    $stmt->execute([(int)$_GET['edit']]);
    $edit = $stmt->fetch();
}

$rows = $pdo->query('SELECT dm.*, COUNT(m.id) AS so_mon FROM danh_muc dm LEFT JOIN mon_an m ON m.id_danh_muc = dm.id GROUP BY dm.id ORDER BY dm.id')->fetchAll();

$page_title = 'Quản lý danh mục';
include __DIR__ . '/includes/header.php';
?>

<h1 class="admin-page-title">Quản lý danh mục bữa ăn</h1>

<div class="grid grid-2">
    <div class="panel">
        <h2 style="margin-top:0;color:var(--green-dark)"><?= $edit ? 'Sửa danh mục' : 'Thêm danh mục' ?></h2>
        <form method="post" action="danhmuc.php">
            <?= csrf_field() ?>
            <input type="hidden" name="action" value="save">
            <input type="hidden" name="id" value="<?= (int)($edit['id'] ?? 0) ?>">
            <div class="form-group">
                <label for="ten">Tên danh mục *</label>
                <input type="text" id="ten" name="ten" required value="<?= e($edit['ten'] ?? '') ?>" placeholder="Ví dụ: Bữa sáng">
            </div>
            <div class="form-group">
                <label for="slug">Slug (để trống sẽ tự tạo)</label>
                <input type="text" id="slug" name="slug" value="<?= e($edit['slug'] ?? '') ?>" placeholder="bua-sang">
            </div>
            <div class="form-group">
                <label for="mo_ta">Mô tả</label>
                <textarea id="mo_ta" name="mo_ta"><?= e($edit['mo_ta'] ?? '') ?></textarea>
            </div>
            <button type="submit" class="btn btn-primary"><?= $edit ? 'Cập nhật' : 'Thêm mới' ?></button>
            <?php if ($edit): ?><a href="danhmuc.php" class="btn btn-ghost">Hủy</a><?php endif; ?>
        </form>
    </div>

    <div class="table-wrap">
        <table>
            <thead><tr><th>ID</th><th>Tên</th><th>Slug</th><th>Số món</th><th>Thao tác</th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r): ?>
                <tr>
                    <td>#<?= (int)$r['id'] ?></td>
                    <td><strong><?= e($r['ten']) ?></strong><br><small style="color:var(--muted)"><?= e($r['mo_ta']) ?></small></td>
                    <td><code><?= e($r['slug']) ?></code></td>
                    <td><?= (int)$r['so_mon'] ?></td>
                    <td>
                        <div class="action-links">
                            <a href="danhmuc.php?edit=<?= (int)$r['id'] ?>">Sửa</a>
                            <a class="delete" href="danhmuc.php?delete=<?= (int)$r['id'] ?>"
                               data-confirm="Xóa danh mục <?= e($r['ten']) ?>? Các món thuộc danh mục cũng sẽ bị xóa.">Xóa</a>
                        </div>
                    </td>
                </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
