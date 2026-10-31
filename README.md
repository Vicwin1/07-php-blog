# Vicwin FC Blog CMS

A PHP 8 and MySQL blog CMS for Apache/XAMPP. The public site is served from the project root, and the admin area is available at `/admin/`.

## Setup

1. Copy the project folder into XAMPP's Apache document root. For example:
   - Windows: `C:\xampp\htdocs\swd\07-php-blog`
   - macOS: `/Applications/XAMPP/xamppfiles/htdocs/swd/07-php-blog`
2. Start Apache and MySQL in the XAMPP Control Panel. Enable Apache `mod_rewrite` and `AllowOverride All` for the project directory. The root `.htaccess` uses `RewriteBase /swd/07-php-blog/` for readable `/post/slug` and `/category/slug` routes.
3. Import [`database.sql`](./database.sql) in phpMyAdmin: select **Import**, choose the file, and click **Go**. The script creates the `vicwin_fc_blog` database and its `admins`, `categories`, and `posts` tables.
4. The default database connection is `127.0.0.1`, database `vicwin_fc_blog`, user `root`, and an empty password. Override these with `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS` environment variables when needed.
5. From a terminal, change to the project folder and create the first administrator:

   ```text
   # Windows
   C:\xampp\php\php.exe create-admin.php "Your Name" you@example.com

   # macOS
   /Applications/XAMPP/xamppfiles/bin/php create-admin.php "Your Name" you@example.com
   ```

   Enter and confirm a password of at least 12 characters when prompted.
6. Optionally add 12 sample posts across four categories:

   ```text
   # Windows
   C:\xampp\php\php.exe seed.php

   # macOS
   /Applications/XAMPP/xamppfiles/bin/php seed.php
   ```

7. Open `http://localhost/swd/07-php-blog/`. Sign in at `http://localhost/swd/07-php-blog/admin/login.php`.

The web server needs the PDO MySQL, mbstring, and fileinfo extensions enabled, plus write permission for `uploads/`. Cover images are limited to JPEG, PNG, and WebP files up to 2 MB. Admin post editing requires at least one category.

Uploads are checked for a successful HTTP upload, measured from the temporary file against the 2 MB limit, and inspected with `finfo` rather than trusting the browser-supplied filename or MIME type. Only JPEG, PNG, and WebP MIME types are accepted; the server assigns a random filename and fixed extension. Together with the uploads-directory Apache rule, these checks reduce oversized-upload/resource exhaustion, forged-type/script upload, path traversal/filename collision, and uploaded-code execution risks. Replaced and deleted post images are removed.

## Features

- Public post, category, and FULLTEXT search pages.
- Paginated homepage, readable post/category URLs, related articles, and reading-time estimates.
- Admin dashboard, post draft/publish workflow, category management, and cover-image uploads.
- Password-hashed admin accounts, CSRF protection, secure session cookie settings, session ID rotation, password-hash upgrades at login, and a 30-minute inactivity timeout.
- Apache access restrictions for the application internals and uploaded executable files.

## Screenshots

Add screenshots of the public homepage, an article page, and the admin dashboard here.

## Folder structure

```text
app/                    Bootstrap, database, helpers, CSRF, and authentication
admin/                  Admin dashboard, sign-in, and content management
admin/posts/            Post listing, editor, and deletion
admin/categories/       Category management
assets/                 Responsive site stylesheet
uploads/                Cover images; PHP execution is disabled
index.php               Public homepage
post.php                Public article page
category.php            Public category page
search.php              Public search page
database.sql            MySQL database and table definitions
create-admin.php        CLI-only first-admin creator
seed.php                CLI-only sample-content seeder
.htaccess               Readable post and category URL rewrites
```

## Exporting your database from phpMyAdmin

In phpMyAdmin, select **vicwin_fc_blog**, open **Export**, choose **Custom**, and select **SQL** as the format. Enable **Add CREATE DATABASE / USE statement** if the export should recreate the database, then click **Export**. Save the downloaded file as `database.sql` in the project folder, replacing the existing file if you intend to update the schema dump.
