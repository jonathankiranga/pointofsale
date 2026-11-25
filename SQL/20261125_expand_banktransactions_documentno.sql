-- POS receipt document numbers can exceed the legacy CHAR(10) limit.
-- Current POS identifiers such as CS-1795604433588 require 16 characters.
ALTER TABLE `banktransactions`
    MODIFY COLUMN `DocumentNo` VARCHAR(50) NOT NULL;
