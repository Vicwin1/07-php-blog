<?php
declare(strict_types=1);
require dirname(__DIR__) . '/_layout.php';

$id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT) ?: 0;
$stmt = db()->prepare('SELECT * FROM posts WHERE id = :id LIMIT 1');
$stmt->execute(['id' => $id]);
$post = $id ? $stmt->fetch() : false;
if ($id && !$post) {
    http_response_code(404);
    exit('Post not found.');
}
$categories = db()->query('SELECT id, name FROM categories ORDER BY name')->fetchAll();
$values = $post ?: ['title' => '', 'slug' => '', 'body' => '', 'status' => 'draft', 'category_id' => '', 'cover_image' => null];
$error = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $values = array_merge($values, [
        'title' => trim((string) ($_POST['title'] ?? '')),
        'slug' => trim((string) ($_POST['slug'] ?? '')),
        'body' => trim((string) ($_POST['body'] ?? '')),
        'status' => (string) ($_POST['status'] ?? 'draft'),
        'category_id' => (string) ($_POST['category_id'] ?? ''),
    ]);
    $categoryId = filter_var($values['category_id'], FILTER_VALIDATE_INT);
    $title = $values['title'];
    $slug = slugify($values['slug'] !== '' ? $values['slug'] : $title);

    if ($title === '' || mb_strlen($title, 'UTF-8') > 200) {
        $error = 'Enter a title of 1–200 characters.';
    } elseif ($slug === '' || mb_strlen($slug, 'UTF-8') > 220) {
        $error = 'Enter a title or slug that produces a valid URL slug.';
    } elseif ($values['body'] === '') {
        $error = 'Post content cannot be empty.';
    } elseif (!$categoryId) {
        $error = 'Choose a category.';
    } elseif (!in_array($values['status'], ['draft', 'published'], true)) {
        $error = 'Choose a valid post status.';
    } elseif (!$categories) {
        $error = 'Create a category before writing a post.';
    } else {
        $check = db()->prepare('SELECT id FROM posts WHERE slug = :slug AND id != :id LIMIT 1');
        $check->execute(['slug' => $slug, 'id' => $id]);
        if ($check->fetch()) {
            $error = 'That slug is already in use.';
        } else {
            $categoryCheck = db()->prepare('SELECT id FROM categories WHERE id = :id');
            $categoryCheck->execute(['id' => $categoryId]);
            if (!$categoryCheck->fetch()) {
                $error = 'Choose an existing category.';
            } else {
                try {
                    $newImage = store_cover_image($_FILES['cover_image'] ?? []);
                    $image = $newImage ?? ($post['cover_image'] ?? null);
                    if ($id) {
                        $save = db()->prepare(
                            "UPDATE posts SET category_id = :category_id, title = :title, slug = :slug,
                             excerpt = :excerpt, body = :body, status = :status, cover_image = :cover_image,
                             published_at = CASE WHEN :published = 'published' THEN COALESCE(published_at, CURRENT_TIMESTAMP) ELSE NULL END
                             WHERE id = :id"
                        );
                        $save->execute([
                            'category_id' => $categoryId, 'title' => $title, 'slug' => $slug,
                            'excerpt' => excerpt($values['body'], 299), 'body' => $values['body'], 'status' => $values['status'],
                            'cover_image' => $image, 'published' => $values['status'], 'id' => $id,
                        ]);
                    } else {
                        $save = db()->prepare(
                            "INSERT INTO posts (category_id, author_id, title, slug, excerpt, body, status, cover_image, published_at)
                             VALUES (:category_id, :author_id, :title, :slug, :excerpt, :body, :status, :cover_image,
                             IF(:published = 'published', CURRENT_TIMESTAMP, NULL))"
                        );
                        $save->execute([
                            'category_id' => $categoryId, 'author_id' => current_admin()['id'],
                            'title' => $title, 'slug' => $slug, 'excerpt' => excerpt($values['body'], 299), 'body' => $values['body'],
                            'status' => $values['status'], 'cover_image' => $image, 'published' => $values['status'],
                        ]);
                    }
                    if ($newImage && !empty($post['cover_image'])) {
                        remove_cover_image($post['cover_image']);
                    }
                    flash('success', $id ? 'Post updated.' : 'Post created.');
                    redirect('admin/posts/index.php');
                } catch (RuntimeException $exception) {
                    $error = $exception->getMessage();
                } catch (PDOException $exception) {
                    if (isset($newImage)) {
                        remove_cover_image($newImage);
                    }
                    error_log('Post save failed: ' . $exception->getMessage());
                    $error = 'The post could not be saved. Check the slug and try again.';
                }
            }
        }
    }
}
admin_header($id ? 'Edit post' : 'New post');
?>
<?php if ($error): ?><div class="notice notice-error"><?= e($error) ?></div><?php endif; ?>
<?php if (!$categories): ?><div class="notice notice-error">You need a category before adding posts. <a href="<?= e(url('admin/categories/index.php')) ?>">Create one now</a>.</div><?php endif; ?>
<form class="panel editor-form" method="post" enctype="multipart/form-data">
    <?= csrf_field() ?>
    <label for="title">Title</label><input id="title" name="title" maxlength="200" required value="<?= e($values['title']) ?>">
    <label for="slug">Slug <span class="muted">(optional; generated from title if blank)</span></label><input id="slug" name="slug" maxlength="220" value="<?= e($values['slug']) ?>">
    <label for="category_id">Category</label><select id="category_id" name="category_id" required><option value="">Choose a category</option><?php foreach ($categories as $category): ?><option value="<?= (int) $category['id'] ?>" <?= (string) $values['category_id'] === (string) $category['id'] ? 'selected' : '' ?>><?= e($category['name']) ?></option><?php endforeach; ?></select>
    <label for="body">Content</label><textarea id="body" name="body" rows="18" required><?= e($values['body']) ?></textarea>
    <label for="status">Status</label><select id="status" name="status"><option value="draft" <?= $values['status'] === 'draft' ? 'selected' : '' ?>>Draft</option><option value="published" <?= $values['status'] === 'published' ? 'selected' : '' ?>>Published</option></select>
    <label for="cover_image">Cover image <span class="muted">(JPEG, PNG or WebP, max 5 MB)</span></label><input id="cover_image" name="cover_image" type="file" accept="image/jpeg,image/png,image/webp">
    <?php if (!empty($values['cover_image'])): ?><p>Current image: <img class="cover-preview" src="<?= e(url($values['cover_image'])) ?>" alt=""></p><?php endif; ?>
    <div class="form-actions"><button type="submit">Save post</button><a class="button button-secondary" href="<?= e(url('admin/posts/index.php')) ?>">Cancel</a></div>
</form>
<?php admin_footer(); ?>
