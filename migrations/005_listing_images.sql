CREATE TABLE IF NOT EXISTS listing_images (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 listing_id BIGINT UNSIGNED NOT NULL,
 storage_key VARCHAR(96) NOT NULL,
 width INT UNSIGNED NOT NULL,
 height INT UNSIGNED NOT NULL,
 sort_order TINYINT UNSIGNED NOT NULL DEFAULT 0,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(id),
 UNIQUE KEY uq_listing_images_storage(storage_key),
 KEY idx_listing_images_order(listing_id,sort_order,id),
 CONSTRAINT fk_listing_images_listing FOREIGN KEY(listing_id) REFERENCES listings(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
