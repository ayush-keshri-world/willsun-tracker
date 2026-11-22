-- Run once in phpMyAdmin after deploying this version.
UPDATE users SET role = 'admin' WHERE role = 'owner';
ALTER TABLE users MODIFY role ENUM('admin', 'employee') NOT NULL DEFAULT 'employee';
UPDATE visits SET photo_expires_at = NULL WHERE photo_path IS NOT NULL;
