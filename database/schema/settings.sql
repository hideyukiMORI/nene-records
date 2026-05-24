CREATE TABLE setting_defs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    setting_key TEXT NOT NULL,
    data_type TEXT NOT NULL,
    default_value TEXT NULL,
    is_public BOOLEAN NOT NULL DEFAULT 0,
    label TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);
CREATE UNIQUE INDEX setting_defs_setting_key ON setting_defs (setting_key);

CREATE TABLE setting_values (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    setting_key TEXT NOT NULL,
    value TEXT NULL,
    is_deleted BOOLEAN NOT NULL DEFAULT 0,
    deleted_at DATETIME NULL,
    created_by INTEGER NULL,
    updated_by INTEGER NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    FOREIGN KEY (setting_key) REFERENCES setting_defs (setting_key) ON DELETE RESTRICT ON UPDATE CASCADE
);
CREATE UNIQUE INDEX setting_values_setting_key ON setting_values (setting_key);

CREATE TABLE setting_revisions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    setting_key TEXT NOT NULL,
    value TEXT NULL,
    previous_value TEXT NULL,
    action TEXT NOT NULL,
    actor_user_id INTEGER NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (setting_key) REFERENCES setting_defs (setting_key) ON DELETE RESTRICT ON UPDATE CASCADE
);
CREATE INDEX setting_revisions_setting_key_created_at ON setting_revisions (setting_key, created_at);
