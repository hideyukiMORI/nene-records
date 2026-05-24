CREATE TABLE access_logs (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    request_id VARCHAR(64) NULL,
    method VARCHAR(10) NOT NULL,
    path VARCHAR(2048) NOT NULL,
    status_code INTEGER NOT NULL,
    duration_ms REAL NOT NULL,
    accessed_at TEXT NOT NULL,
    access_date TEXT NOT NULL
);

CREATE INDEX idx_access_logs_access_date ON access_logs (access_date);
CREATE INDEX idx_access_logs_accessed_at ON access_logs (accessed_at);
