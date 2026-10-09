CREATE TABLE IF NOT EXISTS buyer_enquiries (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 listing_id BIGINT UNSIGNED NOT NULL,
 buyer_id BIGINT UNSIGNED NOT NULL,
 seller_id BIGINT UNSIGNED NOT NULL,
 message TEXT NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_enquiries_seller (seller_id,created_at),
 INDEX idx_enquiries_buyer (buyer_id,created_at),
 CONSTRAINT fk_enquiry_listing FOREIGN KEY (listing_id) REFERENCES listings(id),
 CONSTRAINT fk_enquiry_buyer FOREIGN KEY (buyer_id) REFERENCES users(id),
 CONSTRAINT fk_enquiry_seller FOREIGN KEY (seller_id) REFERENCES users(id)
);
