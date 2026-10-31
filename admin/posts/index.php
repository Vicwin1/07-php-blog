<?php
declare(strict_types=1);
require dirname(__DIR__) . '/_layout.php';

$posts = db()->query(
    'SELECT p.id, p.title, p.slug, p.status, p.updated_at, c.name AS category_name
     FROM posts p JOIN categories c ON c.id = p.category_id
     ORDER BY p.updated_at DESC, p.id DESC'
)->fetchAll();
admin_header('Posts');
?>
<div class="page-actions"><a class="button" href="<?= e(url('admin/posts/form.php')) ?>">New post</a></div>
<div class="table-wrap"><table><thead><tr><th>Title</th><th>Category</th><th>Status</th><th>Updated</th><th>Actions</th></tr></thead><tbody>
<?php foreach ($posts as $post): ?>
    <tr>
        <td><?= e($post['title']) ?></td><td><?= e($post['category_name']) ?></td><td><span class="status status-<?= e($post['status']) ?>"><?= e(ucfirst($post['status'])) ?></span></td><td><?= e(date('M j, Y', strtotime($post['updated_at']))) ?></td>
        <td class="table-actions">
            <a href="<?= e(url('admin/posts/form.php?id=' . (int) $post['id'])) ?>">Edit</a>
            <?php if ($post['status'] === 'published'): ?><a href="<?= e(url('post.php?slug=' . rawurlencode($post['slug']))) ?>">View</a><?php endif; ?>
            <form method="post" action="<?= e(url('admin/posts/delete.php')) ?>" onsubmit="return confirm('Delete this post?')"><?= csrf_field() ?><input type="hidden" name="id" value="<?= (int) $post['id'] ?>"><button class="text-danger" type="submit">Delete</button></form>
        </td>
    </tr>
<?php endforeach; ?>
<?php if (!$posts): ?><tr><td colspan="5">No posts yet.</td></tr><?php endif; ?>
</tbody></table></div>
<?php admin_footer(); ?>
