CREATE TABLE entity_archive (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    organization_id INTEGER NOT NULL DEFAULT 0,
    original_entity_id INTEGER UNSIGNED NOT NULL,
    entity_type_id INTEGER UNSIGNED NOT NULL,
    entity_type_slug VARCHAR(255) NOT NULL,
    entity_type_name VARCHAR(255) NOT NULL,
    entity_slug VARCHAR(255) DEFAULT NULL,
    entity_status VARCHAR(16) NOT NULL,
    deleted_at DATETIME DEFAULT NULL,
    archived_at DATETIME NOT NULL,
    archived_reason VARCHAR(64) NOT NULL DEFAULT 'entity_type_deleted',
    snapshot TEXT NOT NULL
);
CREATE INDEX idx_entity_archive_org ON entity_archive (organization_id);
CREATE INDEX idx_entity_archive_entity_type_id ON entity_archive (entity_type_id);
CREATE INDEX idx_entity_archive_original_entity_id ON entity_archive (original_entity_id);
