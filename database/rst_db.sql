-- ============================================================================
-- Room Scheduling Tracker — MySQL schema
-- Source: RST_DB.xlsx (data dictionary supplied by the owner)
-- Database: rst_db
-- Engine: InnoDB / utf8mb4. All tables, columns, types and relationships
-- below match the spreadsheet exactly. No table or column was renamed.
--
-- Install:
--   mysql -u root -p < database/rst_db.sql
--   # then copy .env.example to .env and set DB_* to match
-- ============================================================================

CREATE DATABASE IF NOT EXISTS rst_db
  CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
USE rst_db;

-- ---------------------------------------------------------------------------
-- tblUsers
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tblUsers (
  user_id       INT UNSIGNED NOT NULL AUTO_INCREMENT,
  username      VARCHAR(50)  NOT NULL,
  email         VARCHAR(255) NOT NULL,
  password_hash VARCHAR(255) NOT NULL,
  profile_img   VARCHAR(255) NULL,
  role          VARCHAR(30)  NOT NULL DEFAULT 'student',
  section       VARCHAR(50)  NULL,
  org_name      VARCHAR(100) NULL,
  phone_number  VARCHAR(17)  NULL,
  PRIMARY KEY (user_id),
  UNIQUE KEY uq_users_username (username),
  UNIQUE KEY uq_users_email (email),
  KEY idx_users_role (role)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- tblMaps — one row per floor map
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tblMaps (
  map_id           INT UNSIGNED NOT NULL AUTO_INCREMENT,
  map_name         VARCHAR(100) NOT NULL,
  floor_number     INT NOT NULL,
  map_width        DECIMAL(10,2) NOT NULL,
  map_height       DECIMAL(10,2) NOT NULL,
  background_image VARCHAR(255) NULL,
  map_status       VARCHAR(20) NOT NULL DEFAULT 'active',
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (map_id),
  KEY idx_maps_status (map_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- tblRooms — rooms drawn on a map. shape_data is JSON geometry
-- (polygon / rectangle / path) in map-relative coordinates.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tblRooms (
  room_id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  map_id           INT UNSIGNED NOT NULL,
  room_name        VARCHAR(100) NOT NULL,
  shape_type       VARCHAR(20)  NOT NULL DEFAULT 'rectangle',
  shape_data       JSON NOT NULL,
  room_description VARCHAR(255) NULL,
  room_status      VARCHAR(20)  NOT NULL DEFAULT 'active',
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (room_id),
  UNIQUE KEY uq_rooms_map_name (map_id, room_name),
  CONSTRAINT fk_rooms_map FOREIGN KEY (map_id)
    REFERENCES tblMaps (map_id) ON DELETE CASCADE ON UPDATE CASCADE,
  KEY idx_rooms_status (room_status)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- tblSchedules — one row per schedule definition (the recurring series)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tblSchedules (
  schedule_id          INT UNSIGNED NOT NULL AUTO_INCREMENT,
  organizer_id         INT UNSIGNED NOT NULL,
  room_id              INT UNSIGNED NOT NULL,
  schedule_title       VARCHAR(150) NOT NULL,
  schedule_description TEXT NULL,
  recurrence_type      VARCHAR(20) NOT NULL DEFAULT 'one_time',
  start_date           DATE NOT NULL,
  end_date             DATE NULL,
  start_time           TIME NOT NULL,
  end_time             TIME NOT NULL,
  schedule_status      VARCHAR(20) NOT NULL DEFAULT 'active',
  created_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  updated_at           DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY (schedule_id),
  CONSTRAINT fk_schedules_organizer FOREIGN KEY (organizer_id)
    REFERENCES tblUsers (user_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  CONSTRAINT fk_schedules_room FOREIGN KEY (room_id)
    REFERENCES tblRooms (room_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  KEY idx_schedules_room_dates (room_id, start_date, end_date),
  KEY idx_schedules_organizer (organizer_id),
  CONSTRAINT chk_schedules_time CHECK (end_time > start_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- tblScheduleDays — weekdays a weekly schedule repeats on (1=Mon .. 7=Sun)
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tblScheduleDays (
  schedule_day_id INT UNSIGNED NOT NULL AUTO_INCREMENT,
  schedule_id     INT UNSIGNED NOT NULL,
  day_of_week     TINYINT UNSIGNED NOT NULL,
  PRIMARY KEY (schedule_day_id),
  UNIQUE KEY uq_schedule_days (schedule_id, day_of_week),
  CONSTRAINT fk_schedule_days_schedule FOREIGN KEY (schedule_id)
    REFERENCES tblSchedules (schedule_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT chk_day_of_week CHECK (day_of_week BETWEEN 1 AND 7)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- tblScheduleExceptions — skip / move / modify one occurrence of a series
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tblScheduleExceptions (
  exception_id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  schedule_id      INT UNSIGNED NOT NULL,
  original_date    DATE NOT NULL,
  exception_type   VARCHAR(20) NOT NULL,
  new_date         DATE NULL,
  new_room_id      INT UNSIGNED NULL,
  new_start_time   TIME NULL,
  new_end_time     TIME NULL,
  exception_reason VARCHAR(255) NULL,
  created_by       INT UNSIGNED NOT NULL,
  created_at       DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY (exception_id),
  UNIQUE KEY uq_exceptions_schedule_date (schedule_id, original_date),
  CONSTRAINT fk_exceptions_schedule FOREIGN KEY (schedule_id)
    REFERENCES tblSchedules (schedule_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_exceptions_new_room FOREIGN KEY (new_room_id)
    REFERENCES tblRooms (room_id) ON DELETE SET NULL ON UPDATE CASCADE,
  CONSTRAINT fk_exceptions_created_by FOREIGN KEY (created_by)
    REFERENCES tblUsers (user_id) ON DELETE RESTRICT ON UPDATE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ---------------------------------------------------------------------------
-- tblScheduleOccurrences — materialised occurrences of a schedule.
-- Generated by the app (RecurrenceService) when a schedule is created or
-- edited. The spreadsheet notes this table is optional if occurrences are
-- calculated dynamically; this app stores them so conflicts, exceptions
-- and the calendar can be queried directly, and regenerates them on edit.
-- ---------------------------------------------------------------------------
CREATE TABLE IF NOT EXISTS tblScheduleOccurrences (
  occurrence_id     INT UNSIGNED NOT NULL AUTO_INCREMENT,
  schedule_id       INT UNSIGNED NOT NULL,
  occurrence_date   DATE NOT NULL,
  room_id           INT UNSIGNED NOT NULL,
  start_time        TIME NOT NULL,
  end_time          TIME NOT NULL,
  occurrence_status VARCHAR(20) NOT NULL DEFAULT 'scheduled',
  PRIMARY KEY (occurrence_id),
  UNIQUE KEY uq_occurrences (schedule_id, occurrence_date, room_id, start_time),
  CONSTRAINT fk_occurrences_schedule FOREIGN KEY (schedule_id)
    REFERENCES tblSchedules (schedule_id) ON DELETE CASCADE ON UPDATE CASCADE,
  CONSTRAINT fk_occurrences_room FOREIGN KEY (room_id)
    REFERENCES tblRooms (room_id) ON DELETE RESTRICT ON UPDATE CASCADE,
  KEY idx_occurrences_room_date (room_id, occurrence_date, start_time),
  KEY idx_occurrences_date (occurrence_date),
  CONSTRAINT chk_occurrences_time CHECK (end_time > start_time)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
