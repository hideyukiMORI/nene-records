CREATE TABLE widgets (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    organization_id INTEGER NOT NULL DEFAULT 0,
    widget_type VARCHAR(32) NOT NULL,
    region VARCHAR(16) NOT NULL,
    display_order INTEGER NOT NULL DEFAULT 0,
    title VARCHAR(255) NULL DEFAULT NULL,
    settings TEXT NULL DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);
CREATE INDEX widgets_org ON widgets (organization_id);
