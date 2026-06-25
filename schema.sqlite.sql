CREATE TABLE IF NOT EXISTS users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    username TEXT NOT NULL UNIQUE,
    password TEXT NOT NULL,
    role TEXT NOT NULL DEFAULT 'admin',
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS settings (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    voting_start TEXT NULL,
    voting_end TEXT NULL,
    voting_status TEXT NOT NULL DEFAULT 'draft',
    theme TEXT NOT NULL DEFAULT 'light',
    school_name TEXT NOT NULL DEFAULT 'SD Negeri Jomblang 2',
    school_logo TEXT NULL,
    welcome_text TEXT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS voters (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    voter_number TEXT NOT NULL UNIQUE,
    nik TEXT NOT NULL UNIQUE,
    name TEXT NOT NULL,
    student_name TEXT NULL,
    class_name TEXT NULL,
    phone TEXT NULL,
    email TEXT NULL,
    token TEXT NOT NULL UNIQUE,
    qr_code TEXT NULL,
    has_voted TEXT NOT NULL DEFAULT '0',
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS candidates (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name TEXT NOT NULL,
    position TEXT NOT NULL,
    photo TEXT NULL,
    video TEXT NULL,
    cv_file TEXT NULL,
    vision TEXT NULL,
    mission TEXT NULL,
    program TEXT NULL,
    motto TEXT NULL,
    education TEXT NULL,
    organization TEXT NULL,
    experience TEXT NULL,
    is_active TEXT NOT NULL DEFAULT '1',
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    updated_at TEXT DEFAULT CURRENT_TIMESTAMP
);

CREATE TABLE IF NOT EXISTS votes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    voter_id INTEGER NOT NULL,
    candidate_id INTEGER NOT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP,
    UNIQUE(voter_id)
);

CREATE TABLE IF NOT EXISTS logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    action TEXT NOT NULL,
    description TEXT NULL,
    created_at TEXT DEFAULT CURRENT_TIMESTAMP
);

INSERT OR IGNORE INTO users (username, password, role) VALUES ('admin', '$2y$10$T58R2PlXMfQp9aIXYPf7DuyG7lq7ordPzo.4s0c2M8uUjOy03q4uS', 'admin');
INSERT OR IGNORE INTO settings (voting_status, theme, school_name) VALUES ('draft', 'light', 'SD Negeri Jomblang 2');
