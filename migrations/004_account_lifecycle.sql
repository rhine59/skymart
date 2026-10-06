ALTER TABLE users
 ADD COLUMN role ENUM('user','admin') NOT NULL DEFAULT 'user' AFTER phone,
 ADD COLUMN status ENUM('active','disabled','deactivated') NOT NULL DEFAULT 'active' AFTER role,
 ADD COLUMN deactivated_at TIMESTAMP NULL AFTER status;

CREATE TABLE IF NOT EXISTS password_reset_tokens (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
 user_id BIGINT UNSIGNED NOT NULL,
 token_hash BINARY(32) NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 expires_at TIMESTAMP NOT NULL,
 used_at TIMESTAMP NULL,
 PRIMARY KEY(id),
 UNIQUE KEY uq_password_reset_hash(token_hash),
 KEY idx_password_reset_user(user_id,expires_at),
 CONSTRAINT fk_password_reset_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
