CREATE TABLE entity_tags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    entity_id INTEGER NOT NULL,
    tag_id INTEGER NOT NULL,
    FOREIGN KEY (entity_id) REFERENCES entities (id) ON DELETE CASCADE ON UPDATE NO ACTION,
    FOREIGN KEY (tag_id) REFERENCES tags (id) ON DELETE CASCADE ON UPDATE NO ACTION
);
CREATE UNIQUE INDEX entity_tags_entity_id_tag_id ON entity_tags (entity_id, tag_id);
