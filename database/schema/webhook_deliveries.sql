CREATE TABLE webhook_deliveries (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    webhook_id INTEGER NOT NULL,
    event VARCHAR(64) NOT NULL,
    entity_type_id INTEGER NOT NULL,
    entity_id INTEGER NOT NULL,
    target_url VARCHAR(2048) NOT NULL,
    secret VARCHAR(255) NULL DEFAULT NULL,
    payload TEXT NOT NULL,
    status VARCHAR(16) NOT NULL DEFAULT 'pending',
    attempts INTEGER NOT NULL DEFAULT 0,
    max_attempts INTEGER NOT NULL DEFAULT 5,
    next_attempt_at DATETIME NOT NULL,
    last_error TEXT NULL DEFAULT NULL,
    response_status INTEGER NULL DEFAULT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL,
    delivered_at DATETIME NULL DEFAULT NULL
);
CREATE INDEX webhook_deliveries_due ON webhook_deliveries (status, next_attempt_at);
CREATE INDEX webhook_deliveries_webhook ON webhook_deliveries (webhook_id);
