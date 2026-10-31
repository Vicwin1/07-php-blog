<?php
declare(strict_types=1);
require dirname(__DIR__) . '/app/bootstrap.php';

if (current_admin() !== null) {
    redirect('admin/index.php');
}
$error = flash('error');
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $email = strtolower(trim((string) ($_POST['email'] ?? '')));
    $password = (string) ($_POST['password'] ?? '');
    $stmt = db()->prepare('SELECT id, name, email, password_hash FROM admins WHERE email = :email LIMIT 1');
    $stmt->execute(['email' => $email]);
    $admin = $stmt->fetch();

    if ($admin && password_verify($password, $admin['password_hash'])) {
        session_regenerate_id(true);
        $_SESSION['admin'] = [
            'id' => (int) $admin['id'],
            'name' => $admin['name'],
            'email' => $admin['email'],
        ];
        $_SESSION['last_activity'] = time();
        unset($_SESSION['_csrf_token']);
        redirect('admin/index.php');
    }
    $error = 'Invalid email or password.';
}
?>
<!doctype html>
<html lang="en">
<head><meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1"><title>Admin login · SWD Blog</title><link rel="stylesheet" href="<?= e(url('assets/style.css')) ?>"></head>
<body class="login-page">
<main class="login-card">
    <a class="brand" href="<?= e(url('index.php')) ?>">SWD Blog</a>
    <h1>Admin sign in</h1>
    <?php if ($error): ?><div class="notice notice-error"><?= e($error) ?></div><?php endif; ?>
    <form method="post" action="<?= e(url('admin/login.php')) ?>">
        <?= csrf_field() ?>
        <label for="email">Email</label><input id="email" name="email" type="email" autocomplete="username" maxlength="160" required value="<?= e($_POST['email'] ?? '') ?>">
        <label for="password">Password</label><input id="password" name="password" type="password" autocomplete="current-password" required>
        <button type="submit">Sign in</button>
    </form>
</main>
</body>
</html>
