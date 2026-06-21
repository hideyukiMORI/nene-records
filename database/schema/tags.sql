CREATE TABLE tags (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    organization_id INTEGER NOT NULL DEFAULT 0,
    slug VARCHAR(255) NOT NULL,
    name VARCHAR(255) NOT NULL
);
CREATE UNIQUE INDEX tags_org_slug ON tags (organization_id, slug);
CREATE INDEX tags_org ON tags (organization_id);
