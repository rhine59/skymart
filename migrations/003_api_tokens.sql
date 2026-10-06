CREATE TABLE IF NOT EXISTS api_tokens (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 user_id BIGINT UNSIGNED NOT NULL,
 token_hash BINARY(32) NOT NULL,
 label VARCHAR(80) NOT NULL DEFAULT 'iPhone',
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 last_used_at TIMESTAMP NULL,
 expires_at TIMESTAMP NOT NULL,
 revoked_at TIMESTAMP NULL,
 PRIMARY KEY(id),
 UNIQUE KEY uq_api_tokens_hash(token_hash),
 KEY idx_api_tokens_user_active(user_id,revoked_at,expires_at),
 CONSTRAINT fk_api_tokens_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
