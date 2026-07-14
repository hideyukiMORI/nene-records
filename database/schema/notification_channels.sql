CREATE TABLE notification_channels (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    organization_id INTEGER NOT NULL DEFAULT 0,
    channel_type VARCHAR(16) NOT NULL,
    label VARCHAR(100) NOT NULL,
    is_enabled INTEGER NOT NULL DEFAULT 1,
    config_json TEXT NOT NULL,
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);
CREATE INDEX notification_channels_org_enabled ON notification_channels (organization_id, is_enabled);
CREATE INDEX notification_channels_org_type ON notification_channels (organization_id, channel_type);
