CREATE DATABASE IF NOT EXISTS mempulse_db
CHARACTER SET utf8mb4
COLLATE utf8mb4_unicode_ci;

USE mempulse_db;


-- 1. USERS TABLE
CREATE TABLE users (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(255) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    password_changed_at DATETIME NULL,
    role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    account_status ENUM('pending', 'approved', 'rejected') NOT NULL DEFAULT 'approved',
    INDEX idx_users_active (deleted_at)
) ENGINE=InnoDB;


-- 2. USER SETTINGS TABLE (SRS Configuration)
CREATE TABLE user_settings (
    user_id BIGINT UNSIGNED PRIMARY KEY,
    remember_multiplier DECIMAL(4,2) NOT NULL DEFAULT 2.00,
    forgot_multiplier DECIMAL(4,2) NOT NULL DEFAULT 0.50,
    initial_interval_days INT UNSIGNED NOT NULL DEFAULT 1,
    maximum_interval_days INT UNSIGNED NOT NULL DEFAULT 365,
    theme ENUM('light', 'dark', 'system')NOT NULL DEFAULT 'system',
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE
) ENGINE=InnoDB;


-- 3. DECKS TABLE
CREATE TABLE decks (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    title VARCHAR(150) NOT NULL,
    cover_image VARCHAR(255) DEFAULT NULL,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,
    INDEX idx_decks_user_active (user_id, deleted_at)
) ENGINE=InnoDB;


-- 4. FLASHCARDS TABLE
CREATE TABLE flashcards (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    deck_id BIGINT UNSIGNED NOT NULL,
    front TEXT NOT NULL,
    back TEXT NOT NULL,
    interval_days INT UNSIGNED NOT NULL DEFAULT 1,
    next_review_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    review_count INT UNSIGNED NOT NULL DEFAULT 0,
    lapses INT UNSIGNED NOT NULL DEFAULT 0,
    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP
        ON UPDATE CURRENT_TIMESTAMP,
    deleted_at DATETIME NULL,
    FOREIGN KEY (deck_id)
        REFERENCES decks(id)
        ON DELETE CASCADE,
    INDEX idx_cards_deck_due (deck_id, deleted_at, next_review_at)
) ENGINE=InnoDB;


-- 5. REVIEW SESSIONS TABLE
CREATE TABLE review_sessions (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id BIGINT UNSIGNED NOT NULL,
    deck_id BIGINT UNSIGNED NOT NULL,
    started_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    ended_at DATETIME NULL,
    total_reviewed INT UNSIGNED NOT NULL DEFAULT 0,
    remembered_count INT UNSIGNED NOT NULL DEFAULT 0,
    accuracy_percentage DECIMAL(5,2)
        GENERATED ALWAYS AS (
            CASE
                WHEN total_reviewed > 0
                    THEN (remembered_count / total_reviewed) * 100
                ELSE 0.00
            END
        ) STORED,
    FOREIGN KEY (user_id)
        REFERENCES users(id)
        ON DELETE CASCADE,
    FOREIGN KEY (deck_id)
        REFERENCES decks(id)
        ON DELETE CASCADE,
    INDEX idx_user_sessions (user_id, started_at)
) ENGINE=InnoDB;


-- 6. REVIEW LOGS TABLE
CREATE TABLE review_logs (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    session_id BIGINT UNSIGNED NOT NULL,
    card_id BIGINT UNSIGNED NULL,
    rating ENUM('remembered', 'forgot') NOT NULL,
    previous_interval INT UNSIGNED NOT NULL,
    new_interval INT UNSIGNED NOT NULL,
    response_time_ms INT UNSIGNED NULL,
    reviewed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (session_id)
        REFERENCES review_sessions(id)
        ON DELETE CASCADE,
    FOREIGN KEY (card_id)
        REFERENCES flashcards(id)
        ON DELETE SET NULL,
    INDEX idx_session_logs (session_id)
) ENGINE=InnoDB;

-- 7. ADMIN AUDIT LOG TABLE (append-only record of admin actions)
CREATE TABLE admin_audit_log (
    id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    admin_id BIGINT UNSIGNED NULL,
    action VARCHAR(50) NOT NULL,
    target_user_id BIGINT UNSIGNED NULL,
    target_email VARCHAR(255) NOT NULL,
    target_name VARCHAR(201) NOT NULL,
    previous_status VARCHAR(20) NULL,
    new_status VARCHAR(20) NULL,
    ip_address VARCHAR(45) NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (admin_id)
        REFERENCES users(id)
        ON DELETE SET NULL,
    FOREIGN KEY (target_user_id)
        REFERENCES users(id)
        ON DELETE SET NULL,
    INDEX idx_audit_created (created_at),
    INDEX idx_audit_target (target_user_id, created_at),
    INDEX idx_audit_admin (admin_id, created_at)
) ENGINE=InnoDB;