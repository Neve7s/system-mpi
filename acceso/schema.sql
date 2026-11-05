-- Base EXCLUSIVA mpi_identidad, importar sin sobreescribir tiendas o liquidaciones.
CREATE TABLE IF NOT EXISTS identity_grants (
 store_user_id BIGINT UNSIGNED NOT NULL PRIMARY KEY,
 liquid_role ENUM('admin','analyst') NULL,
 enabled TINYINT(1) NOT NULL DEFAULT 0,
 updated_at TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS identity_sessions (
 session_id CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
 store_user_id BIGINT UNSIGNED NOT NULL,
 store_auth_version INT UNSIGNED NOT NULL,
 created_at DATETIME NOT NULL,
 last_seen_at DATETIME NOT NULL,
 expires_at DATETIME NOT NULL,
 revoked_at DATETIME NULL,
 INDEX (store_user_id), INDEX (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS identity_codes (
 code_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
 client_id VARCHAR(24) NOT NULL,
 redirect_uri VARCHAR(255) NOT NULL,
 session_id CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 challenge CHAR(43) CHARACTER SET ascii COLLATE ascii_bin NOT NULL,
 expires_at DATETIME NOT NULL,
 used_at DATETIME NULL,
 INDEX (expires_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
CREATE TABLE IF NOT EXISTS identity_login_limits (
 ip_hash CHAR(64) CHARACTER SET ascii COLLATE ascii_bin NOT NULL PRIMARY KEY,
 attempts SMALLINT UNSIGNED NOT NULL DEFAULT 0,
 window_started DATETIME NOT NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
