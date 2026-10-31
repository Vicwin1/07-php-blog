# SWD Blog CMS

A PHP 8 and MySQL blog CMS for Apache/XAMPP. The public site is served from the project root, and the admin area is available at `/admin/`.

## Setup

1. Copy the project into `C:\xampp\htdocs\swd\07-php-blog` (or the equivalent document-root folder).
2. Import [`schema.sql`](./schema.sql) in phpMyAdmin or with the MySQL command line. It creates the `swd_blog` database and its `admins`, `categories`, and `posts` tables.
3. Configure the database connection. Defaults are `127.0.0.1`, database `swd_blog`, user `root`, and an empty password. Override these with `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS` environment variables when needed.
4. Create an administrator from a terminal:

   ```text
   C:\xampp\php\php.exe create-admin.php "Your Name" you@example.com
   ```

   Enter and confirm a password of at least 12 characters when prompted.
5. Open `http://localhost/swd/07-php-blog/` and sign in at `/admin/login.php`.

The web server needs PDO MySQL enabled and write permission for `uploads/`. Cover images are limited to JPEG, PNG, and WebP files up to 2 MB. Admin post editing requires at least one category.

Uploads are checked for a successful HTTP upload, measured from the temporary file against the 2 MB limit, and inspected with `finfo` rather than trusting the browser-supplied filename or MIME type. Only JPEG, PNG, and WebP MIME types are accepted; the server assigns a random filename and fixed extension. Together with the uploads-directory Apache rule, these checks reduce oversized-upload/resource exhaustion, forged-type/script upload, path traversal/filename collision, and uploaded-code execution risks. Replaced and deleted post images are removed.

## Features

- Public post, category, and FULLTEXT search pages.
- Admin dashboard, post draft/publish workflow, category management, and cover-image uploads.
- Password-hashed admin accounts, CSRF protection, secure session cookie settings, session ID rotation, and a 30-minute inactivity timeout.
- Apache access restrictions for the application internals and uploaded executable files.
