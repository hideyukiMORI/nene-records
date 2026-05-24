CREATE TABLE datetime_fields (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    entity_id INTEGER UNSIGNED NOT NULL,
    field_key VARCHAR(255) NOT NULL,
    value VARCHAR(64) NOT NULL,
    is_deleted BOOLEAN NOT NULL DEFAULT 0,
    deleted_at DATETIME NULL,
    FOREIGN KEY (entity_id) REFERENCES entities (id) ON DELETE RESTRICT ON UPDATE NO ACTION
);
CREATE INDEX datetime_fields_entity_id ON datetime_fields (entity_id);
