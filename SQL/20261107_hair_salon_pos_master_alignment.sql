-- Enforce a real database relationship between each salon service and
-- the ERP stockmaster record that supplies its POS item code and barcode.
--
-- Apply after SQL/20261107_hair_salon_pos_item_mapping.sql.
--
-- stockmaster.pkey is the stable ERP row identity. The repository's stockmaster
-- definition does not declare it as a primary/unique key, so add a unique key
-- before it can be referenced by a foreign key.
--
-- If the first ALTER fails because pkey contains duplicates, resolve those
-- duplicate stockmaster.pkey values before re-running this migration.

ALTER TABLE stockmaster
    ADD UNIQUE KEY uq_stockmaster_pkey (pkey);

ALTER TABLE salon_services
    ADD COLUMN stockmaster_pkey BIGINT DEFAULT NULL AFTER pos_barcode;

-- Link existing service mappings to the exact stockmaster row. The MIN()
-- only unambiguous legacy itemcode/barcode pairs are backfilled. If a pair
-- identifies multiple stockmaster rows, it remains unlinked for manual selection.
UPDATE salon_services s
INNER JOIN (
    SELECT itemcode, barcode, MIN(pkey) AS stockmaster_pkey
    FROM stockmaster
    WHERE itemcode IS NOT NULL AND itemcode <> ''
      AND barcode IS NOT NULL AND barcode <> ''
    GROUP BY itemcode, barcode
    HAVING COUNT(*) = 1
) sm
    ON sm.itemcode = s.pos_itemcode
   AND sm.barcode = s.pos_barcode
SET s.stockmaster_pkey = sm.stockmaster_pkey
WHERE s.pos_itemcode IS NOT NULL AND s.pos_itemcode <> ''
  AND s.pos_barcode IS NOT NULL AND s.pos_barcode <> '';

-- Remove the copied item-code/barcode columns. The stockmaster row is now
-- the sole source of those identifiers, so they cannot drift out of sync.
ALTER TABLE salon_services
    DROP INDEX uq_salon_service_pos_itemcode,
    DROP COLUMN pos_itemcode,
    DROP COLUMN pos_barcode;

-- One ERP stock item can be mapped to only one salon service.
-- Multiple NULL values are permitted for legacy/unmapped service rows.
ALTER TABLE salon_services
    ADD UNIQUE KEY uq_salon_service_stockmaster (stockmaster_pkey),
    ADD CONSTRAINT fk_salon_service_stockmaster
        FOREIGN KEY (stockmaster_pkey)
        REFERENCES stockmaster (pkey)
        ON UPDATE RESTRICT
        ON DELETE RESTRICT;
