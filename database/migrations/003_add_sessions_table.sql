-- Sessions table for database-backed PHP sessions (required on Vercel,
-- where file sessions do not survive across serverless invocations).
-- Run once on an existing database; fresh installs already have it in
-- database/rst_db.sql.
CREATE TABLE IF NOT EXISTS tblSessions (
  id VARCHAR(128) NOT NULL,
  data TEXT NULL,
  last_activity INT UNSIGNED NOT NULL,
  PRIMARY KEY (id),
  KEY idx_sessions_activity (last_activity)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
