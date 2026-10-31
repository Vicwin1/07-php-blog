<?php
declare(strict_types=1);
require __DIR__ . '/_layout.php';

$summaryRows = db()->query(
    "SELECT 'summary' AS row_type, NULL AS category_id, NULL AS category_name,
            COUNT(*) AS post_count,
            COALESCE(SUM(status = 'published'), 0) AS published_count,
            COALESCE(SUM(status = 'draft'), 0) AS draft_count,
            (SELECT COUNT(*) FROM categories) AS category_count
     FROM posts
     UNION ALL
     SELECT 'category' AS row_type, c.id AS category_id, c.name AS category_name,
            COUNT(p.id) AS post_count,
            0 AS published_count, 0 AS draft_count, 0 AS category_count
     FROM categories c
     LEFT JOIN posts p ON p.category_id = c.id
     GROUP BY c.id, c.name
     ORDER BY row_type, category_name"
)->fetchAll();

$summary = ['post_count' => 0, 'published_count' => 0, 'draft_count' => 0, 'category_count' => 0];
$categoryCounts = [];
foreach ($summaryRows as $row) {
    if ($row['row_type'] === 'summary') {
        $summary = $row;
    } else {
        $categoryCounts[] = $row;
    }
}

$latestPosts = db()->query(
    'SELECT p.id, p.title, p.slug, p.status, p.created_at, c.name AS category_name
     FROM posts p
     JOIN categories c ON c.id = p.category_id
     ORDER BY p.created_at DESC, p.id DESC
     LIMIT 5'
)->fetchAll();

admin_header('Dashboard');
?>
<section class="stats-grid">
    <div class="stat-card"><span>All posts</span><strong><?= (int) $summary['post_count'] ?></strong></div>
    <div class="stat-card"><span>Published posts</span><strong><?= (int) $summary['published_count'] ?></strong></div>
    <div class="stat-card"><span>Draft posts</span><strong><?= (int) $summary['draft_count'] ?></strong></div>
    <div class="stat-card"><span>Categories</span><strong><?= (int) $summary['category_count'] ?></strong></div>
</section>
<section class="dashboard-grid">
    <div class="panel">
        <div class="panel-heading"><h2>Latest posts</h2><a href="<?= e(url('admin/posts/index.php')) ?>">All posts</a></div>
        <?php if ($latestPosts): ?>
            <div class="table-wrap dashboard-table"><table>
                <thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Created</th></tr></thead>
                <tbody>
                <?php foreach ($latestPosts as $post): ?>
                    <tr>
                        <td><a href="<?= e(url('admin/posts/form.php?id=' . (int) $post['id'])) ?>"><?= e($post['title']) ?></a></td>
                        <td><?= e($post['category_name']) ?></td>
                        <td><span class="status status-<?= e($post['status']) ?>"><?= e(ucfirst($post['status'])) ?></span></td>
                        <td><?= e(date('M j, Y', strtotime($post['created_at']))) ?></td>
                    </tr>
                <?php endforeach; ?>
                </tbody>
            </table></div>
        <?php else: ?>
            <p>No posts yet. <a href="<?= e(url('admin/posts/form.php')) ?>">Write the first post</a>.</p>
        <?php endif; ?>
    </div>
    <div class="panel">
        <div class="panel-heading"><h2>Posts by category</h2><a href="<?= e(url('admin/categories/index.php')) ?>">Manage</a></div>
        <?php if ($categoryCounts): ?>
            <ul class="category-count-list">
                <?php foreach ($categoryCounts as $category): ?>
                    <li><span><?= e($category['category_name']) ?></span><strong><?= (int) $category['post_count'] ?></strong></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No categories yet. <a href="<?= e(url('admin/categories/index.php')) ?>">Create a category</a>.</p>
        <?php endif; ?>
    </div>
</section>
<section class="panel">
    <h2>Get started</h2>
    <p>Manage your blog content from here.</p>
    <a class="button" href="<?= e(url('admin/posts/form.php')) ?>">Write a post</a>
    <a class="button button-secondary" href="<?= e(url('admin/categories/index.php')) ?>">Manage categories</a>
</section>
<?php admin_footer(); ?>
