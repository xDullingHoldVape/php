-- Password Manager Database Schema
-- Run in phpMyAdmin: SQL tab → paste → Go

CREATE DATABASE IF NOT EXISTS password_manager
  CHARACTER SET utf8mb4
  COLLATE utf8mb4_unicode_ci;

USE password_manager;

-- Users table: stores login credentials and encrypted encryption key
CREATE TABLE IF NOT EXISTS users (
    id          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    username    VARCHAR(100)    NOT NULL UNIQUE,
    email       VARCHAR(150)    NOT NULL UNIQUE,
    password    VARCHAR(255)    NOT NULL,           -- bcrypt hash of login password
    enc_key     TEXT            NOT NULL,           -- AES-encrypted encryption key (encrypted with user's plain password)
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Passwords table: stores saved passwords encrypted with user's key
CREATE TABLE IF NOT EXISTS passwords (
    id          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED    NOT NULL,
    site_name   VARCHAR(200)    NOT NULL,           -- website or program name
    username    VARCHAR(200)    DEFAULT NULL,       -- optional username for that site
    password    TEXT            NOT NULL,           -- AES-encrypted password
    notes       TEXT            DEFAULT NULL,       -- optional notes (also encrypted)
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_passwords_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

-- Password generation log (optional audit trail)
CREATE TABLE IF NOT EXISTS generation_log (
    id          INT UNSIGNED    AUTO_INCREMENT PRIMARY KEY,
    user_id     INT UNSIGNED    NOT NULL,
    length      TINYINT UNSIGNED NOT NULL,
    uppercase   TINYINT UNSIGNED NOT NULL DEFAULT 0,
    lowercase   TINYINT UNSIGNED NOT NULL DEFAULT 0,
    numbers     TINYINT UNSIGNED NOT NULL DEFAULT 0,
    special     TINYINT UNSIGNED NOT NULL DEFAULT 0,
    generated_at TIMESTAMP      NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_log_user FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
