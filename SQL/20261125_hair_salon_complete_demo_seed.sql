-- COMPLETE HAIR SALON DEMO DATA
-- Branch: feature/hair-salon-pos
-- Run after the salon POS migrations, including:
--   SQL/20261107_hair_salon_pos_foundation.sql
--   SQL/20261107_hair_salon_pos_operations.sql
--   SQL/20261107_hair_salon_pos_item_mapping.sql
--   SQL/20261107_hair_salon_pos_master_alignment.sql
--   SQL/20261107_hair_salon_stockmaster_direct.sql
--   SQL/20261125_hair_salon_service_images.sql
--
-- This script creates its own NON-STOCK salon services in stockmaster (isstock=3),
-- uses an existing valid ERP posting group's GL accounts/VAT category, creates
-- a service unit if required, and attaches bundled demo illustrations.
-- It does not create fake GL accounts, stock movements, invoices or payments.
--
-- Collation-safe: text joins/comparisons between ERP and salon tables explicitly
-- use utf8mb4_unicode_ci to avoid Error 1267.

SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci;
START TRANSACTION;

-- A. Create/reuse a service unit. stockmaster.units is a code, not a description.
INSERT INTO unit (code, descrip, sns, pkey)
SELECT 'SVC', 'Salon Service', NULL, COALESCE((SELECT MAX(u.pkey) + 1 FROM unit u), 1)
WHERE NOT EXISTS (
    SELECT 1 FROM unit u
    WHERE CONVERT(u.code USING utf8mb4) COLLATE utf8mb4_unicode_ci = 'SVC'
);

-- B. Create a dedicated salon posting group by copying a real, configured
-- posting group from this company database. Prefer 'default'. Do not invent GLs.
INSERT INTO inventorypostinggroup
    (code, defaultgl_sales, defaultgl_purch, defaultgl_vat, balancesheet,
     vatcategory, wip, stockvariance, productionexpense)
SELECT
    'SALONDEMO', source_group.defaultgl_sales, source_group.defaultgl_purch,
    source_group.defaultgl_vat, source_group.balancesheet,
    source_group.vatcategory, source_group.wip, source_group.stockvariance,
    source_group.productionexpense
FROM inventorypostinggroup source_group
INNER JOIN vatcategory vc
    ON CONVERT(source_group.vatcategory USING utf8mb4) COLLATE utf8mb4_unicode_ci
     = CONVERT(vc.vatc USING utf8mb4) COLLATE utf8mb4_unicode_ci
WHERE source_group.defaultgl_sales IS NOT NULL
  AND TRIM(source_group.defaultgl_sales) <> ''
  AND source_group.defaultgl_vat IS NOT NULL
  AND TRIM(source_group.defaultgl_vat) <> ''
ORDER BY CASE WHEN source_group.code = 'default' THEN 0 ELSE 1 END,
         source_group.code
LIMIT 1
ON DUPLICATE KEY UPDATE
    defaultgl_sales = VALUES(defaultgl_sales),
    defaultgl_purch = VALUES(defaultgl_purch),
    defaultgl_vat = VALUES(defaultgl_vat),
    balancesheet = VALUES(balancesheet),
    vatcategory = VALUES(vatcategory),
    wip = VALUES(wip),
    stockvariance = VALUES(stockvariance),
    productionexpense = VALUES(productionexpense);

-- C. Hairdressers.
INSERT INTO salon_hairdressers
    (hairdresser_code, full_name, phone, email, is_active, notes)
VALUES
    ('DEMO001', 'Mary Wanjiku', '0711000001', 'mary.demo@example.test', 1, 'Demo hairdresser'),
    ('DEMO002', 'Grace Njeri', '0711000002', 'grace.demo@example.test', 1, 'Demo hairdresser'),
    ('DEMO003', 'Ann Atieno', '0711000003', 'ann.demo@example.test', 1, 'Demo hairdresser'),
    ('DEMO004', 'Faith Achieng', '0711000004', 'faith.demo@example.test', 1, 'Demo hairdresser')
ON DUPLICATE KEY UPDATE
    full_name = VALUES(full_name), phone = VALUES(phone),
    email = VALUES(email), is_active = 1, notes = VALUES(notes);

-- D. Demo salon services. These are non-stock services, not consumable inventory.
DROP TEMPORARY TABLE IF EXISTS salon_demo_catalogue;
CREATE TEMPORARY TABLE salon_demo_catalogue (
    itemcode VARCHAR(20) NOT NULL PRIMARY KEY,
    descrip VARCHAR(100) NOT NULL,
    sellingprice DECIMAL(10,2) NOT NULL,
    duration_minutes SMALLINT UNSIGNED NOT NULL,
    image_path VARCHAR(255) NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

INSERT INTO salon_demo_catalogue
    (itemcode, descrip, sellingprice, duration_minutes, image_path)
VALUES
    ('SAL-SVC-001', 'Ladies Haircut', 800.00, 45, 'uploads/salon-services/demo/haircut.svg'),
    ('SAL-SVC-002', 'Hair Wash', 500.00, 30, 'uploads/salon-services/demo/wash.svg'),
    ('SAL-SVC-003', 'Knotless Braids', 5500.00, 180, 'uploads/salon-services/demo/braids.svg'),
    ('SAL-SVC-004', 'Blow Dry Finish', 1000.00, 45, 'uploads/salon-services/demo/blowdry.svg'),
    ('SAL-SVC-005', 'Hair Colour', 3500.00, 120, 'uploads/salon-services/demo/colour.svg'),
    ('SAL-SVC-006', 'Manicure', 1200.00, 45, 'uploads/salon-services/demo/nails.svg'),
    ('SAL-SVC-007', 'Deep Conditioning Treatment', 1800.00, 60, 'uploads/salon-services/demo/treatment.svg'),
    ('SAL-SVC-008', 'Signature Hair Styling', 2000.00, 60, 'uploads/salon-services/demo/styling.svg');

-- Insert only if the company has a valid SALONDEMO posting group and service unit.
-- pkey is manually maintained in this ERP schema, so allocate after current MAX(pkey).
INSERT INTO stockmaster
    (isstock, barcode, itemcode, descrip, postinggroup, averagestock,
     sellingprice, partperunit, reorderlevel, eoq, category, units,
     subunits, container, nextserialno, inactive, pkey)
SELECT
    '3', NULL, d.itemcode, d.descrip, 'SALONDEMO', 0,
    d.sellingprice, 1, 0, 0, NULL, 'SVC',
    NULL, NULL, 0, 0,
    base.max_pkey + ROW_NUMBER() OVER (ORDER BY d.itemcode)
FROM salon_demo_catalogue d
CROSS JOIN (SELECT COALESCE(MAX(pkey), 0) AS max_pkey FROM stockmaster) base
WHERE EXISTS (
    SELECT 1 FROM inventorypostinggroup pg
    INNER JOIN vatcategory vc
        ON CONVERT(pg.vatcategory USING utf8mb4) COLLATE utf8mb4_unicode_ci
         = CONVERT(vc.vatc USING utf8mb4) COLLATE utf8mb4_unicode_ci
    WHERE pg.code = 'SALONDEMO'
      AND pg.defaultgl_sales IS NOT NULL AND TRIM(pg.defaultgl_sales) <> ''
      AND pg.defaultgl_vat IS NOT NULL AND TRIM(pg.defaultgl_vat) <> ''
)
AND EXISTS (
    SELECT 1 FROM unit u
    WHERE CONVERT(u.code USING utf8mb4) COLLATE utf8mb4_unicode_ci = 'SVC'
)
AND NOT EXISTS (
    SELECT 1 FROM stockmaster existing_item
    WHERE CONVERT(existing_item.itemcode USING utf8mb4) COLLATE utf8mb4_unicode_ci
        = CONVERT(d.itemcode USING utf8mb4) COLLATE utf8mb4_unicode_ci
);

-- E. Enable salon settings and attach the matching bundled illustration.
INSERT INTO salon_service_settings
    (stockmaster_pkey, duration_minutes, is_active, notes, image_path)
SELECT sm.pkey, d.duration_minutes, 1,
       'DEMO salon service - edit price, duration and image in ERP as required',
       d.image_path
FROM salon_demo_catalogue d
INNER JOIN stockmaster sm
    ON CONVERT(sm.itemcode USING utf8mb4) COLLATE utf8mb4_unicode_ci
     = CONVERT(d.itemcode USING utf8mb4) COLLATE utf8mb4_unicode_ci
ON DUPLICATE KEY UPDATE
    duration_minutes = VALUES(duration_minutes),
    is_active = 1,
    notes = VALUES(notes),
    image_path = VALUES(image_path);

-- F. Assign all demo hairdressers to all eight demo services.
INSERT INTO salon_hairdresser_services
    (hairdresser_id, stockmaster_pkey, is_active)
SELECT h.hairdresser_id, sm.pkey, 1
FROM salon_hairdressers h
CROSS JOIN salon_demo_catalogue d
INNER JOIN stockmaster sm
    ON CONVERT(sm.itemcode USING utf8mb4) COLLATE utf8mb4_unicode_ci
     = CONVERT(d.itemcode USING utf8mb4) COLLATE utf8mb4_unicode_ci
WHERE h.hairdresser_code IN ('DEMO001', 'DEMO002', 'DEMO003', 'DEMO004')
ON DUPLICATE KEY UPDATE is_active = 1;

-- G. Add a global 10% demo commission rule if one is not already active today.
INSERT INTO salon_commission_rules
    (hairdresser_id, stockmaster_pkey, commission_type, commission_value,
     effective_from, effective_to, is_active)
SELECT NULL, NULL, 'percentage', 10.0000, CURDATE(), NULL, 1
WHERE NOT EXISTS (
    SELECT 1 FROM salon_commission_rules
    WHERE hairdresser_id IS NULL AND stockmaster_pkey IS NULL
      AND commission_type = 'percentage' AND commission_value = 10.0000
      AND effective_from <= CURDATE()
      AND (effective_to IS NULL OR effective_to >= CURDATE())
      AND is_active = 1
);

-- H. One sample appointment tomorrow at 10:00, with one sample service line.
SET @salon_demo_service = (
    SELECT pkey FROM stockmaster
    WHERE CONVERT(itemcode USING utf8mb4) COLLATE utf8mb4_unicode_ci = 'SAL-SVC-001'
    LIMIT 1
);

INSERT INTO salon_appointments
    (appointment_reference, customer_code, customer_name, customer_phone,
     appointment_start, appointment_status, notes, created_by)
SELECT 'SAL-DEMO-001', NULL, 'Jane Customer', '0712345678',
       DATE_ADD(CURDATE(), INTERVAL 1 DAY) + INTERVAL 10 HOUR,
       'booked', 'DEMO appointment - delete after testing', 'DEMO-SEED'
WHERE @salon_demo_service IS NOT NULL
ON DUPLICATE KEY UPDATE
    customer_name = VALUES(customer_name),
    customer_phone = VALUES(customer_phone),
    appointment_start = VALUES(appointment_start),
    notes = VALUES(notes);

INSERT INTO salon_appointment_lines
    (appointment_id, stockmaster_pkey, hairdresser_id, agreed_price,
     duration_minutes, line_status)
SELECT a.appointment_id, sm.pkey, h.hairdresser_id,
       sm.sellingprice, ss.duration_minutes, 'booked'
FROM salon_appointments a
INNER JOIN stockmaster sm ON sm.pkey = @salon_demo_service
INNER JOIN salon_service_settings ss ON ss.stockmaster_pkey = sm.pkey
INNER JOIN salon_hairdressers h ON h.hairdresser_code = 'DEMO001'
WHERE a.appointment_reference = 'SAL-DEMO-001'
  AND @salon_demo_service IS NOT NULL
  AND NOT EXISTS (
      SELECT 1 FROM salon_appointment_lines existing_line
      WHERE existing_line.appointment_id = a.appointment_id
        AND existing_line.stockmaster_pkey = sm.pkey
  );

COMMIT;
DROP TEMPORARY TABLE IF EXISTS salon_demo_catalogue;

-- I. Results and diagnostics.
SELECT
    (SELECT COUNT(*) FROM inventorypostinggroup WHERE code = 'SALONDEMO') AS salon_posting_group_created,
    (SELECT COUNT(*) FROM stockmaster WHERE itemcode LIKE 'SAL-SVC-%') AS salon_stockmaster_items_created,
    (SELECT COUNT(*) FROM salon_service_settings ss
       INNER JOIN stockmaster sm ON sm.pkey = ss.stockmaster_pkey
       WHERE sm.itemcode LIKE 'SAL-SVC-%' AND ss.is_active = 1) AS active_salon_services,
    (SELECT COUNT(*) FROM salon_service_settings ss
       INNER JOIN stockmaster sm ON sm.pkey = ss.stockmaster_pkey
       WHERE sm.itemcode LIKE 'SAL-SVC-%'
         AND ss.image_path IS NOT NULL AND ss.image_path <> '') AS services_with_pictures;

SELECT sm.itemcode, sm.descrip AS service_name, sm.sellingprice,
       ss.duration_minutes, ss.image_path,
       GROUP_CONCAT(h.full_name ORDER BY h.full_name SEPARATOR ', ') AS hairdressers
FROM stockmaster sm
INNER JOIN salon_service_settings ss ON ss.stockmaster_pkey = sm.pkey
LEFT JOIN salon_hairdresser_services hs
    ON hs.stockmaster_pkey = sm.pkey AND hs.is_active = 1
LEFT JOIN salon_hairdressers h
    ON h.hairdresser_id = hs.hairdresser_id AND h.is_active = 1
WHERE sm.itemcode LIKE 'SAL-SVC-%'
GROUP BY sm.pkey, sm.itemcode, sm.descrip, sm.sellingprice,
         ss.duration_minutes, ss.image_path
ORDER BY sm.itemcode;

-- The SVG illustrations are committed in:
-- uploads/salon-services/demo/
-- Copy/pull those files with the same branch as this SQL file.
-- These are demo illustrations; replace them with salon photographs using
-- Salon Management > Services when the salon supplies its own images.
