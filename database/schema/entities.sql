CREATE TABLE entities (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    entity_type_id INTEGER UNSIGNED NOT NULL,
    slug VARCHAR(255) NULL,
    status VARCHAR(16) NOT NULL DEFAULT 'draft',
    published_at DATETIME NULL,
    meta_title VARCHAR(255) NULL,
    meta_description TEXT NULL,
    is_deleted BOOLEAN NOT NULL DEFAULT 0,
    deleted_at DATETIME NULL,
    FOREIGN KEY (entity_type_id) REFERENCES entity_types (id) ON DELETE RESTRICT ON UPDATE NO ACTION
);
CREATE INDEX entities_entity_type_id ON entities (entity_type_id);
CREATE INDEX entities_status ON entities (status);
CREATE UNIQUE INDEX entities_entity_type_id_slug ON entities (entity_type_id, slug);
