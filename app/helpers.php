<?php
declare(strict_types=1);

function base_path(): string
{
    $script = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $base = preg_replace('~/(?:admin/.*|[^/]+\.php)$~', '', $script) ?? '';
    return rtrim($base, '/');
}

function url(string $path = ''): string
{
    return base_path() . '/' . ltrim($path, '/');
}

function e(?string $value): string
{
    return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
}

function redirect(string $path): void
{
    header('Location: ' . url($path), true, 303);
    exit;
}

function flash(string $key, ?string $message = null): ?string
{
    if ($message !== null) {
        $_SESSION['_flash'][$key] = $message;
        return null;
    }
    $value = $_SESSION['_flash'][$key] ?? null;
    unset($_SESSION['_flash'][$key]);
    return is_string($value) ? $value : null;
}

function slugify(string $title): string
{
    $text = trim(mb_strtolower($title, 'UTF-8'));
    $ascii = iconv('UTF-8', 'ASCII//TRANSLIT//IGNORE', $text);
    $slug = strtolower((string) $ascii);
    $slug = preg_replace('/[^a-z0-9]+/', '-', $slug) ?? '';
    return trim($slug, '-');
}

function unique_post_slug(string $title, int $excludeId = 0): string
{
    $base = slugify($title);
    if ($base === '') {
        return '';
    }

    $check = db()->prepare('SELECT id FROM posts WHERE slug = :slug AND id != :id LIMIT 1');
    $slug = mb_substr($base, 0, 220, 'UTF-8');
    $suffix = 1;
    do {
        $check->execute(['slug' => $slug, 'id' => $excludeId]);
        if (!$check->fetch()) {
            return $slug;
        }

        $suffix++;
        $ending = '-' . $suffix;
        $slug = rtrim(mb_substr($base, 0, 220 - strlen($ending), 'UTF-8'), '-') . $ending;
    } while (true);
}

function excerpt(string $text, int $length = 180): string
{
    $plain = trim(strip_tags($text));
    if (mb_strlen($plain, 'UTF-8') <= $length) {
        return $plain;
    }
    return rtrim(mb_substr($plain, 0, $length, 'UTF-8')) . '…';
}

function post_body_html(string $body): string
{
    $paragraphs = preg_split('/\R(?:[ \t]*\R)+/u', trim($body)) ?: [];
    $paragraphs = array_filter($paragraphs, static fn (string $paragraph): bool => trim($paragraph) !== '');

    return implode("\n", array_map(
        static fn (string $paragraph): string => '<p>' . nl2br(e(trim($paragraph)), false) . '</p>',
        $paragraphs
    ));
}

function store_cover_image(array $file): ?string
{
    if (($file['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
        return null;
    }
    if (($file['error'] ?? UPLOAD_ERR_OK) !== UPLOAD_ERR_OK) {
        throw new RuntimeException('The cover image could not be uploaded.');
    }
    if (!isset($file['tmp_name']) || !is_uploaded_file((string) $file['tmp_name'])) {
        throw new RuntimeException('The uploaded image is not a valid HTTP upload.');
    }
    $actualSize = filesize((string) $file['tmp_name']);
    if ($actualSize === false || $actualSize > 2 * 1024 * 1024) {
        throw new RuntimeException('Cover images must be 2 MB or smaller.');
    }

    $finfo = new finfo(FILEINFO_MIME_TYPE);
    $mime = $finfo->file((string) $file['tmp_name']);
    if ($mime === false) {
        throw new RuntimeException('The image file type could not be verified.');
    }
    $extensions = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];
    if (!isset($extensions[$mime])) {
        throw new RuntimeException('Choose a JPEG, PNG, or WebP cover image.');
    }

    $directory = dirname(__DIR__) . '/uploads';
    if (!is_dir($directory) && !mkdir($directory, 0755, true) && !is_dir($directory)) {
        throw new RuntimeException('The cover image directory could not be created.');
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (!move_uploaded_file((string) $file['tmp_name'], $directory . '/' . $filename)) {
        throw new RuntimeException('The cover image could not be saved.');
    }
    return 'uploads/' . $filename;
}

function remove_cover_image(?string $image): void
{
    if (!$image || !preg_match('~^uploads/(?:covers/)?[a-f0-9]{32}\.(?:jpg|png|webp)$~', $image)) {
        return;
    }
    $path = dirname(__DIR__) . '/' . $image;
    if (is_file($path) && !unlink($path)) {
        error_log('Unable to remove cover image: ' . $path);
    }
}
