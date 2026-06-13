CREATE TABLE IF NOT EXISTS menus (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    organization_id INTEGER NOT NULL DEFAULT 0,
    name TEXT NOT NULL,
    slug TEXT NOT NULL,
    location TEXT NULL,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
CREATE INDEX menus_org ON menus (organization_id);
CREATE UNIQUE INDEX menus_org_slug ON menus (organization_id, slug);
