CREATE TABLE media (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    organization_id INTEGER NOT NULL DEFAULT 0,
    original_name VARCHAR(255) NOT NULL,
    stored_name VARCHAR(255) NOT NULL,
    mime_type VARCHAR(128) NOT NULL,
    size INTEGER UNSIGNED NOT NULL,
    url VARCHAR(1024) NOT NULL,
    created_at DATETIME NOT NULL
);
CREATE INDEX media_org ON media (organization_id);
