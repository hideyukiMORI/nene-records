CREATE TABLE entity_types (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    organization_id INTEGER NOT NULL DEFAULT 0,
    name VARCHAR(255) NOT NULL,
    slug VARCHAR(255) NOT NULL,
    is_pinned BOOLEAN NOT NULL DEFAULT 0,
    default_layout VARCHAR(32) NOT NULL DEFAULT 'standard',
    display_order INTEGER NOT NULL DEFAULT 0,
    labels TEXT NULL DEFAULT NULL,
    permalink_pattern VARCHAR(255) NULL DEFAULT NULL,
    previous_permalink_pattern VARCHAR(255) NULL DEFAULT NULL
);
CREATE UNIQUE INDEX entity_types_org_slug ON entity_types (organization_id, slug);
CREATE INDEX entity_types_org ON entity_types (organization_id);
