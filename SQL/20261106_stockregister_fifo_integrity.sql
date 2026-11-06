-- Bring the FIFO stock register in line with its runtime usage.
-- Back up the database before running this migration.
-- This migration targets the stockregister definition in SQL/posv1_stockregister.sql.

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
