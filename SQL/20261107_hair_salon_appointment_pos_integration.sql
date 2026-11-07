-- Link each salon appointment to at most one posted POS sale.
-- Apply after 20261107_hair_salon_pos_operations.sql.
-- The unique appointment_id is the database-level duplicate-checkout safeguard.

CREATE TABLE IF NOT EXISTS salon_appointment_transactions (
    appointment_id BIGINT UNSIGNED NOT NULL,
    document_type INT NOT NULL DEFAULT 1,
    document_no VARCHAR(20) NOT NULL,
    sales_entry_no BIGINT NOT NULL,
    linked_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (appointment_id),
    UNIQUE KEY uq_salon_appointment_transaction_document (document_type, document_no),
    KEY ix_salon_appointment_transaction_entry (sales_entry_no),
    CONSTRAINT fk_salon_appointment_transaction_appointment
        FOREIGN KEY (appointment_id) REFERENCES salon_appointments (appointment_id)
        ON UPDATE CASCADE ON DELETE RESTRICT
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO scripts (script, pagesecurity, description)
VALUES ('salon-appointment.php', 4, 'Load and start a booked salon appointment in POS')
ON DUPLICATE KEY UPDATE description=VALUES(description);
