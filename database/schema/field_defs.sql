CREATE TABLE field_defs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    organization_id INTEGER NOT NULL DEFAULT 0,
    entity_type_id INTEGER NOT NULL,
    field_key TEXT NOT NULL,
    data_type TEXT NOT NULL,
    target_entity_type_id INTEGER NULL,
    cardinality TEXT NULL,
    is_deleted BOOLEAN NOT NULL DEFAULT 0,
    deleted_at DATETIME NULL,
    FOREIGN KEY (entity_type_id) REFERENCES entity_types (id) ON DELETE RESTRICT ON UPDATE NO ACTION,
    FOREIGN KEY (target_entity_type_id) REFERENCES entity_types (id) ON DELETE RESTRICT ON UPDATE NO ACTION
);
CREATE INDEX field_defs_entity_type_id ON field_defs (entity_type_id);
CREATE UNIQUE INDEX field_defs_entity_type_id_field_key_active ON field_defs (entity_type_id, field_key) WHERE is_deleted = 0;
