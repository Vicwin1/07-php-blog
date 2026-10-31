<?php
declare(strict_types=1);
require dirname(__DIR__) . '/_layout.php';

$error = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $action = (string) ($_POST['action'] ?? '');
    $id = filter_var($_POST['id'] ?? null, FILTER_VALIDATE_INT) ?: 0;
    if ($action === 'delete' && $id > 0) {
        try {
            db()->prepare('DELETE FROM categories WHERE id = :id')->execute(['id' => $id]);
            flash('success', 'Category deleted.');
            redirect('admin/categories/index.php');
        } catch (PDOException $exception) {
            if ((string) $exception->getCode() === '23000') {
                $error = 'This category has posts. Move or delete those posts before deleting the category.';
            } else {
                error_log('Category delete failed: ' . $exception->getMessage());
                $error = 'The category could not be deleted.';
            }
        }
    } elseif (in_array($action, ['create', 'update'], true)) {
        $name = trim((string) ($_POST['name'] ?? ''));
        $slug = slugify((string) ($_POST['slug'] ?? '') ?: $name);
        if ($name === '' || mb_strlen($name, 'UTF-8') > 80 || $slug === '' || mb_strlen($slug, 'UTF-8') > 90) {
            $error = 'Enter a category name (up to 80 characters) and a valid slug.';
        } else {
            if ($action === 'update' && $id < 1) {
                $error = 'Choose a valid category.';
            } else {
                try {
                    if ($action === 'update') {
                        db()->prepare('UPDATE categories SET name = :name, slug = :slug WHERE id = :id')
                            ->execute(['name' => $name, 'slug' => $slug, 'id' => $id]);
                    } else {
                        db()->prepare('INSERT INTO categories (name, slug) VALUES (:name, :slug)')
                            ->execute(['name' => $name, 'slug' => $slug]);
                    }
                    flash('success', $action === 'update' ? 'Category updated.' : 'Category created.');
                    redirect('admin/categories/index.php');
                } catch (PDOException $exception) {
                    if ((string) $exception->getCode() === '23000') {
                        $error = 'That category name or slug is already in use.';
                    } else {
                        error_log('Category save failed: ' . $exception->getMessage());
                        $error = 'The category could not be saved.';
                    }
                }
            }
        }
    } else {
        $error = 'Invalid category action.';
    }
}
$categories = db()->query(
    'SELECT c.id, c.name, c.slug, COUNT(p.id) AS post_count
     FROM categories c LEFT JOIN posts p ON p.category_id = c.id
     GROUP BY c.id ORDER BY c.name'
)->fetchAll();
admin_header('Categories');
?>
<?php if ($error): ?><div class="notice notice-error"><?= e($error) ?></div><?php endif; ?>
<section class="panel">
    <h2>Add category</h2>
    <form class="inline-form" method="post">
        <?= csrf_field() ?><input type="hidden" name="action" value="create">
        <label for="new-name">Name</label><input id="new-name" name="name" maxlength="80" required>
        <label for="new-slug">Slug <span class="muted">(optional)</span></label><input id="new-slug" name="slug" maxlength="90">
        <button type="submit">Add category</button>
    </form>
</section>
<div class="table-wrap"><table><thead><tr><th>Name</th><th>Slug</th><th>Posts</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($categories as $category): ?>
    <tr>
        <td colspan="3">
            <form class="category-edit" method="post">
                <?= csrf_field() ?><input type="hidden" name="action" value="update"><input type="hidden" name="id" value="<?= (int) $category['id'] ?>">
                <input aria-label="Category name" name="name" maxlength="80" required value="<?= e($category['name']) ?>">
                <input aria-label="Category slug" name="slug" maxlength="90" required value="<?= e($category['slug']) ?>">
                <span><?= (int) $category['post_count'] ?></span>
                <button type="submit">Save</button>
            </form>
        </td>
        <td><form method="post" onsubmit="return confirm('Delete this category?')"><?= csrf_field() ?><input type="hidden" name="action" value="delete"><input type="hidden" name="id" value="<?= (int) $category['id'] ?>"><button class="text-danger" type="submit">Delete</button></form></td>
    </tr>
<?php endforeach; ?>
<?php if (!$categories): ?><tr><td colspan="4">No categories yet.</td></tr><?php endif; ?>
</tbody></table></div>
<?php admin_footer(); ?>
