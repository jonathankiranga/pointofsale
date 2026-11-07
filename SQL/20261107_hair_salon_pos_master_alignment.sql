-- Align salon service identity with the ERP stock item master.
-- Apply after SQL/20261107_hair_salon_pos_item_mapping.sql.
-- Existing salon service records retain their service_id and service_code.
-- Their displayed name/category are refreshed from the mapped ERP item.

UPDATE salon_services SET service_code = CONCAT('TMP', UUID_SHORT());
UPDATE salon_services SET service_code = CONCAT('SRV', LPAD(service_id, 6, '0'));

UPDATE salon_services s
INNER JOIN stockmaster sm
    ON sm.itemcode = s.pos_itemcode
   AND sm.barcode = s.pos_barcode
LEFT JOIN stockcategory sc
    ON sc.categoryid = sm.category
SET s.service_name = sm.descrip,
    s.service_category = sc.categorydescription
WHERE s.pos_itemcode IS NOT NULL
  AND s.pos_itemcode <> ''
  AND s.pos_barcode IS NOT NULL
  AND s.pos_barcode <> '';
