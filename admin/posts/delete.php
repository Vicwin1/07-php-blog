<?php
declare(strict_types=1);
require dirname(__DIR__) . '/_layout.php';
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    header('Allow: POST');
    exit('Method not allowed.');
}
verify_csrf();
$id = filter_input(INPUT_POST, 'id', FILTER_VALIDATE_INT);
if (!$id) {
    http_response_code(400);
    exit('Invalid post.');
}
$stmt = db()->prepare('SELECT cover_image FROM posts WHERE id = :id');
$stmt->execute(['id' => $id]);
$post = $stmt->fetch();
if ($post) {
    db()->prepare('DELETE FROM posts WHERE id = :id')->execute(['id' => $id]);
    remove_cover_image($post['cover_image']);
    flash('success', 'Post deleted.');
}
redirect('admin/posts/index.php');
