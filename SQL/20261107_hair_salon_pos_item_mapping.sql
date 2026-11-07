-- Map salon services to existing ERP POS item masters.
-- Each service item must have its own ERP stockmaster record configured with
-- the correct sales/VAT posting groups. The POS uses that item for accounting
-- and inventory-independent service revenue posting.
ALTER TABLE salon_services
    ADD COLUMN pos_itemcode VARCHAR(20) DEFAULT NULL AFTER standard_price,
    ADD COLUMN pos_barcode VARCHAR(20) DEFAULT NULL AFTER pos_itemcode,
    ADD UNIQUE KEY uq_salon_service_pos_itemcode (pos_itemcode);
