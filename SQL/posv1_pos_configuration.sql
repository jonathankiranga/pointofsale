-- POS defaults are stored in the existing key/value config table.
-- Blank values deliberately require the administrator to select real ERP master records.
INSERT INTO config (confname, confvalue) VALUES
('POS_DefaultCustomer', ''),
('POS_DefaultStore', ''),
('POS_DefaultCurrency', ''),
('POS_CashBankAccount', ''),
('POS_MPesaBankAccount', ''),
('POS_DefaultPostingGroup', '')
ON DUPLICATE KEY UPDATE confname = VALUES(confname);
