<?php
declare(strict_types=1);

if (PHP_SAPI !== 'cli') {
    http_response_code(404);
    exit;
}
require __DIR__ . '/app/db.php';

if ($argc < 3 || $argc > 3) {
    fwrite(STDERR, "Usage: php create-admin.php \"Your Name\" you@example.com\n");
    exit(1);
}
$name = trim($argv[1]);
$email = strtolower(trim($argv[2]));
if ($name === '' || mb_strlen($name, 'UTF-8') > 100 || mb_strlen($email, 'UTF-8') > 160 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    fwrite(STDERR, "Provide a name (up to 100 characters) and a valid email address (up to 160 characters).\n");
    exit(1);
}

fwrite(STDOUT, "Password (minimum 12 characters): ");
$password = trim((string) fgets(STDIN));
fwrite(STDOUT, "Confirm password: ");
$confirmation = trim((string) fgets(STDIN));
if (strlen($password) < 12 || !hash_equals($password, $confirmation)) {
    fwrite(STDERR, "Passwords must match and be at least 12 characters long.\n");
    exit(1);
}

try {
    $stmt = db()->prepare('INSERT INTO admins (name, email, password_hash) VALUES (:name, :email, :password_hash)');
    $stmt->execute([
        'name' => $name,
        'email' => $email,
        'password_hash' => password_hash($password, PASSWORD_DEFAULT),
    ]);
    fwrite(STDOUT, "Admin account created for {$email}.\n");
} catch (PDOException $exception) {
    if ((string) $exception->getCode() === '23000') {
        fwrite(STDERR, "An admin with that email already exists.\n");
        exit(1);
    }
    fwrite(STDERR, "Could not create admin: " . $exception->getMessage() . "\n");
    exit(1);
}
