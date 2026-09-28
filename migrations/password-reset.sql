CREATE TABLE IF NOT EXISTS password_reset_tokens (
 token_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin PRIMARY KEY,
 user_id BIGINT UNSIGNED NOT NULL,
 password_version INT UNSIGNED NOT NULL,
 code_hash VARCHAR(255) CHARACTER SET ascii COLLATE ascii_bin DEFAULT NULL,
 attempts TINYINT UNSIGNED NOT NULL DEFAULT 0,
 expires_at DATETIME NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 CONSTRAINT fk_reset_user FOREIGN KEY(user_id) REFERENCES users(id) ON DELETE CASCADE,
 INDEX idx_reset_user(user_id), INDEX idx_reset_expiry(expires_at)
) ENGINE=InnoDB;
CREATE TABLE IF NOT EXISTS password_reset_requests (
 id BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
 ip_hash CHAR(64) CHARACTER SET ascii NOT NULL,
 email_hash CHAR(64) CHARACTER SET ascii NOT NULL,
 created_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
 INDEX idx_reset_ip(ip_hash,created_at), INDEX idx_reset_email(email_hash,created_at),
 INDEX idx_reset_request_date(created_at)
) ENGINE=InnoDB;
