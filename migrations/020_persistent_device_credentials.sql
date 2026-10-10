-- Authentication persistence is independent of paid advert duration.
-- Existing expiring tokens retain their previous expiry until users sign in again.
ALTER TABLE api_tokens MODIFY COLUMN expires_at DATETIME NULL DEFAULT NULL;
