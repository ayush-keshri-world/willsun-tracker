# MR Visit Tracker

A small, mobile-first PHP 8.1+ and MySQL app for pharmaceutical MRs to manage assigned doctor/retail-party visits, check-ins, and orders.

## Requirements

- PHP 8.1+ with `pdo_mysql`, `fileinfo`, and `session` enabled
- MySQL 5.7+ or MariaDB 10.4+
- A web server whose document root is the `public/` directory

## Local setup

1. Create a local MySQL database named `mr_visit_tracker` (or choose another name).
2. Import [sql/schema.sql](sql/schema.sql) into that database.
3. Copy `config.example.php` to `config.php`, then enter the database connection values. Keep `cookie_secure` as `false` locally.
4. Create the first administrator. Generate a secure password hash:

   ```sh
   php -r "echo password_hash('your-strong-password', PASSWORD_DEFAULT), PHP_EOL;"
   ```

   In your MySQL client, substitute the generated hash and your own mobile number:

   ```sql
   INSERT INTO users (name, mobile, password_hash, role)
   VALUES ('Administrator', '9999999999', 'PASTE_GENERATED_HASH', 'admin');
   ```

5. From the project root, start the PHP development server:

   ```sh
   php -S localhost:8000 -t public
   ```

6. Open `http://localhost:8000`, sign in as the administrator, create an MR, then create and assign a party.

Camera and GPS access normally require HTTPS on a real phone; localhost is suitable for testing standard pages and forms. Test at a 360px-wide mobile viewport.

Employees register with their name, recovery email, mobile number, and password. New accounts have no parties until an administrator assigns them. Users can change their own password after logging in.

## Self-service password reset (Resend)

1. In Resend, verify the sending domain and create an API key.
2. On the web host, add these values to the untracked `config.php` file (never commit the API key):

   ```php
   'resend_api_key' => 're_your_private_key',
   'email_from' => 'Willsun ERP <no-reply@willsunpharma.com>',
   'app_url' => 'https://erp.willsunpharma.com',
   ```

3. For an existing database, run [sql/upgrade_password_resets.sql](sql/upgrade_password_resets.sql) once in phpMyAdmin.
4. Existing users must sign in once and save a recovery email on their Password page. New users, MRs, and admins supply an email during account creation.

The reset page does not reveal whether an email exists. Reset links are single-use, expire after 30 minutes, and requests are limited to one per account every minute.

## Security notes

- `config.php` and uploaded files are intentionally ignored by Git.
- All state-changing forms use a session CSRF token.
- MRs are server-side restricted to their assigned parties, including visit photos.
- Photos are kept outside `public/` and are cleared after 24 hours by the cron job.
