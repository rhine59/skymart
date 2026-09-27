-- Phase 3 listing discovery indexes.
CREATE INDEX idx_listings_category_status_created ON listings (category_id, status, created_at);
CREATE INDEX idx_listings_user_status_created ON listings (user_id, status, created_at);
