CREATE TABLE IF NOT EXISTS favourites (
 user_id BIGINT UNSIGNED NOT NULL,
 listing_id BIGINT UNSIGNED NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(user_id,listing_id),
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 FOREIGN KEY(listing_id) REFERENCES listings(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS saved_searches (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 name VARCHAR(120) NOT NULL,
 query_text VARCHAR(120) NOT NULL DEFAULT '',
 category_slug VARCHAR(80) NOT NULL DEFAULT '',
 enabled TINYINT(1) NOT NULL DEFAULT 1,
 frequency ENUM('immediate','daily','weekly') NOT NULL DEFAULT 'daily',
 notify_email TINYINT(1) NOT NULL DEFAULT 0,
 notify_sms TINYINT(1) NOT NULL DEFAULT 0,
 notify_whatsapp TINYINT(1) NOT NULL DEFAULT 0,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
 KEY idx_saved_searches_user(user_id),
 FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS saved_search_matches (
 saved_search_id BIGINT UNSIGNED NOT NULL,
 listing_id BIGINT UNSIGNED NOT NULL,
 first_seen_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 PRIMARY KEY(saved_search_id,listing_id),
 FOREIGN KEY(saved_search_id) REFERENCES saved_searches(id) ON DELETE CASCADE,
 FOREIGN KEY(listing_id) REFERENCES listings(id) ON DELETE CASCADE
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS notification_outbox (
 id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
 saved_search_id BIGINT UNSIGNED NOT NULL,
 listing_id BIGINT UNSIGNED NOT NULL,
 channel ENUM('email','sms','whatsapp') NOT NULL,
 status ENUM('pending','sent','failed','suppressed') NOT NULL DEFAULT 'pending',
 attempts INT UNSIGNED NOT NULL DEFAULT 0,
 next_attempt_at DATETIME NULL,
 provider_message_id VARCHAR(255) NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 sent_at DATETIME NULL,
 UNIQUE KEY unique_delivery(saved_search_id,listing_id,channel),
 FOREIGN KEY(saved_search_id) REFERENCES saved_searches(id) ON DELETE CASCADE,
 FOREIGN KEY(listing_id) REFERENCES listings(id) ON DELETE CASCADE
) ENGINE=InnoDB;
