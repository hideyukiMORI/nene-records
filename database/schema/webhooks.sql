CREATE TABLE webhooks (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    organization_id INTEGER NOT NULL DEFAULT 0,
    url VARCHAR(2048) NOT NULL,
    events TEXT NOT NULL,
    entity_type_id INTEGER NULL DEFAULT NULL,
    secret VARCHAR(255) NULL DEFAULT NULL,
    is_active INTEGER NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);
CREATE INDEX webhooks_org ON webhooks (organization_id);
