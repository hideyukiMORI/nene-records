CREATE TABLE entity_types (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL
);
CREATE UNIQUE INDEX entity_types_slug ON entity_types (slug);
