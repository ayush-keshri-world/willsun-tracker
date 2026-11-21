-- Run this once on existing installations using phpMyAdmin's SQL tab.
ALTER TABLE users MODIFY role ENUM('owner', 'admin', 'employee') NOT NULL DEFAULT 'employee';

-- Replace the mobile number below with the company owner's registered mobile number.
-- UPDATE users SET role = 'owner' WHERE mobile = '9999999999';
