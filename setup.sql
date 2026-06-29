-- Run this SQL in phpMyAdmin (Task 5 schema)

CREATE DATABASE IF NOT EXISTS apex_intern;
USE apex_intern;

-- Users table with role column (Task 5: User Roles & Permissions)
CREATE TABLE IF NOT EXISTS users (
    id         INT AUTO_INCREMENT PRIMARY KEY,
    name       VARCHAR(100)  NOT NULL,
    email      VARCHAR(150)  NOT NULL UNIQUE,
    password   VARCHAR(255)  NOT NULL,
    phone      VARCHAR(20)   DEFAULT NULL,
    address    VARCHAR(255)  DEFAULT NULL,
    role       ENUM('admin','user') NOT NULL DEFAULT 'user',
    avatar     VARCHAR(255)  DEFAULT NULL,
    created_at TIMESTAMP     DEFAULT CURRENT_TIMESTAMP
);

-- If you already have the users table from Task 4, run these instead:
-- ALTER TABLE users ADD COLUMN role ENUM('admin','user') NOT NULL DEFAULT 'user' AFTER address;
-- ALTER TABLE users ADD COLUMN avatar VARCHAR(255) DEFAULT NULL AFTER role;

-- Make the first registered user an admin (run after registering):
-- UPDATE users SET role = 'admin' WHERE id = 1;
