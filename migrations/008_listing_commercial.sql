ALTER TABLE listings
 ADD COLUMN duration_days SMALLINT UNSIGNED NOT NULL DEFAULT 30,
 ADD COLUMN contact_name VARCHAR(160) NULL,
 ADD COLUMN contact_email VARCHAR(254) NULL,
 ADD COLUMN contact_phone VARCHAR(40) NULL,
 ADD COLUMN payment_status ENUM('unpaid','pending','paid','waived') NOT NULL DEFAULT 'unpaid',
 ADD COLUMN payment_reference VARCHAR(200) NULL,
 ADD COLUMN published_at TIMESTAMP NULL;
CREATE TABLE IF NOT EXISTS listing_fee_options (
 duration_days SMALLINT UNSIGNED NOT NULL PRIMARY KEY,
 fee_pence INT UNSIGNED NOT NULL,
 enabled BOOLEAN NOT NULL DEFAULT TRUE
);
INSERT INTO listing_fee_options(duration_days,fee_pence,enabled) VALUES (30,0,0),(60,0,0),(90,0,0);
