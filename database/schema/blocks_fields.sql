CREATE TABLE blocks_fields (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    organization_id INTEGER NOT NULL DEFAULT 0,
    entity_id INTEGER NOT NULL,
    field_key VARCHAR(255) NOT NULL,
    locale VARCHAR(35) NULL,
    value TEXT NOT NULL,
    is_deleted BOOLEAN NOT NULL DEFAULT 0,
    deleted_at DATETIME NULL,
    FOREIGN KEY (entity_id) REFERENCES entities (id) ON DELETE CASCADE ON UPDATE NO ACTION
);
CREATE INDEX blocks_fields_entity_id_field_key ON blocks_fields (entity_id, field_key);
CREATE INDEX blocks_fields_org ON blocks_fields (organization_id);
