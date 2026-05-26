CREATE TABLE entity_types (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    is_pinned BOOLEAN NOT NULL DEFAULT 0,
    labels TEXT NULL DEFAULT NULL,
    permalink_pattern VARCHAR(255) NULL DEFAULT NULL
);
CREATE UNIQUE INDEX entity_types_slug ON entity_types (slug);
