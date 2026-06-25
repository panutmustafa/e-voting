CREATE DATABASE IF NOT EXISTS evoting CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE evoting;

CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(100) NOT NULL UNIQUE,
    password VARCHAR(255) NOT NULL,
    role ENUM('admin') NOT NULL DEFAULT 'admin',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    voting_start DATETIME NULL,
    voting_end DATETIME NULL,
    voting_status ENUM('draft','open','closed') NOT NULL DEFAULT 'draft',
    theme ENUM('light','dark') NOT NULL DEFAULT 'light',
    school_name VARCHAR(255) NOT NULL DEFAULT 'SD Negeri Jomblang 2',
    school_logo VARCHAR(255) NULL,
    welcome_text TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS voters (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    voter_number VARCHAR(50) NOT NULL UNIQUE,
    nik VARCHAR(20) NOT NULL UNIQUE,
    name VARCHAR(255) NOT NULL,
    student_name VARCHAR(255) NULL,
    class_name VARCHAR(100) NULL,
    phone VARCHAR(30) NULL,
    email VARCHAR(255) NULL,
    token VARCHAR(100) NOT NULL UNIQUE,
    qr_code VARCHAR(255) NULL,
    has_voted ENUM('0','1') NOT NULL DEFAULT '0',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS candidates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(255) NOT NULL,
    position VARCHAR(255) NOT NULL,
    photo VARCHAR(255) NULL,
    video VARCHAR(255) NULL,
    cv_file VARCHAR(255) NULL,
    vision TEXT NULL,
    mission TEXT NULL,
    program TEXT NULL,
    motto VARCHAR(255) NULL,
    education TEXT NULL,
    organization TEXT NULL,
    experience TEXT NULL,
    is_active ENUM('0','1') NOT NULL DEFAULT '1',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS votes (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    voter_id INT UNSIGNED NOT NULL,
    candidate_id INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY unique_vote_per_voter (voter_id),
    CONSTRAINT fk_votes_voter FOREIGN KEY (voter_id) REFERENCES voters(id) ON DELETE CASCADE,
    CONSTRAINT fk_votes_candidate FOREIGN KEY (candidate_id) REFERENCES candidates(id) ON DELETE RESTRICT
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    action VARCHAR(255) NOT NULL,
    description TEXT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB;

INSERT INTO users (username, password, role) VALUES ('admin', '$2y$10$T58R2PlXMfQp9aIXYPf7DuyG7lq7ordPzo.4s0c2M8uUjOy03q4uS', 'admin');
INSERT INTO settings (voting_status, theme, school_name) VALUES ('draft', 'light', 'SD Negeri Jomblang 2');
