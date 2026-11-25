-- Add optional service-card pictures to the existing salon service settings.
-- Apply once after SQL/20261107_hair_salon_stockmaster_direct.sql.
ALTER TABLE salon_service_settings
    ADD COLUMN image_path VARCHAR(255) DEFAULT NULL AFTER notes;
