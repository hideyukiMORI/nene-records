CREATE TABLE IF NOT EXISTS navigation_items (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    organization_id INTEGER NOT NULL DEFAULT 0,
    label TEXT NOT NULL,
    url TEXT NOT NULL,
    location TEXT NOT NULL DEFAULT 'header',
    display_order INTEGER NOT NULL DEFAULT 0,
    created_at TEXT NOT NULL,
    updated_at TEXT NOT NULL
);
CREATE INDEX navigation_items_org ON navigation_items (organization_id);
CREATE INDEX navigation_items_org_location ON navigation_items (organization_id, location);
