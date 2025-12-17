-- -----------------------------------------------------------------------------
-- CALLIE SCHEMA GUIDE
-- -----------------------------------------------------------------------------
-- This file is where you define your database structure.
-- Unlike complex migration tools, Callie keeps it simple:
--
-- 1. Write your CREATE TABLE statements here.
-- 2. Import this file into your database using:
--    a) phpMyAdmin: Go to "Import" tab -> Upload this file.
--    b) Command Line: mysql -u root -p database_name < schema.sql
--    c) TablePlus/DBeaver: Open this file and hit "Run".
-- -----------------------------------------------------------------------------

-- 1. USERS TABLE
-- Stores login information. Passwords must be hashed!
CREATE TABLE IF NOT EXISTS users (
    id INT AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255),
    email VARCHAR(255) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL, -- Store BCrypt hash here, NOT plain text
    role ENUM('user', 'admin') DEFAULT 'user',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
);

-- 2. EXAMPLE: TODOS TABLE
-- A simple example table linked to a user.
CREATE TABLE IF NOT EXISTS todos (
    id INT AUTO_INCREMENT PRIMARY KEY,
    user_id INT NOT NULL,
    title VARCHAR(255) NOT NULL,
    is_completed BOOLEAN DEFAULT FALSE,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    
    -- Foreign Key: Links todo to a user. 
    -- If user is deleted, their todos are deleted (ON DELETE CASCADE).
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

-- -----------------------------------------------------------------------------
-- TIPS:
-- * ALWAYS use `IF NOT EXISTS` to prevent errors if you run this twice.
-- * Use `TIMESTAMP` columns to automatically track creation times.
-- -----------------------------------------------------------------------------
