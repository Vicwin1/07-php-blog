<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

$slug = trim((string) ($_GET['slug'] ?? ''));
$stmt = db()->prepare(
    "SELECT p.*, c.name AS category_name, c.slug AS category_slug, a.name AS author_name
     FROM posts p
     JOIN categories c ON c.id = p.category_id
     JOIN admins a ON a.id = p.author_id
     WHERE p.slug = :slug AND p.status = 'published'
     LIMIT 1"
);
$stmt->execute(['slug' => $slug]);
$post = $stmt->fetch();
if (!$post) {
    http_response_code(404);
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $post ? e($post['title']) . ' · SWD Blog' : 'Post not found · SWD Blog' ?></title>
    <link rel="stylesheet" href="<?= e(url('assets/style.css')) ?>">
</head>
<body>
<header class="site-header"><a class="brand" href="<?= e(url('index.php')) ?>">SWD Blog</a><nav><a href="<?= e(url('index.php')) ?>">Home</a></nav></header>
<main class="container article-container">
    <?php if ($post): ?>
        <article class="article">
            <a class="eyebrow" href="<?= e(url('category.php?slug=' . rawurlencode($post['category_slug']))) ?>"><?= e($post['category_name']) ?></a>
            <h1><?= e($post['title']) ?></h1>
            <p class="article-meta">By <?= e($post['author_name']) ?> · <?= e(date('M j, Y', strtotime($post['published_at']))) ?></p>
            <?php if ($post['cover_image']): ?><img class="article-cover" src="<?= e(url($post['cover_image'])) ?>" alt=""><?php endif; ?>
            <div class="article-body"><?= nl2br(e($post['body'])) ?></div>
        </article>
    <?php else: ?>
        <h1>Post not found</h1>
        <p>This post may have been removed or is not published.</p>
    <?php endif; ?>
</main>
</body>
</html>
