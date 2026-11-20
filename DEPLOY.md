# Deploy to cPanel

1. In cPanel, create a subdomain such as `mr.example.com`. Set its document root to the uploaded app’s `public` folder, for example `/home/CPANEL_USER/mr-visit-tracker/public`.
2. Enable AutoSSL for the subdomain, then confirm that `https://mr.example.com` works. Camera and GPS require HTTPS.
3. In **Select PHP Version**, select PHP 8.1 or newer. Enable the `pdo_mysql` and `fileinfo` extensions if your host exposes extension controls.
4. In **MySQL Databases**, create a database and a database user. Add that user to the database with all privileges.
5. Open **phpMyAdmin**, select the new database, choose **Import**, and import `sql/schema.sql`.
6. Upload the project by cPanel Git Version Control, File Manager, or FTP. Do not upload a real `config.php` from another environment.
7. On the server, copy `config.example.php` to `config.php`. Enter the cPanel database host, database name, user, and password. Set `'cookie_secure' => true` once HTTPS is working. Ensure `storage/photos/` is writable by the PHP process (normally directory permissions `0755`; use `0775` only if your host requires it).
8. Create the first admin through phpMyAdmin using the command and `INSERT` shown in [README.md](README.md). Do not use the sample mobile number or password in production.
9. In cPanel **Cron Jobs**, add this command once per hour. Replace `CPANEL_USER`, the app path, and the PHP path if your hosting provider gives a different one:

   ```sh
   /usr/local/bin/php /home/CPANEL_USER/mr-visit-tracker/cron/cleanup.php >/dev/null 2>&1
   ```

   Select **Once Per Hour**. To discover the correct PHP path, ask the host or run `which php` through its terminal if it provides one.
10. On a real phone over HTTPS, log in as an MR, open an assigned party, complete a check-in, add an order, and later confirm that the photo becomes unavailable after the hourly cleanup runs while the visit remains visible.

## Updating

Use cPanel Git Version Control’s **Update from Remote**, or upload only changed application files. Preserve production `config.php` and `storage/photos/`. Back up the MySQL database before importing any future schema migration.
