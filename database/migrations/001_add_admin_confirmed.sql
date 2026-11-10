-- Add admin confirmation to tblSchedules.
-- Run this once on an existing database (fresh installs already include it).
-- MySQL:
ALTER TABLE tblSchedules ADD COLUMN admin_confirmed TINYINT(1) NOT NULL DEFAULT 0 AFTER schedule_status;
-- SQLite (use this line instead on SQLite):
-- ALTER TABLE tblSchedules ADD COLUMN admin_confirmed INTEGER NOT NULL DEFAULT 0;
