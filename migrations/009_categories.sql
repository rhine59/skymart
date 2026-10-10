-- Preserve existing adverts by mapping retired categories into Parts.
INSERT IGNORE INTO categories (name, slug) VALUES
('Flexwing','flexwing'),('Avionics','avionics'),('Services','services'),('Parts','parts'),
('Miscellaneous','miscellaneous'),('Aircraft','aircraft'),('Instruments','instruments'),('Pilot Equipment','pilot-equipment');
UPDATE listings SET category_id=(SELECT id FROM categories WHERE slug='parts')
WHERE category_id IN (SELECT id FROM categories WHERE slug IN ('engines','propellers'));
UPDATE saved_searches SET category_slug='parts' WHERE category_slug IN ('engines','propellers');
DELETE FROM categories WHERE slug IN ('engines','propellers');
