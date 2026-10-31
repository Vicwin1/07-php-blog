<?php
declare(strict_types=1);

function current_admin(): ?array
{
    return isset($_SESSION['admin']) && is_array($_SESSION['admin'])
        ? $_SESSION['admin']
        : null;
}

function require_admin(): void
{
    if (current_admin() === null) {
        flash('error', 'Please sign in to continue.');
        redirect('admin/login.php');
    }
}

function logout_admin(): void
{
    $_SESSION = [];
    if (ini_get('session.use_cookies')) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 42000,
            'path' => $params['path'],
            'domain' => $params['domain'],
            'secure' => $params['secure'],
            'httponly' => $params['httponly'],
            'samesite' => $params['samesite'] ?? 'Lax',
        ]);
    }
    if (session_status() === PHP_SESSION_ACTIVE) {
        session_destroy();
    }
}
