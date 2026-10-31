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
$relatedPosts = [];
$readingMinutes = 1;
if ($post) {
    $wordCount = str_word_count(strip_tags($post['body']));
    $readingMinutes = max(1, (int) ceil($wordCount / 200));

    $relatedStmt = db()->prepare(
        "SELECT p.title, p.slug, p.excerpt, p.body, p.cover_image, p.published_at,
                a.name AS author_name
         FROM posts p
         JOIN admins a ON a.id = p.author_id
         WHERE p.category_id = :category_id
           AND p.id != :post_id
           AND p.status = 'published'
         ORDER BY p.published_at DESC, p.id DESC
         LIMIT 3"
    );
    $relatedStmt->execute(['category_id' => $post['category_id'], 'post_id' => $post['id']]);
    $relatedPosts = $relatedStmt->fetchAll();
}
if (!$post) {
    http_response_code(404);
}
?>
<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title><?= $post ? e($post['title']) . ' · Vicwin FC Blog' : 'Post not found · Vicwin FC Blog' ?></title>
    <link rel="stylesheet" href="<?= e(url('assets/style.css')) ?>">
</head>
<body>
<header class="site-header"><a class="brand" href="<?= e(url()) ?>">Vicwin FC Blog</a><nav><a href="<?= e(url()) ?>">Home</a></nav></header>
<main class="container article-container">
    <?php if ($post): ?>
        <article class="article">
            <a class="eyebrow" href="<?= e(url('category/' . rawurlencode($post['category_slug']))) ?>"><?= e($post['category_name']) ?></a>
            <h1><?= e($post['title']) ?></h1>
            <p class="article-meta">By <?= e($post['author_name']) ?> · <time datetime="<?= e(date(DATE_ATOM, strtotime($post['published_at']))) ?>"><?= e(date('M j, Y', strtotime($post['published_at']))) ?></time> · <?= $readingMinutes ?> min read</p>
            <?php if ($post['cover_image']): ?><img class="article-cover" src="<?= e(url($post['cover_image'])) ?>" alt=""><?php endif; ?>
            <div class="article-body"><?= post_body_html($post['body']) ?></div>
        </article>
        <?php if ($relatedPosts): ?>
            <section class="related-section" aria-labelledby="related-title">
                <h2 id="related-title">Related posts</h2>
                <div class="post-grid">
                    <?php foreach ($relatedPosts as $related): ?>
                        <article class="post-card">
                            <?php if ($related['cover_image']): ?><a href="<?= e(url('post/' . rawurlencode($related['slug']))) ?>"><img class="cover" src="<?= e(url($related['cover_image'])) ?>" alt=""></a><?php endif; ?>
                            <div class="post-card-content">
                                <h3><a href="<?= e(url('post/' . rawurlencode($related['slug']))) ?>"><?= e($related['title']) ?></a></h3>
                                <p><?= e($related['excerpt'] ?: excerpt($related['body'])) ?></p>
                                <small>By <?= e($related['author_name']) ?></small>
                            </div>
                        </article>
                    <?php endforeach; ?>
                </div>
            </section>
        <?php endif; ?>
    <?php else: ?>
        <section class="not-found">
            <p class="eyebrow">404 · Not found</p>
            <h1>Post not found</h1>
            <p>This post may have been removed or is not published.</p>
            <a class="button" href="<?= e(url()) ?>">Back to all posts</a>
        </section>
    <?php endif; ?>
</main>
</body>
</html>
