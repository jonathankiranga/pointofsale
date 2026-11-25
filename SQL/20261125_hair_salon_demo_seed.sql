-- Hair salon POS demonstration seed data
-- Repository: jonathankiranga/pointofsale
-- Branch: feature/hair-salon-pos
--
-- RUN THIS FILE ON THE COMPANY DATABASE AFTER ALL SALON MIGRATIONS,
-- INCLUDING:
--   SQL/20261107_hair_salon_pos_foundation.sql
--   SQL/20261107_hair_salon_pos_operations.sql
--   SQL/20261107_hair_salon_pos_item_mapping.sql
--   SQL/20261107_hair_salon_pos_master_alignment.sql
--   SQL/20261107_hair_salon_stockmaster_direct.sql
--   SQL/20261125_hair_salon_service_images.sql
--
-- This script does not create fake stockmaster or accounting records.
-- Salon services must already exist in stockmaster and have valid posting
-- group, VAT category, sales/VAT GL accounts, units and selling price.
--
-- Collation protection:
-- The posting-group and VAT-category joins explicitly convert both operands
-- to utf8mb4_unicode_ci. This avoids 1267 when the ERP columns use
-- utf8mb4_0900_ai_ci while salon tables use utf8mb4_unicode_ci.
--
-- Safe to re-run: staff, service settings, assignments, appointment and
-- global commission rule are inserted/updated without duplicating records.

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;

START TRANSACTION;

-- 1. Demo hairdressers.
INSERT INTO salon_hairdressers
    (hairdresser_code, full_name, phone, email, is_active, notes)
VALUES
    ('DEMO001', 'Mary Wanjiku', '0711000001', 'mary.demo@example.test', 1, 'Demo hairdresser'),
    ('DEMO002', 'Grace Njeri', '0711000002', 'grace.demo@example.test', 1, 'Demo hairdresser'),
    ('DEMO003', 'Ann Atieno', '0711000003', 'ann.demo@example.test', 1, 'Demo hairdresser'),
    ('DEMO004', 'Faith Achieng', '0711000004', 'faith.demo@example.test', 1, 'Demo hairdresser')
ON DUPLICATE KEY UPDATE
    full_name = VALUES(full_name),
    phone = VALUES(phone),
    email = VALUES(email),
    is_active = 1,
    notes = VALUES(notes);

-- 2. Find up to eight existing ERP stock items that look like salon services
-- and are fully configured for POS revenue/VAT posting.
-- IMPORTANT: the COLLATE clauses below are the fix for your collation error.
DROP TEMPORARY TABLE IF EXISTS salon_demo_seed_items;

CREATE TEMPORARY TABLE salon_demo_seed_items (
    stockmaster_pkey BIGINT NOT NULL PRIMARY KEY,
    duration_minutes SMALLINT UNSIGNED NOT NULL,
    service_name VARCHAR(255) NOT NULL
) ENGINE=InnoDB;

INSERT INTO salon_demo_seed_items
    (stockmaster_pkey, duration_minutes, service_name)
SELECT
    sm.pkey,
    CASE
        WHEN LOWER(sm.descrip) LIKE '%braid%' THEN 120
        WHEN LOWER(sm.descrip) LIKE '%treatment%' THEN 60
        WHEN LOWER(sm.descrip) LIKE '%wash%' THEN 30
        ELSE 45
    END,
    sm.descrip
FROM stockmaster sm
INNER JOIN inventorypostinggroup ipg
    ON CONVERT(sm.postinggroup USING utf8mb4) COLLATE utf8mb4_unicode_ci
     = CONVERT(ipg.code USING utf8mb4) COLLATE utf8mb4_unicode_ci
INNER JOIN vatcategory vc
    ON CONVERT(ipg.vatcategory USING utf8mb4) COLLATE utf8mb4_unicode_ci
     = CONVERT(vc.vatc USING utf8mb4) COLLATE utf8mb4_unicode_ci
WHERE sm.itemcode IS NOT NULL
  AND sm.itemcode <> ''
  AND sm.descrip IS NOT NULL
  AND sm.sellingprice IS NOT NULL
  AND sm.sellingprice >= 0
  AND sm.units IS NOT NULL
  AND ipg.defaultgl_sales IS NOT NULL
  AND ipg.defaultgl_vat IS NOT NULL
  AND vc.vat IS NOT NULL
  AND (sm.inactive IS NULL OR sm.inactive = 0)
  AND LOWER(sm.descrip) REGEXP
      'hair|cut|wash|braid|style|manicure|pedicure|treatment|blow|colour|color|relaxer'
ORDER BY sm.descrip
LIMIT 8;

-- 3. Enable the selected stockmaster items as salon services.
INSERT INTO salon_service_settings
    (stockmaster_pkey, duration_minutes, is_active, notes)
SELECT
    stockmaster_pkey,
    duration_minutes,
    1,
    'DEMO seed data - change duration and details in Salon Management'
FROM salon_demo_seed_items
ON DUPLICATE KEY UPDATE
    duration_minutes = VALUES(duration_minutes),
    is_active = 1,
    notes = VALUES(notes);

-- 3b. Attach the bundled demo service artwork.
-- These are real SVG files committed under uploads/salon-services/demo/.
-- The images are deliberately mapped by the stockmaster service description.
UPDATE salon_service_settings ss
INNER JOIN stockmaster sm ON sm.pkey = ss.stockmaster_pkey
SET ss.image_path = CASE
    WHEN LOWER(sm.descrip) LIKE '%braid%' THEN 'uploads/salon-services/demo/braids.svg'
    WHEN LOWER(sm.descrip) LIKE '%wash%' THEN 'uploads/salon-services/demo/wash.svg'
    WHEN LOWER(sm.descrip) LIKE '%blow%' THEN 'uploads/salon-services/demo/blowdry.svg'
    WHEN LOWER(sm.descrip) LIKE '%colour%' OR LOWER(sm.descrip) LIKE '%color%' THEN 'uploads/salon-services/demo/colour.svg'
    WHEN LOWER(sm.descrip) LIKE '%manicure%' OR LOWER(sm.descrip) LIKE '%pedicure%' OR LOWER(sm.descrip) LIKE '%nail%' THEN 'uploads/salon-services/demo/nails.svg'
    WHEN LOWER(sm.descrip) LIKE '%treatment%' OR LOWER(sm.descrip) LIKE '%relaxer%' THEN 'uploads/salon-services/demo/treatment.svg'
    WHEN LOWER(sm.descrip) LIKE '%style%' THEN 'uploads/salon-services/demo/styling.svg'
    WHEN LOWER(sm.descrip) LIKE '%hair%' OR LOWER(sm.descrip) LIKE '%cut%' THEN 'uploads/salon-services/demo/haircut.svg'
    ELSE 'uploads/salon-services/demo/styling.svg'
END
WHERE ss.is_active = 1
  AND sm.itemcode IS NOT NULL
  AND sm.itemcode <> ''
  AND LOWER(sm.descrip) REGEXP
      'hair|cut|wash|braid|style|manicure|pedicure|treatment|blow|colour|color|relaxer';

-- 4. Assign all four demo hairdressers to the selected demo services.
INSERT INTO salon_hairdresser_services
    (hairdresser_id, stockmaster_pkey, is_active)
SELECT
    h.hairdresser_id,
    s.stockmaster_pkey,
    1
FROM salon_hairdressers h
CROSS JOIN salon_demo_seed_items s
WHERE h.hairdresser_code IN ('DEMO001', 'DEMO002', 'DEMO003', 'DEMO004')
ON DUPLICATE KEY UPDATE is_active = 1;

-- 5. Add a global 10% demo commission rule if one is not already active today.
INSERT INTO salon_commission_rules
    (hairdresser_id, stockmaster_pkey, commission_type, commission_value,
     effective_from, effective_to, is_active)
SELECT NULL, NULL, 'percentage', 10.0000, CURDATE(), NULL, 1
WHERE NOT EXISTS (
    SELECT 1
    FROM salon_commission_rules
    WHERE hairdresser_id IS NULL
      AND stockmaster_pkey IS NULL
      AND commission_type = 'percentage'
      AND commission_value = 10.0000
      AND effective_from <= CURDATE()
      AND (effective_to IS NULL OR effective_to >= CURDATE())
      AND is_active = 1
);

-- 6. Add one sample appointment only when at least one eligible service exists.
SET @salon_demo_service = (
    SELECT stockmaster_pkey
    FROM salon_demo_seed_items
    ORDER BY service_name
    LIMIT 1
);

INSERT INTO salon_appointments
    (appointment_reference, customer_code, customer_name, customer_phone,
     appointment_start, appointment_status, notes, created_by)
SELECT
    'SAL-DEMO-001', NULL, 'Jane Customer', '0712345678',
    DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 10 HOUR,
    'booked', 'DEMO appointment - safe to delete after testing', 'DEMO-SEED'
WHERE @salon_demo_service IS NOT NULL
ON DUPLICATE KEY UPDATE
    customer_name = VALUES(customer_name),
    customer_phone = VALUES(customer_phone),
    appointment_start = VALUES(appointment_start),
    notes = VALUES(notes);

INSERT INTO salon_appointment_lines
    (appointment_id, stockmaster_pkey, hairdresser_id, agreed_price,
     duration_minutes, line_status)
SELECT
    a.appointment_id,
    sm.pkey,
    h.hairdresser_id,
    sm.sellingprice,
    ss.duration_minutes,
    'booked'
FROM salon_appointments a
INNER JOIN stockmaster sm ON sm.pkey = @salon_demo_service
INNER JOIN salon_service_settings ss ON ss.stockmaster_pkey = sm.pkey
INNER JOIN salon_hairdressers h ON h.hairdresser_code = 'DEMO001'
WHERE a.appointment_reference = 'SAL-DEMO-001'
  AND @salon_demo_service IS NOT NULL
  AND NOT EXISTS (
      SELECT 1
      FROM salon_appointment_lines existing_line
      WHERE existing_line.appointment_id = a.appointment_id
        AND existing_line.stockmaster_pkey = sm.pkey
  );

COMMIT;

DROP TEMPORARY TABLE IF EXISTS salon_demo_seed_items;

-- 7. Results: first result tells you whether the database has eligible
-- stockmaster service items; second result shows the seeded salon menu.
SELECT
    CASE
        WHEN COUNT(*) = 0 THEN
            'NO ELIGIBLE STOCKMASTER SERVICES FOUND: create/configure salon service items in ERP stockmaster first.'
        ELSE
            CONCAT(COUNT(*), ' eligible salon service item(s) enabled.')
    END AS seed_result
FROM salon_service_settings ss
INNER JOIN stockmaster sm ON sm.pkey = ss.stockmaster_pkey
WHERE ss.is_active = 1
  AND LOWER(sm.descrip) REGEXP
      'hair|cut|wash|braid|style|manicure|pedicure|treatment|blow|colour|color|relaxer';

SELECT
    sm.pkey,
    sm.itemcode,
    sm.descrip AS service_name,
    sm.sellingprice,
    ss.duration_minutes,
    ss.image_path,
    GROUP_CONCAT(h.full_name ORDER BY h.full_name SEPARATOR ', ') AS assigned_hairdressers
FROM salon_service_settings ss
INNER JOIN stockmaster sm ON sm.pkey = ss.stockmaster_pkey
LEFT JOIN salon_hairdresser_services hs
    ON hs.stockmaster_pkey = sm.pkey AND hs.is_active = 1
LEFT JOIN salon_hairdressers h
    ON h.hairdresser_id = hs.hairdresser_id AND h.is_active = 1
WHERE ss.is_active = 1
GROUP BY sm.pkey, sm.itemcode, sm.descrip, sm.sellingprice,
         ss.duration_minutes, ss.image_path
ORDER BY sm.descrip;

-- Service pictures are uploaded through Salon Management > Services.
-- This seed file deliberately does not create fake image paths or files.
