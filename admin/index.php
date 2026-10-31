<?php
declare(strict_types=1);
require __DIR__ . '/_layout.php';

$counts = [
    'posts' => (int) db()->query('SELECT COUNT(*) FROM posts')->fetchColumn(),
    'published' => (int) db()->query("SELECT COUNT(*) FROM posts WHERE status = 'published'")->fetchColumn(),
    'categories' => (int) db()->query('SELECT COUNT(*) FROM categories')->fetchColumn(),
];
admin_header('Dashboard');
?>
<section class="stats-grid">
    <div class="stat-card"><span>All posts</span><strong><?= $counts['posts'] ?></strong></div>
    <div class="stat-card"><span>Published</span><strong><?= $counts['published'] ?></strong></div>
    <div class="stat-card"><span>Categories</span><strong><?= $counts['categories'] ?></strong></div>
</section>
<section class="panel">
    <h2>Get started</h2>
    <p>Manage your blog content from here.</p>
    <a class="button" href="<?= e(url('admin/posts/form.php')) ?>">Write a post</a>
    <a class="button button-secondary" href="<?= e(url('admin/categories/index.php')) ?>">Manage categories</a>
</section>
<?php admin_footer(); ?>
