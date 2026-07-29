CREATE TABLE org_connect_tokens (
    id INTEGER PRIMARY KEY AUTOINCREMENT,
    organization_id INTEGER UNSIGNED NOT NULL,
    service VARCHAR(32) NOT NULL,
    token_ciphertext TEXT NOT NULL,
    token_hint VARCHAR(8) NOT NULL DEFAULT '',
    created_at DATETIME NOT NULL,
    updated_at DATETIME NOT NULL
);

CREATE UNIQUE INDEX uq_org_connect_tokens_org_service ON org_connect_tokens (organization_id, service);
