CREATE TABLE entity_revisions (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    entity_id INTEGER UNSIGNED NOT NULL,
    action VARCHAR(32) NOT NULL,
    status VARCHAR(32) NOT NULL,
    previous_status VARCHAR(32) NULL,
    slug VARCHAR(255) NULL,
    previous_slug VARCHAR(255) NULL,
    actor_user_id INTEGER UNSIGNED NULL,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (entity_id) REFERENCES entities (id) ON DELETE CASCADE ON UPDATE CASCADE
);
CREATE INDEX entity_revisions_entity_id_created_at ON entity_revisions (entity_id, created_at);
