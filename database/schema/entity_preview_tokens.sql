CREATE TABLE entity_preview_tokens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    organization_id INTEGER NOT NULL DEFAULT 0,
    entity_id INTEGER UNSIGNED NOT NULL,
    token VARCHAR(64) NOT NULL,
    expires_at DATETIME NOT NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (entity_id) REFERENCES entities (id) ON DELETE CASCADE ON UPDATE NO ACTION
);
CREATE UNIQUE INDEX entity_preview_tokens_token ON entity_preview_tokens (token);
CREATE INDEX entity_preview_tokens_entity_id ON entity_preview_tokens (entity_id);
CREATE INDEX entity_preview_tokens_org ON entity_preview_tokens (organization_id);
