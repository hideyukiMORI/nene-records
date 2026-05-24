CREATE TABLE entities (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    entity_type_id INTEGER UNSIGNED NOT NULL,
    is_deleted BOOLEAN NOT NULL DEFAULT 0,
    deleted_at DATETIME NULL,
    FOREIGN KEY (entity_type_id) REFERENCES entity_types (id) ON DELETE RESTRICT ON UPDATE NO ACTION
);
CREATE INDEX entities_entity_type_id ON entities (entity_type_id);
