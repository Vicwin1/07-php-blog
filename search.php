<?php
declare(strict_types=1);
require __DIR__ . '/app/bootstrap.php';

$query = trim((string) ($_GET['q'] ?? ''));
$posts = [];
if ($query !== '') {
    $baseQuery = "SELECT p.title, p.slug, p.body, p.excerpt, p.cover_image, p.published_at,
                         c.name AS category_name, c.slug AS category_slug, a.name AS author_name";
    if (mb_strlen($query, 'UTF-8') < 4) {
        $likeQuery = strtr($query, ['!' => '!!', '%' => '!%', '_' => '!_']);
        $stmt = db()->prepare(
            $baseQuery . " FROM posts p
             JOIN categories c ON c.id = p.category_id
             JOIN admins a ON a.id = p.author_id
             WHERE p.status = 'published'
               AND (p.title LIKE :title ESCAPE '!' OR p.body LIKE :body ESCAPE '!')
             ORDER BY p.published_at DESC, p.id DESC LIMIT 50"
        );
        $stmt->execute(['title' => '%' . $likeQuery . '%', 'body' => '%' . $likeQuery . '%']);
    } else {
        $stmt = db()->prepare(
            $baseQuery . ", MATCH(p.title, p.body) AGAINST (:relevance IN NATURAL LANGUAGE MODE) AS relevance
             FROM posts p
             JOIN categories c ON c.id = p.category_id
             JOIN admins a ON a.id = p.author_id
             WHERE p.status = 'published'
               AND MATCH(p.title, p.body) AGAINST (:query IN NATURAL LANGUAGE MODE)
             ORDER BY relevance DESC, p.published_at DESC, p.id DESC LIMIT 50"
        );
        $stmt->execute(['relevance' => $query, 'query' => $query]);
    }
    $posts = $stmt->fetchAll();
}
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Search · SWD Blog</title><link rel="stylesheet" href="<?= e(url('assets/style.css')) ?>"></head>
<body>
<header class="site-header"><a class="brand" href="<?= e(url()) ?>">SWD Blog</a><nav><a href="<?= e(url()) ?>">Home</a></nav></header>
<main class="container">
    <h1>Search posts</h1>
    <form class="search-form" action="<?= e(url('search.php')) ?>" method="get"><label class="sr-only" for="q">Search posts</label><input id="q" name="q" type="search" value="<?= e($query) ?>" placeholder="Search posts…"><button type="submit">Search</button></form>
    <?php if ($query !== ''): ?>
        <p>Results for “<?= e($query) ?>”</p>
        <section class="post-grid">
            <?php foreach ($posts as $post): ?>
                <article class="post-card">
                    <?php if ($post['cover_image']): ?><a href="<?= e(url('post/' . rawurlencode($post['slug']))) ?>"><img class="cover" src="<?= e(url($post['cover_image'])) ?>" alt=""></a><?php endif; ?>
                    <div class="post-card-content"><a class="eyebrow" href="<?= e(url('category/' . rawurlencode($post['category_slug']))) ?>"><?= e($post['category_name']) ?></a><h2><a href="<?= e(url('post/' . rawurlencode($post['slug']))) ?>"><?= e($post['title']) ?></a></h2><p><?= e($post['excerpt'] ?: excerpt($post['body'])) ?></p><small>By <?= e($post['author_name']) ?> · <time datetime="<?= e(date(DATE_ATOM, strtotime($post['published_at']))) ?>"><?= e(date('M j, Y', strtotime($post['published_at']))) ?></time></small></div>
                </article>
            <?php endforeach; ?>
            <?php if (!$posts): ?><p>No matching posts found.</p><?php endif; ?>
        </section>
    <?php endif; ?>
</main>
</body>
</html>
