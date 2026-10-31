<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

$stmt = db()->query(
    "SELECT p.id, p.title, p.slug, p.body, p.excerpt, p.cover_image, p.published_at,
            c.name AS category_name, c.slug AS category_slug, a.name AS author_name
     FROM posts p
     JOIN categories c ON c.id = p.category_id
     JOIN admins a ON a.id = p.author_id
     WHERE p.status = 'published'
     ORDER BY p.published_at DESC, p.id DESC
     LIMIT 20"
);
$posts = $stmt->fetchAll();
$categories = db()->query('SELECT name, slug FROM categories ORDER BY name')->fetchAll();
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>SWD Blog</title>
    <link rel="stylesheet" href="<?= e(url('assets/style.css')) ?>">
</head>
<body>
<header class="site-header">
    <a class="brand" href="<?= e(url('index.php')) ?>">SWD Blog</a>
    <nav aria-label="Main navigation">
        <?php foreach ($categories as $category): ?>
            <a href="<?= e(url('category.php?slug=' . rawurlencode($category['slug']))) ?>"><?= e($category['name']) ?></a>
        <?php endforeach; ?>
        <a href="<?= e(url('admin/login.php')) ?>">Admin</a>
    </nav>
</header>
<main class="container">
    <section class="hero">
        <h1>Stories and ideas</h1>
        <p>Thoughtful articles from our team.</p>
        <form class="search-form" action="<?= e(url('search.php')) ?>" method="get">
            <label class="sr-only" for="q">Search posts</label>
            <input id="q" name="q" type="search" placeholder="Search posts…" value="<?= e($_GET['q'] ?? '') ?>">
            <button type="submit">Search</button>
        </form>
    </section>
    <section class="post-grid" aria-label="Latest posts">
        <?php foreach ($posts as $post): ?>
            <article class="post-card">
                <?php if ($post['cover_image']): ?>
                    <a href="<?= e(url('post.php?slug=' . rawurlencode($post['slug']))) ?>">
                        <img class="cover" src="<?= e(url($post['cover_image'])) ?>" alt="">
                    </a>
                <?php endif; ?>
                <div class="post-card-content">
                    <a class="eyebrow" href="<?= e(url('category.php?slug=' . rawurlencode($post['category_slug']))) ?>"><?= e($post['category_name']) ?></a>
                    <h2><a href="<?= e(url('post.php?slug=' . rawurlencode($post['slug']))) ?>"><?= e($post['title']) ?></a></h2>
                    <p><?= e($post['excerpt'] ?: excerpt($post['body'])) ?></p>
                    <small>By <?= e($post['author_name']) ?> · <?= e(date('M j, Y', strtotime($post['published_at']))) ?></small>
                </div>
            </article>
        <?php endforeach; ?>
        <?php if (!$posts): ?><p>No published posts yet.</p><?php endif; ?>
    </section>
</main>
<footer class="site-footer">© <?= date('Y') ?> SWD Blog</footer>
</body>
</html>
