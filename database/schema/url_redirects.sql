CREATE TABLE url_redirects (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    organization_id INTEGER NOT NULL,
    source_path VARCHAR(255) NOT NULL,
    target_path VARCHAR(255) NOT NULL,
    created_at DATETIME NOT NULL
);
CREATE INDEX url_redirects_org ON url_redirects (organization_id);
CREATE UNIQUE INDEX url_redirects_org_source ON url_redirects (organization_id, source_path);
