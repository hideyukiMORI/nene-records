CREATE TABLE access_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    organization_id INTEGER NOT NULL DEFAULT 0,
    request_id VARCHAR(64) NULL,
    method VARCHAR(10) NOT NULL,
    path VARCHAR(2048) NOT NULL,
    status_code INTEGER NOT NULL,
    duration_ms REAL NOT NULL,
    accessed_at TEXT NOT NULL,
    access_date TEXT NOT NULL,
    visitor_hash VARCHAR(64) NULL,
    referer_host VARCHAR(255) NULL,
    utm_source VARCHAR(255) NULL,
    utm_medium VARCHAR(255) NULL,
    utm_campaign VARCHAR(255) NULL,
    ref VARCHAR(255) NULL,
    client_type VARCHAR(16) NULL,
    is_bot INTEGER NULL
);

CREATE INDEX idx_access_logs_access_date ON access_logs (access_date);
CREATE INDEX idx_access_logs_accessed_at ON access_logs (accessed_at);
CREATE INDEX access_logs_org ON access_logs (organization_id);
CREATE INDEX idx_access_logs_date_visitor ON access_logs (access_date, visitor_hash);
