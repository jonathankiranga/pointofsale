-- Shared-hosting POS print queue.
-- Apply this migration to the same company database used by config.php $DefaultDatabase.

CREATE TABLE IF NOT EXISTS pos_print_devices (
    device_id CHAR(32) NOT NULL,
    secret_hash CHAR(64) NOT NULL,
    device_name VARCHAR(120) NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    last_seen DATETIME DEFAULT NULL,
    PRIMARY KEY (device_id),
    KEY idx_pos_print_devices_active (is_active, last_seen)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS pos_print_jobs (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    document_no VARCHAR(40) NOT NULL,
    payload LONGTEXT NOT NULL,
    status ENUM('queued','printing','printed','failed') NOT NULL DEFAULT 'queued',
    device_id CHAR(32) DEFAULT NULL,
    attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
    locked_until DATETIME DEFAULT NULL,
    last_error VARCHAR(500) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    printed_at DATETIME DEFAULT NULL,
    PRIMARY KEY (id),
    KEY idx_pos_print_jobs_queue (status, locked_until, id),
    KEY idx_pos_print_jobs_device (device_id, status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
