-- Move salon service identity and commercial data to ERP stockmaster.
-- Apply once AFTER 20261107_hair_salon_pos_master_alignment.sql.
--
-- stockmaster is the only item master. salon_service_settings stores only
-- salon operations (duration, activation and notes), keyed directly by
-- stockmaster.pkey. Assignments, appointments and commissions also reference
-- stockmaster.pkey directly.

-- Review these diagnostics before proceeding. Every referenced service must
-- already have an unambiguous stockmaster mapping.
SELECT 'unmapped appointment lines' AS issue, COUNT(*) AS affected
FROM salon_appointment_lines l
LEFT JOIN salon_services s ON s.service_id=l.service_id
WHERE s.stockmaster_pkey IS NULL
UNION ALL
SELECT 'unmapped commission history', COUNT(*)
FROM salon_service_commissions c
LEFT JOIN salon_services s ON s.service_id=c.service_id
WHERE s.stockmaster_pkey IS NULL
UNION ALL
SELECT 'unmapped staff assignments', COUNT(*)
FROM salon_hairdresser_services hs
LEFT JOIN salon_services s ON s.service_id=hs.service_id
WHERE s.stockmaster_pkey IS NULL
UNION ALL
SELECT 'unmapped commission rules', COUNT(*)
FROM salon_commission_rules r
LEFT JOIN salon_services s ON s.service_id=r.service_id
WHERE r.service_id IS NOT NULL AND s.stockmaster_pkey IS NULL;

-- Abort before any schema changes if a referenced legacy service cannot be
-- translated to one ERP stockmaster key. The duplicate-key insert is a
-- deliberate migration guard and works without stored-procedure delimiters.
CREATE TEMPORARY TABLE salon_migration_guard (guard_id TINYINT NOT NULL PRIMARY KEY);
INSERT INTO salon_migration_guard VALUES (1);
INSERT INTO salon_migration_guard
SELECT IF(SUM(affected)=0,0,1)
FROM (
    SELECT COUNT(*) AS affected
    FROM salon_appointment_lines l
    LEFT JOIN salon_services s ON s.service_id=l.service_id
    WHERE s.stockmaster_pkey IS NULL
    UNION ALL
    SELECT COUNT(*)
    FROM salon_service_commissions c
    LEFT JOIN salon_services s ON s.service_id=c.service_id
    WHERE s.stockmaster_pkey IS NULL
    UNION ALL
    SELECT COUNT(*)
    FROM salon_hairdresser_services hs
    LEFT JOIN salon_services s ON s.service_id=hs.service_id
    WHERE s.stockmaster_pkey IS NULL
    UNION ALL
    SELECT COUNT(*)
    FROM salon_commission_rules r
    LEFT JOIN salon_services s ON s.service_id=r.service_id
    WHERE r.service_id IS NOT NULL AND s.stockmaster_pkey IS NULL
) unmapped;
DROP TEMPORARY TABLE salon_migration_guard;

CREATE TABLE IF NOT EXISTS salon_service_settings (
    stockmaster_pkey BIGINT NOT NULL,
    duration_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 30,
    is_active TINYINT(1) NOT NULL DEFAULT 1,
    notes TEXT DEFAULT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (stockmaster_pkey),
    CONSTRAINT fk_salon_settings_stockmaster
        FOREIGN KEY (stockmaster_pkey) REFERENCES stockmaster (pkey)
        ON UPDATE RESTRICT ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO salon_service_settings (stockmaster_pkey,duration_minutes,is_active,notes,created_at,updated_at)
SELECT stockmaster_pkey,duration_minutes,is_active,description,created_at,updated_at
FROM salon_services
WHERE stockmaster_pkey IS NOT NULL
ON DUPLICATE KEY UPDATE
    duration_minutes=VALUES(duration_minutes),
    is_active=VALUES(is_active),
    notes=VALUES(notes);

-- Staff/service assignments now identify the ERP item directly.
ALTER TABLE salon_hairdresser_services
    ADD COLUMN stockmaster_pkey BIGINT DEFAULT NULL AFTER hairdresser_id;
UPDATE salon_hairdresser_services hs
INNER JOIN salon_services s ON s.service_id=hs.service_id
SET hs.stockmaster_pkey=s.stockmaster_pkey;
ALTER TABLE salon_hairdresser_services
    DROP FOREIGN KEY fk_salon_hs_service,
    DROP INDEX ix_salon_hairdresser_services_service,
    DROP PRIMARY KEY,
    DROP COLUMN service_id,
    MODIFY stockmaster_pkey BIGINT NOT NULL,
    ADD PRIMARY KEY (hairdresser_id,stockmaster_pkey),
    ADD KEY ix_salon_hairdresser_services_stockmaster (stockmaster_pkey),
    ADD CONSTRAINT fk_salon_hs_stockmaster
        FOREIGN KEY (stockmaster_pkey) REFERENCES stockmaster (pkey)
        ON UPDATE RESTRICT ON DELETE RESTRICT;

-- Commission rules: NULL still means all services.
ALTER TABLE salon_commission_rules
    ADD COLUMN stockmaster_pkey BIGINT DEFAULT NULL AFTER hairdresser_id;
UPDATE salon_commission_rules r
INNER JOIN salon_services s ON s.service_id=r.service_id
SET r.stockmaster_pkey=s.stockmaster_pkey;
ALTER TABLE salon_commission_rules
    DROP FOREIGN KEY fk_salon_cr_service,
    DROP INDEX ix_salon_commission_lookup,
    DROP COLUMN service_id,
    ADD KEY ix_salon_commission_lookup (is_active,hairdresser_id,stockmaster_pkey,effective_from,effective_to),
    ADD CONSTRAINT fk_salon_cr_stockmaster
        FOREIGN KEY (stockmaster_pkey) REFERENCES stockmaster (pkey)
        ON UPDATE RESTRICT ON DELETE RESTRICT;

-- Historical commission entries retain their exact ERP item identity.
ALTER TABLE salon_service_commissions
    ADD COLUMN stockmaster_pkey BIGINT DEFAULT NULL AFTER source_line_entry_no;
UPDATE salon_service_commissions c
INNER JOIN salon_services s ON s.service_id=c.service_id
SET c.stockmaster_pkey=s.stockmaster_pkey;
ALTER TABLE salon_service_commissions
    DROP FOREIGN KEY fk_salon_sc_service,
    DROP INDEX ix_salon_commission_service,
    DROP COLUMN service_id,
    MODIFY stockmaster_pkey BIGINT NOT NULL,
    ADD KEY ix_salon_commission_stockmaster (stockmaster_pkey),
    ADD CONSTRAINT fk_salon_sc_stockmaster
        FOREIGN KEY (stockmaster_pkey) REFERENCES stockmaster (pkey)
        ON UPDATE RESTRICT ON DELETE RESTRICT;

-- Appointment lines retain the ERP item selected at booking.
ALTER TABLE salon_appointment_lines
    ADD COLUMN stockmaster_pkey BIGINT DEFAULT NULL AFTER appointment_id;
UPDATE salon_appointment_lines l
INNER JOIN salon_services s ON s.service_id=l.service_id
SET l.stockmaster_pkey=s.stockmaster_pkey;
ALTER TABLE salon_appointment_lines
    DROP FOREIGN KEY fk_salon_al_service,
    DROP COLUMN service_id,
    MODIFY stockmaster_pkey BIGINT NOT NULL,
    ADD KEY ix_salon_appointment_lines_stockmaster (stockmaster_pkey),
    ADD CONSTRAINT fk_salon_al_stockmaster
        FOREIGN KEY (stockmaster_pkey) REFERENCES stockmaster (pkey)
        ON UPDATE RESTRICT ON DELETE RESTRICT;

-- Remove the duplicate service catalogue. The ERP stockmaster now owns item
-- code, description, category, barcode, selling price, VAT and posting setup.
DROP TABLE salon_services;
