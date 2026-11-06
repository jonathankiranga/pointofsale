-- POS cashier shifts, payment classification and tax snapshots.
-- Run once against each company database before enabling POS X/Z reporting.

CREATE TABLE IF NOT EXISTS pos_shifts (
    id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
    cashier_id VARCHAR(20) NOT NULL,
    cashier_name VARCHAR(100) NOT NULL,
    opened_at DATETIME NOT NULL,
    opening_float DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    closed_at DATETIME DEFAULT NULL,
    cash_counted DECIMAL(15,2) DEFAULT NULL,
    expected_cash DECIMAL(15,2) DEFAULT NULL,
    cash_variance DECIMAL(15,2) DEFAULT NULL,
    total_sales DECIMAL(15,2) DEFAULT NULL,
    total_vat DECIMAL(15,2) DEFAULT NULL,
    cash_sales DECIMAL(15,2) DEFAULT NULL,
    mpesa_sales DECIMAL(15,2) DEFAULT NULL,
    z_report_number VARCHAR(30) DEFAULT NULL,
    status ENUM('OPEN','CLOSED') NOT NULL DEFAULT 'OPEN',
    active_cashier_id VARCHAR(20) GENERATED ALWAYS AS (CASE WHEN status='OPEN' THEN cashier_id ELSE NULL END) STORED,
    PRIMARY KEY (id),
    UNIQUE KEY uq_pos_shifts_one_open_per_cashier (active_cashier_id),
    KEY idx_pos_shifts_cashier_status (cashier_id, status),
    KEY idx_pos_shifts_opened (opened_at),
    KEY idx_pos_shifts_closed (closed_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;

ALTER TABLE SalesHeader
    ADD COLUMN pos_shift_id BIGINT UNSIGNED NULL,
    ADD COLUMN payment_method VARCHAR(20) NULL,
    ADD COLUMN mpesa_phone VARCHAR(20) NULL,
    ADD COLUMN total_vat DECIMAL(15,2) NOT NULL DEFAULT 0.00,
    ADD KEY idx_salesheader_pos_shift (pos_shift_id),
    ADD KEY idx_salesheader_payment_method (payment_method);

ALTER TABLE SalesLine
    ADD COLUMN pos_tax_category VARCHAR(20) NULL;

-- Required defaults use the existing config table. Configure these ERP master
-- records in POSConfiguration.php; this migration deliberately invents none.
INSERT INTO config (confname, confvalue) VALUES
('POS_DefaultPostingGroup', '')
ON DUPLICATE KEY UPDATE confname = VALUES(confname);
