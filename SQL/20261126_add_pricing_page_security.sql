-- Register the Selling Price Setup page with the dedicated price-maintenance permission.
-- Token 12 is 'Prices Security' in the company security-token setup.
INSERT INTO scripts (script, pagesecurity, description)
SELECT 'Pricing.php', 12, 'Selling Price Setup'
WHERE NOT EXISTS (
    SELECT 1
    FROM scripts
    WHERE script = 'Pricing.php'
);
