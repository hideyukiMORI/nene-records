CREATE TABLE comments (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    organization_id INTEGER NOT NULL DEFAULT 0,
    entity_id INTEGER NOT NULL,
    author_name VARCHAR(100) NOT NULL,
    author_email VARCHAR(255) NOT NULL,
    body TEXT NOT NULL,
    is_approved INTEGER NOT NULL DEFAULT 0,
    created_at DATETIME NOT NULL,
    FOREIGN KEY (entity_id) REFERENCES entities (id) ON DELETE CASCADE ON UPDATE NO ACTION
);
CREATE INDEX comments_org ON comments (organization_id);
CREATE INDEX comments_entity_approved ON comments (entity_id, is_approved);
