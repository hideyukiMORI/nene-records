CREATE TABLE text_fields (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    entity_id INTEGER UNSIGNED NOT NULL,
    field_key VARCHAR(255) NOT NULL,
    locale VARCHAR(10) NULL DEFAULT NULL,
    value TEXT NOT NULL,
    is_deleted BOOLEAN NOT NULL DEFAULT 0,
    deleted_at DATETIME NULL,
    FOREIGN KEY (entity_id) REFERENCES entities (id) ON DELETE RESTRICT ON UPDATE NO ACTION
);
CREATE INDEX text_fields_entity_id ON text_fields (entity_id);
CREATE INDEX text_fields_entity_locale ON text_fields (entity_id, locale);
