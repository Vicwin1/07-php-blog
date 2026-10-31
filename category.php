<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
$categoryStmt = db()->prepare('SELECT id, name, slug FROM categories WHERE slug = :slug LIMIT 1');
$categoryStmt->execute(['slug' => $slug]);
$category = $categoryStmt->fetch();
$posts = [];
if ($category) {
    $stmt = db()->prepare(
        "SELECT p.title, p.slug, p.body, p.excerpt, p.cover_image, p.published_at, a.name AS author_name
         FROM posts p JOIN admins a ON a.id = p.author_id
         WHERE p.category_id = :category_id AND p.status = 'published'
         ORDER BY p.published_at DESC, p.id DESC"
    );
    $stmt->execute(['category_id' => $category['id']]);
    $posts = $stmt->fetchAll();
} else {
    http_response_code(404);
}
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title><?= $category ? e($category['name']) . ' · Vicwin FC Blog' : 'Category not found' ?></title><link rel="stylesheet" href="<?= e(url('assets/style.css')) ?>"></head>
<body>
<header class="site-header"><a class="brand" href="<?= e(url()) ?>">Vicwin FC Blog</a><nav><a href="<?= e(url()) ?>">Home</a></nav></header>
<main class="container">
    <h1><?= $category ? e($category['name']) : 'Category not found' ?></h1>
    <?php if ($category): ?>
        <section class="post-grid">
            <?php foreach ($posts as $post): ?>
                <article class="post-card">
                    <?php if ($post['cover_image']): ?><a href="<?= e(url('post/' . rawurlencode($post['slug']))) ?>"><img class="cover" src="<?= e(url($post['cover_image'])) ?>" alt=""></a><?php endif; ?>
                    <div class="post-card-content"><h2><a href="<?= e(url('post/' . rawurlencode($post['slug']))) ?>"><?= e($post['title']) ?></a></h2><p><?= e($post['excerpt'] ?: excerpt($post['body'])) ?></p><small>By <?= e($post['author_name']) ?> · <time datetime="<?= e(date(DATE_ATOM, strtotime($post['published_at']))) ?>"><?= e(date('M j, Y', strtotime($post['published_at']))) ?></time></small></div>
                </article>
            <?php endforeach; ?>
            <?php if (!$posts): ?><p>No published posts in this category yet.</p><?php endif; ?>
        </section>
    <?php endif; ?>
</main>
</body>
</html>
