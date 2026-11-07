-- Hair salon POS foundation
-- Apply once to the POS company database. Safe to re-run because all objects
-- are created with IF NOT EXISTS. This migration does not modify standard POS
-- sales, inventory, accounting, or existing production employee tables.

CREATE TABLE IF NOT EXISTS salon_hairdressers (
    hairdresser_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    hairdresser_code VARCHAR(20) NOT NULL,
    full_name VARCHAR(100) NOT NULL,
    phone VARCHAR(30) DEFAULT NULL,
    email VARCHAR(120) DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    notes TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (hairdresser_id),
    UNIQUE KEY uq_salon_hairdresser_code (hairdresser_code),
    KEY ix_salon_hairdresser_active_name (is_active, full_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS salon_services (
    service_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    service_code VARCHAR(30) NOT NULL,
    service_name VARCHAR(120) NOT NULL,
    service_category VARCHAR(60) DEFAULT NULL,
    description TEXT DEFAULT NULL,
    duration_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 30,
    standard_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (service_id),
    UNIQUE KEY uq_salon_service_code (service_code),
    KEY ix_salon_service_active_name (is_active, service_name),
    KEY ix_salon_service_category (service_category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS salon_hairdresser_services (
    hairdresser_id BIGINT UNSIGNED NOT NULL,
    service_id BIGINT UNSIGNED NOT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (hairdresser_id, service_id),
    KEY ix_salon_hairdresser_services_service (service_id),
    CONSTRAINT fk_salon_hs_hairdresser
        FOREIGN KEY (hairdresser_id) REFERENCES salon_hairdressers (hairdresser_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_salon_hs_service
        FOREIGN KEY (service_id) REFERENCES salon_services (service_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- A rule can be global, service-specific, hairdresser-specific, or specific
-- to one hairdresser/service combination. Resolve the most specific active
-- rule when a paid service is completed.
CREATE TABLE IF NOT EXISTS salon_commission_rules (
    commission_rule_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    hairdresser_id BIGINT UNSIGNED DEFAULT NULL,
    service_id BIGINT UNSIGNED DEFAULT NULL,
    commission_type ENUM('percentage','fixed') NOT NULL DEFAULT 'percentage',
    commission_value DECIMAL(12,4) NOT NULL DEFAULT 0.0000,
    effective_from DATE NOT NULL,
    effective_to DATE DEFAULT NULL,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (commission_rule_id),
    KEY ix_salon_commission_lookup (is_active, hairdresser_id, service_id, effective_from, effective_to),
    CONSTRAINT fk_salon_cr_hairdresser
        FOREIGN KEY (hairdresser_id) REFERENCES salon_hairdressers (hairdresser_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_salon_cr_service
        FOREIGN KEY (service_id) REFERENCES salon_services (service_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Immutable calculation snapshot for auditability. Create entries only when
-- the underlying service line is completed and paid. Payout/reversal changes
-- are recorded here rather than recalculating historic commission from rules.
CREATE TABLE IF NOT EXISTS salon_service_commissions (
    commission_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    source_document_type INT NOT NULL,
    source_document_no VARCHAR(20) NOT NULL,
    source_line_entry_no BIGINT NOT NULL,
    service_id BIGINT UNSIGNED NOT NULL,
    hairdresser_id BIGINT UNSIGNED NOT NULL,
    quantity DECIMAL(12,3) NOT NULL DEFAULT 1.000,
    eligible_service_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    commission_type ENUM('percentage','fixed') NOT NULL,
    commission_value DECIMAL(12,4) NOT NULL,
    commission_amount DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    commission_status ENUM('pending','paid','reversed') NOT NULL DEFAULT 'pending',
    payout_reference VARCHAR(40) DEFAULT NULL,
    payout_date DATETIME DEFAULT NULL,
    reversal_reason VARCHAR(255) DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (commission_id),
    UNIQUE KEY uq_salon_commission_source (source_document_type, source_document_no, source_line_entry_no, hairdresser_id),
    KEY ix_salon_commission_payout (commission_status, hairdresser_id, created_at),
    KEY ix_salon_commission_service (service_id),
    CONSTRAINT fk_salon_sc_service
        FOREIGN KEY (service_id) REFERENCES salon_services (service_id)
        ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_salon_sc_hairdresser
        FOREIGN KEY (hairdresser_id) REFERENCES salon_hairdressers (hairdresser_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
