# Vicwin FC Blog CMS

A PHP 8 and MySQL blog CMS for Apache/XAMPP. The public site is served from the project root, and the admin area is available at `/admin/`.

## Setup

1. Copy the project into `C:\xampp\htdocs\swd\07-php-blog` (or the equivalent document-root folder).
2. In XAMPP Control Panel, start Apache and MySQL. Enable Apache `mod_rewrite` and `AllowOverride All` for the project directory. The root `.htaccess` uses `RewriteBase /swd/07-php-blog/` for readable `/post/slug` and `/category/slug` routes.
3. Import [`database.sql`](./database.sql) in phpMyAdmin. Select **Import**, choose `database.sql`, then click **Go**. It creates the `vicwin_fc_blog` database and the `admins`, `categories`, and `posts` tables.
4. Configure the database connection. Defaults are `127.0.0.1`, database `vicwin_fc_blog`, user `root`, and an empty password. Override these with `DB_HOST`, `DB_NAME`, `DB_USER`, and `DB_PASS` environment variables when needed.
5. Create an administrator from a terminal:

   ```text
   C:\xampp\php\php.exe create-admin.php "Your Name" you@example.com
   ```

   Enter and confirm a password of at least 12 characters when prompted.
6. To add 12 sample posts and their categories, run:

   ```text
   C:\xampp\php\php.exe seed.php
   ```

7. Open `http://localhost/swd/07-php-blog/` and sign in at `http://localhost/swd/07-php-blog/admin/login.php`.

The web server needs the PDO MySQL, mbstring, and fileinfo extensions enabled, plus write permission for `uploads/`. Cover images are limited to JPEG, PNG, and WebP files up to 2 MB. Admin post editing requires at least one category.

Uploads are checked for a successful HTTP upload, measured from the temporary file against the 2 MB limit, and inspected with `finfo` rather than trusting the browser-supplied filename or MIME type. Only JPEG, PNG, and WebP MIME types are accepted; the server assigns a random filename and fixed extension. Together with the uploads-directory Apache rule, these checks reduce oversized-upload/resource exhaustion, forged-type/script upload, path traversal/filename collision, and uploaded-code execution risks. Replaced and deleted post images are removed.

## Features

- Public post, category, and FULLTEXT search pages.
- Paginated homepage, readable post/category URLs, related articles, and reading-time estimates.
- Admin dashboard, post draft/publish workflow, category management, and cover-image uploads.
- Password-hashed admin accounts, CSRF protection, secure session cookie settings, session ID rotation, password-hash upgrades at login, and a 30-minute inactivity timeout.
- Apache access restrictions for the application internals and uploaded executable files.

## Screenshots

Screenshots of the public homepage, article page, and admin dashboard can be added here.

## Folder structure

```text
app/                 Bootstrap, database, helpers, CSRF, and authentication
admin/               Admin dashboard and post/category management
assets/              Responsive site stylesheet
uploads/             Cover image uploads (PHP execution disabled)
index.php            Public homepage
post.php             Public article page
category.php         Public category page
search.php           Public search page
database.sql         MySQL database and table definitions
create-admin.php     CLI admin account creator
seed.php             CLI sample-content seeder
.htaccess            Readable post and category URL rewrites
```

## Exporting your database from phpMyAdmin

To export the current database to `database.sql`, open phpMyAdmin, select **vicwin_fc_blog**, choose **Export**, select **Custom** and **SQL** as the format, then click **Export**. Save the downloaded file as `database.sql` in the project folder. If you want an export that can recreate the database itself, enable **Add CREATE DATABASE / USE statement** under the custom export options.
