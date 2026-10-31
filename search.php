<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

$query = trim((string) ($_GET['q'] ?? ''));
$posts = [];
if ($query !== '') {
    $stmt = db()->prepare(
        "SELECT p.title, p.slug, p.body, p.excerpt, p.cover_image, p.published_at,
                c.name AS category_name, a.name AS author_name,
                MATCH(p.title, p.body) AGAINST (:query IN NATURAL LANGUAGE MODE) AS relevance
         FROM posts p
         JOIN categories c ON c.id = p.category_id
         JOIN admins a ON a.id = p.author_id
         WHERE p.status = 'published'
           AND MATCH(p.title, p.body) AGAINST (:query2 IN NATURAL LANGUAGE MODE)
         ORDER BY relevance DESC, p.published_at DESC
         LIMIT 50"
    );
    $stmt->execute(['query' => $query, 'query2' => $query]);
    $posts = $stmt->fetchAll();
}
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Search · SWD Blog</title><link rel="stylesheet" href="<?= e(url('assets/style.css')) ?>"></head>
<body>
<header class="site-header"><a class="brand" href="<?= e(url('index.php')) ?>">SWD Blog</a><nav><a href="<?= e(url('index.php')) ?>">Home</a></nav></header>
<main class="container">
    <h1>Search posts</h1>
    <form class="search-form" action="<?= e(url('search.php')) ?>" method="get"><label class="sr-only" for="q">Search posts</label><input id="q" name="q" type="search" value="<?= e($query) ?>" placeholder="Search posts…"><button type="submit">Search</button></form>
    <?php if ($query !== ''): ?>
        <p>Results for “<?= e($query) ?>”</p>
        <section class="post-grid">
            <?php foreach ($posts as $post): ?>
                <article class="post-card">
                    <?php if ($post['cover_image']): ?><a href="<?= e(url('post.php?slug=' . rawurlencode($post['slug']))) ?>"><img class="cover" src="<?= e(url($post['cover_image'])) ?>" alt=""></a><?php endif; ?>
                    <div class="post-card-content"><span class="eyebrow"><?= e($post['category_name']) ?></span><h2><a href="<?= e(url('post.php?slug=' . rawurlencode($post['slug']))) ?>"><?= e($post['title']) ?></a></h2><p><?= e($post['excerpt'] ?: excerpt($post['body'])) ?></p><small>By <?= e($post['author_name']) ?></small></div>
                </article>
            <?php endforeach; ?>
            <?php if (!$posts): ?><p>No matching posts found.</p><?php endif; ?>
        </section>
    <?php endif; ?>
</main>
</body>
</html>
