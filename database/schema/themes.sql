CREATE TABLE themes (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    organization_id INTEGER NOT NULL DEFAULT 0,
    theme_key VARCHAR(64) NOT NULL,
    name VARCHAR(80) NOT NULL,
    version VARCHAR(32) NOT NULL DEFAULT '1.0.0',
    source VARCHAR(16) NOT NULL DEFAULT 'runtime',
    manifest TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);
CREATE UNIQUE INDEX themes_org_key ON themes (organization_id, theme_key);
CREATE INDEX themes_org ON themes (organization_id);
