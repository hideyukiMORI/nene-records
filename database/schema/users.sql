CREATE TABLE users (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    email VARCHAR(255) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    role VARCHAR(32) NOT NULL DEFAULT 'admin',
    status ENUM('active', 'invited') NOT NULL DEFAULT 'active',
    invite_token_hash VARCHAR(64) NULL DEFAULT NULL,
    invite_expires_at DATETIME NULL DEFAULT NULL,
    password_reset_token_hash VARCHAR(64) NULL DEFAULT NULL,
    password_reset_expires_at DATETIME NULL DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);
CREATE UNIQUE INDEX users_email ON users (email);
