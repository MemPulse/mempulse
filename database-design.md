ERD
```mermaid
erDiagram
	direction LR
	USERS {
		BIGINT id PK ""  
		VARCHAR first_name  ""  
		VARCHAR last_name  ""  
		VARCHAR email  ""  
		VARCHAR password_hash  ""  
		ENUM role  ""  
		DATETIME created_at  ""  
		DATETIME updated_at  ""  
		DATETIME deleted_at  ""  
	}

	DECKS {
		BIGINT id PK ""  
		BIGINT user_id FK ""  
		VARCHAR title  ""  
		DATETIME created_at  ""  
		DATETIME updated_at  ""  
		DATETIME deleted_at  ""  
	}

	REVIEW_SESSIONS {
		BIGINT id PK ""  
		BIGINT user_id FK ""  
		BIGINT deck_id FK ""  
		DATETIME started_at  ""  
		DATETIME ended_at  ""  
		INT total_reviewed  ""  
		INT remembered_count  ""  
		DECIMAL accuracy_percentage  ""  
	}

	USER_SETTINGS {
		BIGINT user_id PK,FK ""  
		DECIMAL remember_multiplier  ""  
		DECIMAL forgot_multiplier  ""  
		INT initial_interval_days  ""  
		DATETIME updated_at  ""  
	}

	FLASHCARDS {
		BIGINT id PK ""  
		BIGINT deck_id FK ""  
		TEXT front  ""  
		TEXT back  ""  
		INT interval_days  ""  
		DATETIME next_review_at  ""  
		INT review_count  ""  
		INT lapses  ""  
		DATETIME created_at  ""  
		DATETIME updated_at  ""  
		DATETIME deleted_at  ""  
	}

	REVIEW_LOGS {
		BIGINT id PK ""  
		BIGINT session_id FK ""  
		BIGINT card_id FK ""  
		ENUM rating  ""  
		INT previous_interval  ""  
		INT new_interval  ""  
		DATETIME reviewed_at  ""  
	}

	USERS||--o{DECKS:"owns"
	USERS||--o{REVIEW_SESSIONS:"conducts"
	USERS||--||USER_SETTINGS:"configures"
	DECKS||--o{FLASHCARDS:"contains"
	DECKS||--o{REVIEW_SESSIONS:"reviewed in"
	REVIEW_SESSIONS||--o{REVIEW_LOGS:"records"
	FLASHCARDS||--o{REVIEW_LOGS:"tracks"

Schema
-- Create Database
	CREATE DATABASE IF NOT EXISTS mempulse_db
	CHARACTER SET utf8mb4
	COLLATE utf8mb4_unicode_ci;
	USE mempulse_db;
-- 1. USERS TABLE
CREATE TABLE users (
	id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	first_name VARCHAR(50),
	last_name VARCHAR(50),
	email VARCHAR(255) NOT NULL UNIQUE,
	password_hash VARCHAR(255) NOT NULL,
	role ENUM('admin', 'user') NOT NULL DEFAULT 'user',
	created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
	updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE
	CURRENT_TIMESTAMP,
	deleted_at DATETIME NULL,
	INDEX idx_users_active (deleted_at)
) ENGINE=InnoDB;
-- 2. USER SETTINGS TABLE (SRS Configuration)
CREATE TABLE user_settings (
	user_id BIGINT UNSIGNED PRIMARY KEY,
	remember_multiplier DECIMAL(4,2) NOT NULL DEFAULT 2.00,
	forgot_multiplier DECIMAL(4,2) NOT NULL DEFAULT 0.50,
	initial_interval_days INT UNSIGNED NOT NULL DEFAULT 1,
	updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE
	CURRENT_TIMESTAMP,
	FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;
-- 3. DECKS TABLE
CREATE TABLE decks (
	id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
	user_id BIGINT UNSIGNED NOT NULL,
	title VARCHAR(150) NOT NULL,
	description TEXT NULL,
	created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
	updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE
	CURRENT_TIMESTAMP,
	deleted_at DATETIME NULL,
	FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
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
	updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE
	CURRENT_TIMESTAMP,
	deleted_at DATETIME NULL,
	FOREIGN KEY (deck_id) REFERENCES decks(id) ON DELETE CASCADE,
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
	accuracy_percentage DECIMAL(5,2) GENERATED ALWAYS AS (
	CASE
	WHEN total_reviewed > 0 THEN (remembered_count / total_reviewed)
	* 100
	ELSE 0.00
	END
) STORED,
	FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
	b. Processing Card Rating (SRS Interval Logic)
	FOREIGN KEY (deck_id) REFERENCES decks(id) ON DELETE CASCADE,
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
	reviewed_at DATETIME DEFAULT CURRENT_TIMESTAMP,
	FOREIGN KEY (session_id) REFERENCES review_sessions(id) ON DELETE
	CASCADE,
	FOREIGN KEY (card_id) REFERENCES flashcards(id) ON DELETE SET NULL,
	INDEX idx_session_logs (session_id)
) ENGINE=InnoDB;