<?php
declare(strict_types=1);
require_once dirname(__DIR__) . '/app/bootstrap.php';
require_admin();

function admin_header(string $title): void
{
    $admin = current_admin();
    ?>
    <!doctype html>
    <html lang="en">
    <head>
        <meta charset="utf-8">
        <meta name="viewport" content="width=device-width, initial-scale=1">
        <title><?= e($title) ?> · SWD Blog Admin</title>
        <link rel="stylesheet" href="<?= e(url('assets/style.css')) ?>">
    </head>
    <body class="admin-body">
    <aside class="admin-sidebar">
        <a class="admin-brand" href="<?= e(url('admin/index.php')) ?>">SWD Blog</a>
        <p class="sidebar-user"><?= e($admin['name'] ?? '') ?></p>
        <nav aria-label="Admin navigation">
            <a href="<?= e(url('admin/index.php')) ?>">Dashboard</a>
            <a href="<?= e(url('admin/posts/index.php')) ?>">Posts</a>
            <a href="<?= e(url('admin/categories/index.php')) ?>">Categories</a>
            <a href="<?= e(url('index.php')) ?>">View site</a>
        </nav>
        <form action="<?= e(url('admin/logout.php')) ?>" method="post" class="sidebar-logout">
            <?= csrf_field() ?>
            <button class="link-button" type="submit">Logout</button>
        </form>
    </aside>
    <main class="admin-main">
        <header class="admin-topbar"><h1><?= e($title) ?></h1></header>
        <?php if ($message = flash('success')): ?><div class="notice notice-success"><?= e($message) ?></div><?php endif; ?>
        <?php if ($message = flash('error')): ?><div class="notice notice-error"><?= e($message) ?></div><?php endif; ?>
    <?php
}

function admin_footer(): void
{
    ?>
    </main>
    </body>
    </html>
    <?php
}
