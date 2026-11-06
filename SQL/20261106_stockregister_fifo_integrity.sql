-- Bring the FIFO stock register in line with its runtime usage.
-- Back up the database before running this migration.
-- This migration targets the stockregister definition in SQL/posv1_stockregister.sql.
--
-- Legacy GRN layers stored StockIn in the purchase line's selected UOM, while
-- FIFO StockOut movements are already recorded in loose-unit equivalents.
-- Convert only StockIn for full-unit GRN layers; do not multiply StockOut.

DROP PROCEDURE IF EXISTS migrate_stockregister_fifo_units;
DELIMITER $$
CREATE PROCEDURE migrate_stockregister_fifo_units()
BEGIN
    IF EXISTS (
        SELECT 1
        FROM stockregister sr
        LEFT JOIN (
            SELECT documentno, code,
                   COUNT(DISTINCT entryno) AS line_count,
                   MAX(UOM) AS UOM,
                   MAX(PartPerUnit) AS PartPerUnit
            FROM PurchaseLine
            GROUP BY documentno, code
        ) pl
          ON pl.documentno = sr.GRN
         AND pl.code = sr.itemcode
        WHERE pl.line_count IS NULL
           OR pl.line_count <> 1
           OR (pl.UOM = 'fulqty' AND COALESCE(pl.PartPerUnit,0) <= 0)
    ) THEN
        SIGNAL SQLSTATE '45000'
            SET MESSAGE_TEXT = 'Stock register has GRN layers that cannot be matched unambiguously to PurchaseLine. Reconcile those layers before applying this migration.';
    END IF;

    UPDATE stockregister sr
    JOIN (
        SELECT documentno, code,
               MAX(UOM) AS UOM,
               MAX(PartPerUnit) AS PartPerUnit
        FROM PurchaseLine
        GROUP BY documentno, code
        HAVING COUNT(DISTINCT entryno) = 1
    ) pl
      ON pl.documentno = sr.GRN
     AND pl.code = sr.itemcode
    SET sr.StockIn = CASE
        WHEN pl.UOM = 'fulqty' THEN sr.StockIn * pl.PartPerUnit
        ELSE sr.StockIn
    END;
END$$
DELIMITER ;

CALL migrate_stockregister_fifo_units();
DROP PROCEDURE migrate_stockregister_fifo_units;

-- rowid is the FIFO layer identifier. It must be generated for each receipt layer.
-- StockBalance is derived, never manually maintained: StockIn minus StockOut.
ALTER TABLE stockregister
    MODIFY COLUMN rowid BIGINT NOT NULL AUTO_INCREMENT,
    ADD PRIMARY KEY (rowid),
    MODIFY COLUMN StockBalance DOUBLE
        GENERATED ALWAYS AS (COALESCE(StockIn, 0) - COALESCE(StockOut, 0)) STORED;

-- Supports oldest-layer-first retrieval by item.
CREATE INDEX idx_stockregister_item_fifo
    ON stockregister (itemcode, rowid);
