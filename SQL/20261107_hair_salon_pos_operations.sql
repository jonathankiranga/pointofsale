-- Salon operational workflow extension
-- Apply after 20261107_hair_salon_pos_foundation.sql.

CREATE TABLE IF NOT EXISTS salon_appointments (
    appointment_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    appointment_reference VARCHAR(24) NOT NULL,
    customer_code VARCHAR(20) DEFAULT NULL,
    customer_name VARCHAR(100) NOT NULL,
    customer_phone VARCHAR(30) DEFAULT NULL,
    appointment_start DATETIME NOT NULL,
    appointment_end DATETIME DEFAULT NULL,
    appointment_status ENUM('booked','arrived','in_service','completed','cancelled','no_show') NOT NULL DEFAULT 'booked',
    notes TEXT DEFAULT NULL,
    created_by VARCHAR(20) NOT NULL,
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    updated_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    PRIMARY KEY (appointment_id),
    UNIQUE KEY uq_salon_appointment_reference (appointment_reference),
    KEY ix_salon_appointment_start_status (appointment_start, appointment_status),
    KEY ix_salon_appointment_customer_phone (customer_phone)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS salon_appointment_lines (
    appointment_line_id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    appointment_id BIGINT UNSIGNED NOT NULL,
    service_id BIGINT UNSIGNED NOT NULL,
    hairdresser_id BIGINT UNSIGNED DEFAULT NULL,
    agreed_price DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    duration_minutes SMALLINT UNSIGNED NOT NULL DEFAULT 30,
    line_status ENUM('booked','arrived','in_service','completed','cancelled') NOT NULL DEFAULT 'booked',
    created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (appointment_line_id),
    KEY ix_salon_appointment_lines_appointment (appointment_id),
    KEY ix_salon_appointment_lines_hairdresser (hairdresser_id),
    CONSTRAINT fk_salon_al_appointment FOREIGN KEY (appointment_id)
        REFERENCES salon_appointments (appointment_id) ON UPDATE CASCADE ON DELETE CASCADE,
    CONSTRAINT fk_salon_al_service FOREIGN KEY (service_id)
        REFERENCES salon_services (service_id) ON UPDATE CASCADE ON DELETE RESTRICT,
    CONSTRAINT fk_salon_al_hairdresser FOREIGN KEY (hairdresser_id)
        REFERENCES salon_hairdressers (hairdresser_id) ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Register the operational maintenance page with the POS security framework.
INSERT INTO scripts (script, pagesecurity, description)
VALUES ('SalonManagement.php', 4, 'Salon staff, services, appointments and commissions')
ON DUPLICATE KEY UPDATE description=VALUES(description);
