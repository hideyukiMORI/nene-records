CREATE TABLE entity_archive (
    id INT UNSIGNED NOT NULL AUTO_INCREMENT,
    original_entity_id INT UNSIGNED NOT NULL,
    entity_type_id INT UNSIGNED NOT NULL,
    entity_type_slug VARCHAR(255) NOT NULL,
    entity_type_name VARCHAR(255) NOT NULL,
    entity_slug VARCHAR(255) DEFAULT NULL,
    entity_status VARCHAR(16) NOT NULL,
    deleted_at DATETIME DEFAULT NULL,
    archived_at DATETIME NOT NULL,
    archived_reason VARCHAR(64) NOT NULL DEFAULT 'entity_type_deleted',
    snapshot JSON NOT NULL,
    PRIMARY KEY (id),
    KEY idx_entity_type_id (entity_type_id),
    KEY idx_original_entity_id (original_entity_id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
