CREATE TABLE entity_relations (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    source_entity_id INTEGER NOT NULL,
    target_entity_id INTEGER NOT NULL,
    field_key TEXT NOT NULL,
    FOREIGN KEY (source_entity_id) REFERENCES entities (id) ON DELETE CASCADE ON UPDATE NO ACTION,
    FOREIGN KEY (target_entity_id) REFERENCES entities (id) ON DELETE CASCADE ON UPDATE NO ACTION
);
CREATE INDEX entity_relations_source_entity_id_field_key ON entity_relations (source_entity_id, field_key);
CREATE UNIQUE INDEX entity_relations_source_target_field_key ON entity_relations (source_entity_id, target_entity_id, field_key);
