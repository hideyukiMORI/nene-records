CREATE TABLE user_profiles (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    user_id INTEGER NOT NULL,
    display_name VARCHAR(100) NULL DEFAULT NULL,
    full_name VARCHAR(200) NULL DEFAULT NULL,
    job_title VARCHAR(100) NULL DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (user_id) REFERENCES users (id) ON DELETE CASCADE ON UPDATE NO ACTION
);
CREATE UNIQUE INDEX user_profiles_user_id ON user_profiles (user_id);
